<?php

namespace App\Services;

use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\SppInvoice;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SppService
{
    /**
     * Catat pembayaran SPP secara atomik.
     * Tagihan dikunci (lockForUpdate) agar dua request bersamaan tidak dapat
     * melewati sisa tagihan. Pemasukan keuangan dibuat satu kali per pembayaran
     * dengan referensi unik sehingga saldo tidak menghitung ganda.
     *
     * @param  array{amount: float|int|string, paid_at?: string|null, method?: string|null, notes?: string|null, proof_path?: string|null}  $data
     */
    public function pay(SppInvoice $invoice, array $data, User $actor): SppPayment
    {
        $unit = $actor->financeUnit();

        if ($unit !== null && $invoice->unit !== $unit) {
            abort(403, 'Tagihan bukan milik unit akun ini.');
        }

        return DB::transaction(function () use ($invoice, $data, $actor) {
            /** @var SppInvoice $locked */
            $locked = SppInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === SppInvoice::STATUS_PAID) {
                throw ValidationException::withMessages(['amount' => 'Tagihan ini sudah lunas.']);
            }

            $amount = round((float) $data['amount'], 2);
            $remaining = $locked->remainingAmount();

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari nol.']);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan (Rp '.number_format($remaining, 0, ',', '.').').']);
            }

            $paidAt = isset($data['paid_at']) && $data['paid_at']
                ? Carbon::parse($data['paid_at'])
                : Carbon::now();

            $payment = SppPayment::create([
                'spp_invoice_id' => $locked->id,
                'unit' => $locked->unit,
                'receipt_number' => $this->nextReceiptNumber($locked->unit, $paidAt),
                'amount' => $amount,
                'paid_at' => $paidAt,
                'method' => $data['method'] ?? 'tunai',
                'notes' => $data['notes'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'received_by' => $actor->id,
            ]);

            $locked->refreshStatus();

            $category = FinanceCategory::firstOrCreate(
                ['unit' => $locked->unit, 'type' => FinanceTransaction::TYPE_INCOME, 'name' => 'Pembayaran SPP'],
                ['is_active' => true],
            );

            FinanceTransaction::firstOrCreate(
                ['reference' => 'spp-payment-'.$payment->id],
                [
                    'unit' => $locked->unit,
                    'category_id' => $category->id,
                    'type' => FinanceTransaction::TYPE_INCOME,
                    'source' => FinanceTransaction::SOURCE_SPP,
                    'transaction_date' => $paidAt->toDateString(),
                    'amount' => $amount,
                    'description' => 'Pembayaran SPP '.$locked->student?->name.' periode '.$locked->period,
                    'destination' => null,
                    'created_by' => $actor->id,
                ],
            );

            return $payment->load(['invoice.student']);
        });
    }

    /**
     * Buat tagihan untuk satu siswa. Tagihan yang sudah ada tidak diubah.
     */
    public function createInvoice(Student $student, array $data): SppInvoice
    {
        $unit = $student->unit();

        $exists = SppInvoice::where('student_id', $student->id)->where('period', $data['period'])->exists();
        if ($exists) {
            throw ValidationException::withMessages(['period' => 'Tagihan untuk periode ini sudah ada.']);
        }

        return SppInvoice::create([
            'unit' => $unit,
            'student_id' => $student->id,
            'period' => $data['period'],
            'due_date' => $data['due_date'] ?? null,
            'amount' => round((float) $data['amount'], 2),
            'paid_amount' => 0,
            'status' => SppInvoice::STATUS_UNPAID,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Generate beberapa tagihan sekaligus untuk satu unit & periode.
     *
     * @return int jumlah tagihan baru
     */
    public function generateForUnit(string $unit, string $period, float $amount, ?string $dueDate = null): int
    {
        $created = 0;

        Student::where('status', 'aktif')
            ->whereHas('classroom', fn ($q) => $q->where('unit', $unit))
            ->chunkById(200, function ($students) use ($unit, $period, $amount, $dueDate, &$created) {
                foreach ($students as $student) {
                    $exists = SppInvoice::where('student_id', $student->id)->where('period', $period)->exists();
                    if ($exists) {
                        continue;
                    }
                    SppInvoice::create([
                        'unit' => $unit,
                        'student_id' => $student->id,
                        'period' => $period,
                        'due_date' => $dueDate,
                        'amount' => $amount,
                        'paid_amount' => 0,
                        'status' => SppInvoice::STATUS_UNPAID,
                    ]);
                    $created++;
                }
            });

        return $created;
    }

    private function nextReceiptNumber(string $unit, Carbon $paidAt): string
    {
        $prefix = 'SPP-'.strtoupper($unit).'-'.$paidAt->format('Ym').'-';

        $last = SppPayment::where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
