<?php

namespace Tests\Feature\Api;

use App\Models\Classroom;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\FinanceUnitSetting;
use App\Models\SppInvoice;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function classroom(string $unit = 'sma'): Classroom
    {
        return Classroom::create([
            'name' => strtoupper($unit).'-'.fake()->unique()->numberBetween(1, 999),
            'grade' => 10,
            'academic_year' => '2026/2027',
            'unit' => $unit,
        ]);
    }

    private function student(string $unit = 'sma'): Student
    {
        return Student::create([
            'nis' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'nisn' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'name' => 'Siswa '.$unit,
            'gender' => 'L',
            'classroom_id' => $this->classroom($unit)->id,
            'status' => 'aktif',
        ]);
    }

    public function test_bendahara_unit_is_forced_on_create(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_lpi', 'unit' => 'lpi']);

        $this->actingAs($bendahara)->postJson('/api/finance/transactions', [
            'unit' => 'sma',
            'type' => 'income',
            'transaction_date' => '2026-10-01',
            'amount' => 150000,
            'description' => 'Infaq',
        ])->assertCreated()->assertJsonPath('unit', 'lpi');

        $this->assertDatabaseHas('finance_transactions', ['unit' => 'lpi', 'amount' => 150000]);
        $this->assertDatabaseMissing('finance_transactions', ['unit' => 'sma']);
    }

    public function test_bendahara_cannot_see_other_unit_transactions(): void
    {
        $lpi = User::factory()->create(['role' => 'bendahara_lpi']);
        $sma = User::factory()->create(['role' => 'bendahara_sma']);

        FinanceTransaction::factory()->unit('lpi')->income()->create(['description' => 'LPI only']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['description' => 'SMA only']);

        $this->actingAs($lpi)->getJson('/api/finance/transactions?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'LPI only');

        $this->actingAs($sma)->getJson('/api/finance/transactions?type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'SMA only');
    }

    public function test_bendahara_cannot_touch_other_unit_record(): void
    {
        $lpi = User::factory()->create(['role' => 'bendahara_lpi']);
        $trx = FinanceTransaction::factory()->unit('sma')->create();

        $this->actingAs($lpi)->deleteJson("/api/finance/transactions/{$trx->id}")->assertStatus(403);
        $this->assertDatabaseHas('finance_transactions', ['id' => $trx->id]);
    }

    public function test_category_must_match_transaction_type(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $category = FinanceCategory::factory()->income()->unit('sma')->create();

        $this->actingAs($bendahara)->postJson('/api/finance/transactions', [
            'type' => 'expense',
            'category_id' => $category->id,
            'transaction_date' => '2026-10-01',
            'amount' => 10000,
            'description' => 'Salah kategori',
        ])->assertStatus(422);
    }

    public function test_admin_can_query_specific_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        FinanceTransaction::factory()->unit('lpi')->income()->create(['description' => 'LPI']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['description' => 'SMA']);

        $this->actingAs($admin)->getJson('/api/finance/transactions?unit=lpi&type=income')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'LPI');
    }

    public function test_admin_can_create_transaction_for_selected_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/finance/transactions?unit=lpi', [
            'type' => 'income',
            'transaction_date' => '2026-10-01',
            'amount' => 75000,
            'description' => 'Infaq LPI',
        ])->assertCreated()->assertJsonPath('unit', 'lpi');

        $this->assertDatabaseHas('finance_transactions', ['unit' => 'lpi', 'amount' => 75000]);
    }

    public function test_dashboard_includes_opening_balance_and_totals(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        FinanceUnitSetting::create(['unit' => 'sma', 'opening_balance' => 1000000]);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 300000]);
        FinanceTransaction::factory()->unit('sma')->expense()->create(['amount' => 200000]);

        $this->actingAs($bendahara)->getJson('/api/finance/dashboard')
            ->assertOk()
            ->assertJsonPath('opening_balance', 1000000)
            ->assertJsonPath('total_income', 300000)
            ->assertJsonPath('total_expense', 200000)
            ->assertJsonPath('balance', 1100000);
    }

    public function test_non_finance_roles_cannot_access_finance(): void
    {
        foreach (['guru', 'wali_murid'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->getJson('/api/finance/transactions')->assertStatus(403);
        }
    }

    public function test_paying_spp_updates_invoice_and_records_income_once(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $invoice = SppInvoice::factory()->create(['unit' => 'sma', 'amount' => 500000]);

        $this->actingAs($bendahara)->postJson("/api/finance/spp/invoices/{$invoice->id}/payments", [
            'amount' => 500000,
            'method' => 'tunai',
        ])->assertCreated();

        $invoice->refresh();
        $this->assertSame(SppInvoice::STATUS_PAID, $invoice->status);
        $this->assertEquals(500000, (float) $invoice->paid_amount);

        $this->assertDatabaseCount('spp_payments', 1);
        $this->assertDatabaseCount('finance_transactions', 1);
        $this->assertDatabaseHas('finance_transactions', [
            'unit' => 'sma',
            'type' => 'income',
            'source' => 'spp',
            'amount' => 500000,
        ]);

        $this->actingAs($bendahara)->getJson('/api/finance/dashboard')
            ->assertOk()
            ->assertJsonPath('total_income', 500000)
            ->assertJsonPath('spp_outstanding', 0);
    }

    public function test_spp_payment_cannot_exceed_remaining(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $invoice = SppInvoice::factory()->create(['unit' => 'sma', 'amount' => 500000]);

        $this->actingAs($bendahara)->postJson("/api/finance/spp/invoices/{$invoice->id}/payments", [
            'amount' => 600000,
        ])->assertStatus(422);

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertEquals(SppInvoice::STATUS_UNPAID, $invoice->fresh()->status);
    }

    public function test_partial_payment_marks_invoice_partial(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $invoice = SppInvoice::factory()->create(['unit' => 'sma', 'amount' => 500000]);

        $this->actingAs($bendahara)->postJson("/api/finance/spp/invoices/{$invoice->id}/payments", [
            'amount' => 200000,
        ])->assertCreated();

        $this->assertSame(SppInvoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_generate_invoices_only_for_unit_and_skips_existing(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $sma = $this->student('sma');
        $lpi = $this->student('lpi');
        SppInvoice::factory()->create(['unit' => 'sma', 'student_id' => $sma->id, 'period' => '2026-10']);

        $this->actingAs($bendahara)->postJson('/api/finance/spp/invoices/generate', [
            'period' => '2026-10',
            'amount' => 400000,
        ])->assertOk()->assertJsonPath('created', 0);

        $this->assertDatabaseCount('spp_invoices', 1);

        $this->actingAs($bendahara)->postJson('/api/finance/spp/invoices/generate', [
            'period' => '2026-11',
            'amount' => 400000,
        ])->assertOk()->assertJsonPath('created', 1);

        $this->assertDatabaseHas('spp_invoices', ['student_id' => $sma->id, 'period' => '2026-11']);
        $this->assertDatabaseMissing('spp_invoices', ['student_id' => $lpi->id]);
    }

    public function test_bendahara_cannot_pay_other_unit_invoice(): void
    {
        $lpi = User::factory()->create(['role' => 'bendahara_lpi']);
        $invoice = SppInvoice::factory()->create(['unit' => 'sma']);

        $this->actingAs($lpi)->postJson("/api/finance/spp/invoices/{$invoice->id}/payments", [
            'amount' => 100000,
        ])->assertStatus(403);
    }

    public function test_cancelling_payment_removes_income_and_reverts_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = SppInvoice::factory()->create(['unit' => 'sma', 'amount' => 500000]);
        $payment = SppPayment::factory()->create([
            'spp_invoice_id' => $invoice->id,
            'unit' => 'sma',
            'amount' => 500000,
        ]);
        FinanceTransaction::create([
            'unit' => 'sma',
            'type' => 'income',
            'source' => 'spp',
            'transaction_date' => '2026-10-01',
            'amount' => 500000,
            'description' => 'SPP',
            'reference' => 'spp-payment-'.$payment->id,
        ]);
        $invoice->refreshStatus();
        $this->assertSame(SppInvoice::STATUS_PAID, $invoice->fresh()->status);

        $this->actingAs($admin)->deleteJson("/api/finance/spp/payments/{$payment->id}")->assertOk();

        $this->assertDatabaseCount('spp_payments', 0);
        $this->assertDatabaseCount('finance_transactions', 0);
        $this->assertSame(SppInvoice::STATUS_UNPAID, $invoice->fresh()->status);
    }

    public function test_bendahara_cannot_cancel_payment(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $payment = SppPayment::factory()->create(['unit' => 'sma']);

        $this->actingAs($bendahara)->deleteJson("/api/finance/spp/payments/{$payment->id}")->assertStatus(403);
        $this->assertDatabaseCount('spp_payments', 1);
    }

    public function test_spp_student_search_is_unit_scoped(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $this->student('sma');
        $this->student('lpi');

        $res = $this->actingAs($bendahara)->getJson('/api/finance/spp/students')->assertOk();
        $this->assertCount(1, $res->json());
    }

    public function test_report_groups_by_category(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $category = FinanceCategory::factory()->expense()->unit('sma')->create(['name' => 'Listrik']);
        FinanceTransaction::factory()->unit('sma')->expense()->create([
            'category_id' => $category->id,
            'amount' => 250000,
            'transaction_date' => '2026-10-05',
        ]);

        $this->actingAs($bendahara)->getJson('/api/finance/report')
            ->assertOk()
            ->assertJsonPath('total_expense', 250000)
            ->assertJsonPath('by_category.0.category', 'Listrik');
    }

    public function test_report_aggregates_by_day_and_dashboard_trend(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 100000, 'transaction_date' => '2026-10-01']);
        FinanceTransaction::factory()->unit('sma')->expense()->create(['amount' => 40000, 'transaction_date' => '2026-10-01']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 50000, 'transaction_date' => '2026-11-02']);

        $report = $this->actingAs($bendahara)->getJson('/api/finance/report?from=2026-10-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('total_income', 150000)
            ->assertJsonPath('total_expense', 40000)
            ->assertJsonCount(2, 'by_day');

        $this->assertEquals('2026-10-01', $report->json('by_day.0.date'));
        $this->assertEquals(100000, $report->json('by_day.0.income'));
        $this->assertEquals(40000, $report->json('by_day.0.expense'));
        $this->assertEquals('2026-11-02', $report->json('by_day.1.date'));
        $this->assertEquals(50000, $report->json('by_day.1.income'));

        $this->actingAs($bendahara)->getJson('/api/finance/dashboard?from=2026-10-01&to=2026-11-30')
            ->assertOk()
            ->assertJsonPath('trend.0.period', '2026-10')
            ->assertJsonPath('trend.0.income', 100000)
            ->assertJsonPath('trend.0.expense', 40000)
            ->assertJsonPath('trend.1.period', '2026-11')
            ->assertJsonPath('trend.1.income', 50000);
    }

    public function test_report_honours_end_date_inclusively(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 10000, 'transaction_date' => '2026-10-31']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 90000, 'transaction_date' => '2026-11-01']);

        $this->actingAs($bendahara)->getJson('/api/finance/report?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertJsonPath('total_income', 10000);
    }

    public function test_spp_report_aggregates_invoices_and_payments(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $student = $this->student('sma');

        $partial = SppInvoice::factory()->create([
            'unit' => 'sma',
            'student_id' => $student->id,
            'period' => '2026-10',
            'amount' => 500000,
            'paid_amount' => 200000,
            'status' => SppInvoice::STATUS_PARTIAL,
        ]);
        SppInvoice::factory()->create([
            'unit' => 'sma',
            'student_id' => $student->id,
            'period' => '2026-11',
            'amount' => 300000,
            'paid_amount' => 0,
            'status' => SppInvoice::STATUS_UNPAID,
        ]);
        SppPayment::factory()->create([
            'spp_invoice_id' => $partial->id,
            'unit' => 'sma',
            'amount' => 200000,
        ]);

        $this->actingAs($bendahara)->getJson('/api/finance/spp/report')
            ->assertOk()
            ->assertJsonPath('invoice_total', 800000)
            ->assertJsonPath('invoice_paid', 200000)
            ->assertJsonPath('outstanding', 600000)
            ->assertJsonPath('payments_total', 200000)
            ->assertJsonPath('payments_count', 1)
            ->assertJsonPath('invoices_by_status.unpaid', 1)
            ->assertJsonPath('invoices_by_status.partial', 1)
            ->assertJsonPath('invoices_by_status.paid', 0);
    }

    public function test_generate_invoices_across_chunks(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        $classroom = $this->classroom('sma');
        $now = now();
        $rows = [];

        for ($i = 1; $i <= 205; $i++) {
            $rows[] = [
                'nis' => 'N'.$i,
                'nisn' => 'NN'.$i,
                'name' => 'Siswa '.$i,
                'gender' => 'L',
                'classroom_id' => $classroom->id,
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Student::insert($rows);

        $this->actingAs($bendahara)->postJson('/api/finance/spp/invoices/generate', [
            'period' => '2026-12',
            'amount' => 400000,
        ])->assertOk()->assertJsonPath('created', 205);

        $this->assertDatabaseCount('spp_invoices', 205);
    }

    public function test_preview_returns_only_requested_type_and_total(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 100000, 'description' => 'Infaq']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 250000, 'description' => 'SPP']);
        FinanceTransaction::factory()->unit('sma')->expense()->create(['amount' => 99000, 'description' => 'Listrik']);
        FinanceTransaction::factory()->unit('lpi')->income()->create(['amount' => 500000, 'description' => 'LPI only']);

        $this->actingAs($bendahara)->getJson('/api/finance/transactions/preview?type=income')
            ->assertOk()
            ->assertJsonPath('type', 'income')
            ->assertJsonPath('unit', 'sma')
            ->assertJsonPath('total', 350000)
            ->assertJsonCount(2, 'transactions');

        $this->actingAs($bendahara)->getJson('/api/finance/transactions/preview?type=expense')
            ->assertOk()
            ->assertJsonPath('total', 99000)
            ->assertJsonCount(1, 'transactions');
    }

    public function test_preview_rejects_invalid_type(): void
    {
        $bendahara = User::factory()->create(['role' => 'bendahara_sma']);

        $this->actingAs($bendahara)->getJson('/api/finance/transactions/preview?type=transfer')
            ->assertStatus(422);
    }

    public function test_preview_honours_cross_unit_isolation(): void
    {
        $lpi = User::factory()->create(['role' => 'bendahara_lpi']);
        $sma = User::factory()->create(['role' => 'bendahara_sma']);
        FinanceTransaction::factory()->unit('sma')->income()->create(['amount' => 100000, 'description' => 'SMA only']);
        FinanceTransaction::factory()->unit('lpi')->income()->create(['amount' => 400000, 'description' => 'LPI only']);

        $this->actingAs($lpi)->getJson('/api/finance/transactions/preview?type=income&unit=sma')
            ->assertOk()
            ->assertJsonPath('unit', 'lpi')
            ->assertJsonPath('total', 400000)
            ->assertJsonCount(1, 'transactions');

        $this->actingAs($sma)->getJson('/api/finance/transactions/preview?type=income')
            ->assertOk()
            ->assertJsonPath('total', 100000);
    }

    public function test_non_finance_roles_cannot_access_preview(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->getJson('/api/finance/transactions/preview?type=income')->assertStatus(403);
    }
}
