<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\FinanceUnitSetting;
use App\Models\SppInvoice;
use App\Models\SppPayment;
use Illuminate\Database\Eloquent\Builder;
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

        $totals = $this->transactionRange(FinanceTransaction::query()->where('unit', $unit), $range)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = (float) ($totals[FinanceTransaction::TYPE_INCOME] ?? 0);
        $expense = (float) ($totals[FinanceTransaction::TYPE_EXPENSE] ?? 0);

        $opening = (float) (FinanceUnitSetting::where('unit', $unit)->value('opening_balance') ?? 0);

        $invoiceStats = SppInvoice::query()
            ->where('unit', $unit)
            ->selectRaw(
                'COALESCE(SUM(amount), 0) as total, COALESCE(SUM(paid_amount), 0) as paid, '
                .'COUNT(*) as total_count, '
                .'COALESCE(SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END), 0) as unpaid_count',
                [SppInvoice::STATUS_UNPAID, SppInvoice::STATUS_PARTIAL],
            )
            ->first();

        $paymentRange = $range === null
            ? null
            : [Carbon::parse($range[0])->startOfDay(), Carbon::parse($range[1])->endOfDay()];

        $sppReceived = (float) SppPayment::where('unit', $unit)
            ->when($paymentRange, fn ($q) => $q->whereBetween('paid_at', $paymentRange))
            ->sum('amount');

        $recent = $this->transactionRange(FinanceTransaction::with('category')->where('unit', $unit), $range)
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
            'spp_invoice_total' => round((float) $invoiceStats->total, 2),
            'spp_received' => round($sppReceived, 2),
            'spp_outstanding' => round(max(0, (float) $invoiceStats->total - (float) $invoiceStats->paid), 2),
            'spp_invoice_count' => (int) $invoiceStats->total_count,
            'spp_unpaid_count' => (int) $invoiceStats->unpaid_count,
            'recent_transactions' => $recent,
            'trend' => $trend,
            'range' => ['from' => $range[0] ?? null, 'to' => $range[1] ?? null],
        ];
    }

    private function transactionRange(Builder $query, ?array $range): Builder
    {
        return $query->when($range, fn ($q) => $q
            ->where('transaction_date', '>=', $range[0])
            ->where('transaction_date', '<', Carbon::parse($range[1])->addDay()->startOfDay()));
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
        $endExclusive = Carbon::parse($end)->addDay()->startOfDay();

        $periodExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', transaction_date)",
            'pgsql' => "to_char(transaction_date, 'YYYY-MM')",
            default => "DATE_FORMAT(transaction_date, '%Y-%m')",
        };

        $rows = FinanceTransaction::query()
            ->selectRaw("{$periodExpression} as period, type, SUM(amount) as total")
            ->where('unit', $unit)
            ->where('transaction_date', '>=', $start)
            ->where('transaction_date', '<', $endExclusive)
            ->groupBy('period', 'type')
            ->orderBy('period')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->groupBy('period')
            ->map(fn ($group, $period) => [
                'period' => $period,
                'income' => round((float) $group->where('type', FinanceTransaction::TYPE_INCOME)->sum('total'), 2),
                'expense' => round((float) $group->where('type', FinanceTransaction::TYPE_EXPENSE)->sum('total'), 2),
            ])
            ->values()
            ->all();
    }
}
