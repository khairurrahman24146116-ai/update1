<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\FinanceUnitSetting;
use App\Models\SppInvoice;
use App\Models\SppPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Ringkasan dashboard keuangan untuk satu unit.
     * Pembayaran SPP ikut tercatat sebagai pemasukan (source=spp) sehingga
     * total pemasukan tidak menghitung dua kali.
     *
     * @return array<string, mixed>
     */
    public function summary(string $unit, ?string $from = null, ?string $to = null): array
    {
        $range = $this->range($from, $to);

        $income = (float) FinanceTransaction::where('unit', $unit)
            ->where('type', FinanceTransaction::TYPE_INCOME)
            ->when($range, fn ($q) => $q->whereBetween('transaction_date', $range))
            ->sum('amount');

        $expense = (float) FinanceTransaction::where('unit', $unit)
            ->where('type', FinanceTransaction::TYPE_EXPENSE)
            ->when($range, fn ($q) => $q->whereBetween('transaction_date', $range))
            ->sum('amount');

        $opening = (float) (FinanceUnitSetting::where('unit', $unit)->value('opening_balance') ?? 0);

        $invoiceQuery = SppInvoice::where('unit', $unit);
        $paymentQuery = SppPayment::where('unit', $unit)
            ->when($range, fn ($q) => $q->whereBetween(DB::raw('DATE(paid_at)'), $range));

        $invoiceTotal = (float) (clone $invoiceQuery)->sum('amount');
        $invoicePaid = (float) (clone $invoiceQuery)->sum('paid_amount');
        $sppReceived = (float) $paymentQuery->sum('amount');

        $recent = FinanceTransaction::with('category')
            ->where('unit', $unit)
            ->when($range, fn ($q) => $q->whereBetween('transaction_date', $range))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $trend = $this->monthlyTrend($unit, $range);

        return [
            'unit' => $unit,
            'opening_balance' => $opening,
            'total_income' => round($income, 2),
            'total_expense' => round($expense, 2),
            'balance' => round($opening + $income - $expense, 2),
            'spp_invoice_total' => round($invoiceTotal, 2),
            'spp_received' => round($sppReceived, 2),
            'spp_outstanding' => round(max(0, $invoiceTotal - $invoicePaid), 2),
            'spp_invoice_count' => (clone $invoiceQuery)->count(),
            'spp_unpaid_count' => (clone $invoiceQuery)->whereIn('status', [SppInvoice::STATUS_UNPAID, SppInvoice::STATUS_PARTIAL])->count(),
            'recent_transactions' => $recent,
            'trend' => $trend,
            'range' => ['from' => $range[0] ?? null, 'to' => $range[1] ?? null],
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function range(?string $from, ?string $to): ?array
    {
        if (! $from && ! $to) {
            return null;
        }

        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * @param  array<int, string>|null  $range
     * @return array<int, array{period: string, income: float, expense: float}>
     */
    private function monthlyTrend(string $unit, ?array $range): array
    {
        $start = $range[0] ?? Carbon::now()->subMonths(5)->startOfMonth()->toDateString();
        $end = $range[1] ?? Carbon::now()->endOfMonth()->toDateString();

        $rows = FinanceTransaction::query()
            ->select(['transaction_date', 'type', 'amount'])
            ->where('unit', $unit)
            ->whereBetween('transaction_date', [$start, $end])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->groupBy(fn ($r) => Carbon::parse($r->transaction_date)->format('Y-m'))
            ->sortKeys()
            ->map(fn ($group, $period) => [
                'period' => $period,
                'income' => round((float) $group->where('type', FinanceTransaction::TYPE_INCOME)->sum('amount'), 2),
                'expense' => round((float) $group->where('type', FinanceTransaction::TYPE_EXPENSE)->sum('amount'), 2),
            ])
            ->values()
            ->all();
    }
}
