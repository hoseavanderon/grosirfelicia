<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionReportService
{
    /**
     * @return array<string, mixed>
     */
    public function getReport(int $userId, string $fromDate, string $toDate): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $summary = $this->buildSummary($userId, $from, $to);
        $dailyLabels = $this->buildDailyLabels($from, $to);
        $salesSeries = $this->buildSalesSeries($userId, $from, $to, $dailyLabels);
        $itemsSeries = $this->buildItemsSeries($userId, $from, $to, $dailyLabels);
        $transactionsSeries = $this->buildTransactionsSeries($userId, $from, $to, $dailyLabels);
        $categoriesSeries = $this->buildCategoriesSeries($userId, $from, $to);

        return [
            'summary' => $summary,
            'charts' => [
                'labels' => $dailyLabels,
                'sales' => $salesSeries,
                'items_sold' => $itemsSeries,
                'transactions' => $transactionsSeries,
                'categories' => $categoriesSeries,
            ],
            'chart_stats' => [
                'sales' => $this->buildStats($salesSeries),
                'items_sold' => $this->buildStats($itemsSeries),
                'transactions' => $this->buildStats($transactionsSeries),
                'categories' => $this->buildStats($categoriesSeries['values'] ?? []),
            ],
            'top_customers' => $this->buildTopCustomers($userId, $from, $to),
            'best_products' => $this->buildBestProducts($userId, $from, $to),
        ];
    }

    /**
     * @return array{
     *     total_transactions: int,
     *     total_sales: int,
     *     unique_stores: int,
     *     total_items_sold: int
     * }
     */
    private function buildSummary(int $userId, Carbon $from, Carbon $to): array
    {
        $totalTransactions = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $detailStats = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(detail_transactions.harga_jual * detail_transactions.pcs), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total_items_sold')
            ->first();

        $uniqueStores = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->count('customer_id');

        return [
            'total_transactions' => (int) $totalTransactions,
            'total_sales' => (int) ($detailStats->total_sales ?? 0),
            'unique_stores' => (int) $uniqueStores,
            'total_items_sold' => (int) ($detailStats->total_items_sold ?? 0),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildDailyLabels(Carbon $from, Carbon $to): array
    {
        $labels = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $labels;
    }

    /**
     * @param  array<int, string>  $dailyLabels
     * @return array<int, int>
     */
    private function buildSalesSeries(int $userId, Carbon $from, Carbon $to, array $dailyLabels): array
    {
        $rows = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->selectRaw('DATE(transactions.created_at) as day')
            ->selectRaw('COALESCE(SUM(detail_transactions.harga_jual * detail_transactions.pcs), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->mapDailySeries($dailyLabels, $rows);
    }

    /**
     * @param  array<int, string>  $dailyLabels
     * @return array<int, int>
     */
    private function buildItemsSeries(int $userId, Carbon $from, Carbon $to, array $dailyLabels): array
    {
        $rows = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->selectRaw('DATE(transactions.created_at) as day')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->mapDailySeries($dailyLabels, $rows);
    }

    /**
     * @param  array<int, string>  $dailyLabels
     * @return array<int, int>
     */
    private function buildTransactionsSeries(int $userId, Carbon $from, Carbon $to, array $dailyLabels): array
    {
        $rows = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(id) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->mapDailySeries($dailyLabels, $rows);
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildCategoriesSeries(int $userId, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->join('detail_products', 'detail_products.id', '=', 'detail_transactions.detail_product_id')
            ->join('products', 'products.id', '=', 'detail_products.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.user_id', $userId)
            ->where('products.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereNull('detail_products.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->groupBy('categories.id', 'categories.category')
            ->selectRaw('categories.category as label')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total')
            ->orderByDesc('total')
            ->orderBy('categories.category')
            ->get();

        return [
            'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->values()->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array{name: string, total_pcs: int}>
     */
    private function buildTopCustomers(int $userId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->join('customers', 'customers.id', '=', 'transactions.customer_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereNull('customers.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->groupBy('customers.id', 'customers.nama_pelanggan')
            ->selectRaw('customers.nama_pelanggan as name')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total_pcs')
            ->orderByDesc('total_pcs')
            ->orderBy('customers.nama_pelanggan')
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'total_pcs' => (int) $row->total_pcs,
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, total_pcs: int}>
     */
    private function buildBestProducts(int $userId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('detail_transactions')
            ->join('transactions', 'transactions.id', '=', 'detail_transactions.transaction_id')
            ->join('detail_products', 'detail_products.id', '=', 'detail_transactions.detail_product_id')
            ->join('products', 'products.id', '=', 'detail_products.product_id')
            ->where('transactions.user_id', $userId)
            ->where('products.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereNull('detail_transactions.deleted_at')
            ->whereNull('detail_products.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->groupBy('products.id', 'products.nama_produk')
            ->selectRaw('products.nama_produk as name')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total_pcs')
            ->orderByDesc('total_pcs')
            ->orderBy('products.nama_produk')
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'total_pcs' => (int) $row->total_pcs,
            ])
            ->values();
    }

    /**
     * @param  array<int, string>  $dailyLabels
     * @param  Collection<string, mixed>  $rows
     * @return array<int, int>
     */
    private function mapDailySeries(array $dailyLabels, Collection $rows): array
    {
        return collect($dailyLabels)
            ->map(fn (string $day) => (int) ($rows[$day] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|float>  $values
     * @return array{total: int|float, average: int|float, peak: int|float}
     */
    private function buildStats(array $values): array
    {
        if ($values === []) {
            return [
                'total' => 0,
                'average' => 0,
                'peak' => 0,
            ];
        }

        $total = array_sum($values);
        $count = count($values);
        $peak = max($values);

        return [
            'total' => $total,
            'average' => $count > 0 ? round($total / $count, 2) : 0,
            'peak' => $peak,
        ];
    }
}
