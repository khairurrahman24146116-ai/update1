<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesFinanceUnit;
use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Services\ActivityLogger;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceTransactionController extends Controller
{
    use ResolvesFinanceUnit;

    public function __construct(protected FinanceService $finance) {}

    public function index(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $query = FinanceTransaction::with('category')
            ->where('unit', $unit)
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q2) => $q2
                ->where('description', 'like', "%{$v}%")
                ->orWhere('destination', 'like', "%{$v}%")
                ->orWhere('reference', 'like', "%{$v}%")));

        return $query->orderByDesc('transaction_date')->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 15), 100));
    }

    public function show(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUnit($request, $financeTransaction->unit);

        return $financeTransaction->load(['category', 'creator:id,name']);
    }

    public function store(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $v = $request->validate([
            'type' => ['required', Rule::in([FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_EXPENSE])],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('unit', $unit))],
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:9999999999999',
            'description' => 'required|string|max:255',
            'destination' => 'nullable|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        if (! empty($v['category_id'])) {
            $category = FinanceCategory::find($v['category_id']);
            if (! $category || $category->type !== $v['type']) {
                throw ValidationException::withMessages(['category_id' => 'Kategori tidak sesuai dengan jenis transaksi.']);
            }
        }

        if ($request->hasFile('proof')) {
            $v['proof_path'] = $request->file('proof')->store('finance/proofs', 'public');
        }

        $v['unit'] = $unit;
        $v['source'] = FinanceTransaction::SOURCE_MANUAL;
        $v['created_by'] = $request->user()->id;
        $v['amount'] = round((float) $v['amount'], 2);
        unset($v['proof']);

        $trx = FinanceTransaction::create($v);
        ActivityLogger::log('finance.transaction.create', $request->user(), FinanceTransaction::class, $trx->id, [
            'unit' => $unit,
            'type' => $trx->type,
            'amount' => $trx->amount,
        ]);

        return response()->json($trx->load('category'), 201);
    }

    public function update(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUnit($request, $financeTransaction->unit);

        if ($financeTransaction->source === FinanceTransaction::SOURCE_SPP) {
            throw ValidationException::withMessages(['transaction' => 'Transaksi pembayaran SPP tidak dapat diubah manual.']);
        }

        $unit = $financeTransaction->unit;

        $v = $request->validate([
            'type' => ['sometimes', Rule::in([FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_EXPENSE])],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('unit', $unit))],
            'transaction_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0.01|max:9999999999999',
            'description' => 'sometimes|string|max:255',
            'destination' => 'nullable|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        $type = $v['type'] ?? $financeTransaction->type;
        if (! empty($v['category_id'])) {
            $category = FinanceCategory::find($v['category_id']);
            if (! $category || $category->type !== $type) {
                throw ValidationException::withMessages(['category_id' => 'Kategori tidak sesuai dengan jenis transaksi.']);
            }
        }
        if (isset($v['amount'])) {
            $v['amount'] = round((float) $v['amount'], 2);
        }
        if ($request->hasFile('proof')) {
            if ($financeTransaction->proof_path) {
                Storage::disk('public')->delete($financeTransaction->proof_path);
            }
            $v['proof_path'] = $request->file('proof')->store('finance/proofs', 'public');
        }
        unset($v['proof']);

        $financeTransaction->update($v);
        ActivityLogger::log('finance.transaction.update', $request->user(), FinanceTransaction::class, $financeTransaction->id, [
            'unit' => $unit,
            'changes' => array_keys($v),
        ]);

        return $financeTransaction->fresh()->load('category');
    }

    public function destroy(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUnit($request, $financeTransaction->unit);

        if ($financeTransaction->source === FinanceTransaction::SOURCE_SPP) {
            throw ValidationException::withMessages(['transaction' => 'Transaksi pembayaran SPP tidak dapat dihapus manual.']);
        }

        if ($financeTransaction->proof_path) {
            Storage::disk('public')->delete($financeTransaction->proof_path);
        }

        $id = $financeTransaction->id;
        $financeTransaction->delete();
        ActivityLogger::log('finance.transaction.delete', $request->user(), FinanceTransaction::class, $id, [
            'unit' => $financeTransaction->unit,
        ]);

        return response()->noContent();
    }

    public function categories(Request $request)
    {
        $unit = $this->resolveUnit($request);

        return FinanceCategory::where('unit', $unit)
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->where('is_active', true)
            ->orderBy('type')->orderBy('name')
            ->get();
    }

    public function storeCategory(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $v = $request->validate([
            'type' => ['required', Rule::in([FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_EXPENSE])],
            'name' => ['required', 'string', 'max:120', Rule::unique('finance_categories', 'name')->where(fn ($q) => $q->where('unit', $unit)->where('type', $request->type))],
        ]);

        $category = FinanceCategory::create([...$v, 'unit' => $unit, 'is_active' => true]);

        return response()->json($category, 201);
    }

    public function dashboard(Request $request)
    {
        $unit = $this->resolveUnit($request);

        return $this->finance->summary($unit, $request->query('from'), $request->query('to'));
    }

    /**
     * Laporan pemasukan/pengeluaran per kategori + rekap harian.
     */
    public function report(Request $request)
    {
        $unit = $this->resolveUnit($request);

        $base = FinanceTransaction::query()
            ->where('unit', $unit)
            ->when($request->from, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v));

        $byCategory = (clone $base)
            ->selectRaw('type, category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('type', 'category_id')
            ->with('category:id,name')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'category' => $row->category?->name ?? 'Tanpa kategori',
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
            ]);

        $byDay = (clone $base)
            ->select(['transaction_date', 'type', 'amount'])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(fn ($r) => $r->transaction_date->toDateString())
            ->map(fn ($rows, $date) => [
                'date' => $date,
                'income' => round((float) $rows->where('type', FinanceTransaction::TYPE_INCOME)->sum('amount'), 2),
                'expense' => round((float) $rows->where('type', FinanceTransaction::TYPE_EXPENSE)->sum('amount'), 2),
            ])
            ->values();

        return response()->json([
            'unit' => $unit,
            'total_income' => round((float) (clone $base)->where('type', FinanceTransaction::TYPE_INCOME)->sum('amount'), 2),
            'total_expense' => round((float) (clone $base)->where('type', FinanceTransaction::TYPE_EXPENSE)->sum('amount'), 2),
            'by_category' => $byCategory,
            'by_day' => $byDay,
        ]);
    }
}
