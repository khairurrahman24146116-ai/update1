<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesFinanceUnit;
use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\SppInvoice;
use App\Models\SppPayment;
use App\Models\Student;
use App\Services\ActivityLogger;
use App\Services\SppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SppController extends Controller
{
    use ResolvesFinanceUnit;

    public function __construct(protected SppService $spp) {}

    /**
     * Cari siswa pada unit akun, hanya siswa yang punya kelas di unit tersebut.
     */
    public function students(Request $request)
    {
        $unit = $this->resolveUnit($request);
        $search = trim((string) $request->query('search', ''));

        $query = Student::with('classroom:id,name,unit')
            ->whereHas('classroom', fn ($q) => $q->where('unit', $unit))
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%")));

        return $query->orderBy('name')->limit(25)->get();
    }

    public function index(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $query = SppInvoice::with(['student:id,nis,nisn,name,classroom_id', 'student.classroom:id,name,unit'])
            ->where('unit', $unit)
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->period, fn ($q, $v) => $q->where('period', $v))
            ->when($request->student_id, fn ($q, $v) => $q->where('student_id', $v))
            ->when($request->search, fn ($q, $v) => $q->whereHas('student', fn ($q2) => $q2
                ->where('name', 'like', "%{$v}%")
                ->orWhere('nis', 'like', "%{$v}%")
                ->orWhere('nisn', 'like', "%{$v}%")));

        return $query->orderByDesc('period')->orderBy('student_id')
            ->paginate(min((int) $request->query('per_page', 15), 100));
    }

    public function show(Request $request, SppInvoice $sppInvoice)
    {
        $this->authorizeUnit($request, $sppInvoice->unit);

        return $sppInvoice->load([
            'student:id,nis,nisn,name,classroom_id',
            'student.classroom:id,name,unit',
            'payments' => fn ($q) => $q->orderByDesc('paid_at'),
            'payments.receiver:id,name',
        ]);
    }

    public function store(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $v = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => 'required|numeric|min:0.01|max:9999999999999',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $student = Student::with('classroom')->findOrFail($v['student_id']);
        if ($student->unit() !== $unit) {
            abort(403, 'Siswa bukan milik unit akun ini.');
        }

        $invoice = $this->spp->createInvoice($student, $v);
        ActivityLogger::log('spp.invoice.create', $request->user(), SppInvoice::class, $invoice->id, [
            'unit' => $unit,
            'student_id' => $student->id,
            'period' => $invoice->period,
        ]);

        return response()->json($invoice->load('student'), 201);
    }

    public function generate(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $v = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => 'required|numeric|min:0.01|max:9999999999999',
            'due_date' => 'nullable|date',
        ]);

        $created = $this->spp->generateForUnit($unit, $v['period'], (float) $v['amount'], $v['due_date'] ?? null);

        ActivityLogger::log('spp.invoice.generate', $request->user(), SppInvoice::class, null, [
            'unit' => $unit,
            'period' => $v['period'],
            'created' => $created,
        ]);

        return response()->json(['created' => $created, 'unit' => $unit, 'period' => $v['period']]);
    }

    public function pay(Request $request, SppInvoice $sppInvoice)
    {
        $this->authorizeUnit($request, $sppInvoice->unit);

        $v = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_at' => 'nullable|date',
            'method' => ['nullable', Rule::in(['tunai', 'transfer', 'qris', 'lainnya'])],
            'notes' => 'nullable|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        if ($request->hasFile('proof')) {
            $v['proof_path'] = $request->file('proof')->store('spp/proofs', 'public');
        }
        unset($v['proof']);

        $payment = $this->spp->pay($sppInvoice, $v, $request->user());

        ActivityLogger::log('spp.payment.create', $request->user(), SppPayment::class, $payment->id, [
            'unit' => $payment->unit,
            'invoice_id' => $payment->spp_invoice_id,
            'amount' => $payment->amount,
        ]);

        return response()->json($payment->load(['invoice.student', 'invoice.payments']), 201);
    }

    public function payments(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $query = SppPayment::with(['invoice.student:id,nis,nisn,name,classroom_id', 'receiver:id,name'])
            ->where('unit', $unit)
            ->when($request->from, fn ($q, $v) => $q->whereDate('paid_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('paid_at', '<=', $v))
            ->when($request->period, fn ($q, $v) => $q->whereHas('invoice', fn ($q2) => $q2->where('period', $v)))
            ->when($request->method, fn ($q, $v) => $q->where('method', $v))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q2) => $q2
                ->where('receipt_number', 'like', "%{$v}%")
                ->orWhereHas('invoice.student', fn ($q3) => $q3
                    ->where('name', 'like', "%{$v}%")
                    ->orWhere('nis', 'like', "%{$v}%")
                    ->orWhere('nisn', 'like', "%{$v}%"))));

        return $query->orderByDesc('paid_at')->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 15), 100));
    }

    public function destroyPayment(Request $request, SppPayment $sppPayment)
    {
        $this->authorizeUnit($request, $sppPayment->unit);

        $invoice = $sppPayment->invoice;
        $paymentId = $sppPayment->id;

        DB::transaction(function () use ($sppPayment, $invoice) {
            $sppPayment->delete();
            FinanceTransaction::where('reference', 'spp-payment-'.$sppPayment->id)->delete();
            $invoice->refreshStatus();
        });

        ActivityLogger::log('spp.payment.cancel', $request->user(), SppPayment::class, $paymentId, [
            'unit' => $invoice->unit,
            'invoice_id' => $invoice->id,
        ]);

        return response()->json(['invoice' => $invoice->fresh()]);
    }

    public function outstanding(Request $request)
    {
        $unit = $this->resolveUnit($request);

        return SppInvoice::with(['student:id,nis,nisn,name,classroom_id', 'student.classroom:id,name'])
            ->where('unit', $unit)
            ->whereIn('status', [SppInvoice::STATUS_UNPAID, SppInvoice::STATUS_PARTIAL])
            ->orderBy('period')
            ->orderBy('student_id')
            ->paginate(min((int) $request->query('per_page', 15), 100));
    }

    public function report(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $from = $request->query('from');
        $to = $request->query('to');

        $invoices = SppInvoice::where('unit', $unit)
            ->when($from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->get();

        $payments = SppPayment::where('unit', $unit)
            ->when($from, fn ($q, $v) => $q->whereDate('paid_at', '>=', $v))
            ->when($to, fn ($q, $v) => $q->whereDate('paid_at', '<=', $v))
            ->get();

        return response()->json([
            'unit' => $unit,
            'invoice_total' => round((float) $invoices->sum('amount'), 2),
            'invoice_paid' => round((float) $invoices->sum('paid_amount'), 2),
            'outstanding' => round((float) $invoices->sum(fn ($i) => $i->remainingAmount()), 2),
            'payments_total' => round((float) $payments->sum('amount'), 2),
            'payments_count' => $payments->count(),
            'invoices_by_status' => [
                'unpaid' => $invoices->where('status', SppInvoice::STATUS_UNPAID)->count(),
                'partial' => $invoices->where('status', SppInvoice::STATUS_PARTIAL)->count(),
                'paid' => $invoices->where('status', SppInvoice::STATUS_PAID)->count(),
            ],
        ]);
    }
}
