<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Product;

class ProductStockService
{
    public function calculateTotalStock(int $productId): int
    {
        return (int) DetailProduct::query()
            ->where('product_id', $productId)
            ->sum('stok');
    }
    /**
     * @param  int|array<int, int>  $productIds
     */
    public function markNeedsStockCheck(int|array $productIds): void
    {
        $ids = is_array($productIds) ? $productIds : [$productIds];

        if ($ids === []) {
            return;
        }

        Product::query()
            ->whereIn('id', $ids)
            ->update([
                'stock_check_status' => Product::STOCK_CHECK_REQUIRED,
            ]);
    }
}
