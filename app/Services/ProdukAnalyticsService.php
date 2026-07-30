<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProdukAnalyticsService
{
    public function bestSellers(int $userId, int $limit = 5): Collection
    {
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

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
            ->whereBetween('transactions.created_at', [$yearStart, $yearEnd])
            ->groupBy('products.id', 'products.nama_produk')
            ->selectRaw('products.id as product_id')
            ->selectRaw('products.nama_produk as name')
            ->selectRaw('COALESCE(SUM(detail_transactions.pcs), 0) as total_sold')
            ->orderByDesc('total_sold')
            ->orderBy('products.nama_produk')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->product_id,
                'name' => $row->name,
                'total_sold' => (int) $row->total_sold,
            ])
            ->values();
    }

   public function expiringSoon(int $userId): Collection
    {
        $today = now()->toDateString();
        $deadline = now()->addDays(45)->toDateString();

        return DetailProduct::query()
            ->select([
                'detail_products.id',
                'detail_products.stok',
                'detail_products.expired',
                'products.nama_produk',
                'products.category_id',
                'products.brand_id',
                'products.sort_order',
            ])
            ->join('products', 'products.id', '=', 'detail_products.product_id')
            ->where('products.user_id', $userId)
            ->whereNull('products.deleted_at')
            ->whereNull('detail_products.deleted_at')
            ->where('detail_products.stok', '>', 0)
            ->whereNotNull('detail_products.expired')
            ->whereDate('detail_products.expired', '>=', $today)
            ->whereDate('detail_products.expired', '<=', $deadline)
            ->orderBy('products.category_id')
            ->orderBy('products.brand_id')
            ->orderBy('products.sort_order')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->nama_produk,
                'stock' => (int) $row->stok,
                'expired_label' => \Carbon\Carbon::parse($row->expired)->format('d/m/Y'),
            ])
            ->values();
    }

    public function criticalStock(int $userId): Collection
    {
        return Product::query()
            ->where('user_id', $userId)
            ->withSum('detailProducts as total_stock_sum', 'stok')
            ->orderBy('category_id')
            ->orderBy('brand_id')
            ->orderBy('sort_order')
            ->get(['id', 'nama_produk'])
            ->map(function (Product $product) {
                $stock = (int) ($product->total_stock_sum ?? 0);

                return [
                    'id' => $product->id,
                    'name' => $product->nama_produk,
                    'stock' => $stock,
                ];
            })
            ->filter(fn (array $item) => $item['stock'] >= 0 && $item['stock'] < 30)
            ->values();
    }
}
