<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductCreationService
{
    public function __construct(
        private readonly JejakProdukService $jejakProdukService,
    ) {}

    public function nextSortOrder(int $userId, int $brandId): int
    {
        $maxSortOrder = Product::query()
            ->where('user_id', $userId)
            ->where('brand_id', $brandId)
            ->max('sort_order');

        return ((int) $maxSortOrder) + 1;
    }

    public function finalizeNewProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $this->recordInitialStockMovements($product);
        });
    }

    private function recordInitialStockMovements(Product $product): void
    {
        $product->load([
            'detailProducts' => fn ($query) => $query->orderBy('id'),
        ]);

        $runningStock = 0;

        foreach ($product->detailProducts as $detailProduct) {
            $qty = (int) $detailProduct->stok;

            if ($qty <= 0) {
                continue;
            }

            $runningStock += $qty;

            $this->jejakProdukService->logMasukEntry(
                userId: (int) $product->user_id,
                productId: (int) $product->id,
                qty: $qty,
                nomorNota: null,
                stockAfter: $runningStock,
            );
        }
    }
}
