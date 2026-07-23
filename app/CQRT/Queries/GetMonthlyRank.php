<?php

namespace App\CQRT\Queries;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetMonthlyRank
{
    public function __invoke(): array
    {
        return Cache::remember('analytics:monthly-rank', 3600, function () {
            $monthlyTotals = DB::table('transactions')
                ->select('user_id')
                ->selectRaw("TO_CHAR(transaction_date, 'YYYY-MM') as month")
                ->selectRaw('SUM(amount) as total_amount')
                ->groupBy('user_id')
                ->groupByRaw("TO_CHAR(transaction_date, 'YYYY-MM')");

            $results = DB::table('monthly_totals')
                ->withExpression('monthly_totals', $monthlyTotals)
                ->select('user_id', 'month', 'total_amount')
                ->selectRaw('RANK() OVER (PARTITION BY month ORDER BY total_amount DESC) as user_rank')
                ->selectRaw('ROUND(AVG(total_amount) OVER (PARTITION BY month)) as monthly_avg')
                ->orderBy('month', 'desc')
                ->orderBy('user_rank', 'asc')
                ->limit(100)
                ->get();

            return $results->map(function ($item) {
                return [
                    'user_id'      => (int) $item->user_id,
                    'month'        => (string) $item->month,
                    'total_amount' => (int) $item->total_amount,
                    'user_rank'    => (int) $item->user_rank,
                    'monthly_avg'  => (int) $item->monthly_avg,
                ];
            })->toArray();
        });
    }
}
