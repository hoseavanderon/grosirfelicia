<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAuditService
{
    public function __construct(
        private readonly ProductStockService $productStockService,
    ) {}

    public function loadAuditData(int $userId): array
    {
        $products = Product::query()
            ->with('brand')
            ->withSum('detailProducts as total_stock_sum', 'stok')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('nama_produk')
            ->get();

        $groups = [];

        foreach ($products as $product) {
            $brandId = $product->brand_id ?? 0;
            $brandName = $product->brand?->brand ?? 'Lainnya';
            $isVerified = (int) $product->stock_check_status === Product::STOCK_CHECK_VERIFIED;
            $stock = (int) ($product->total_stock_sum ?? 0);

            if (! isset($groups[$brandId])) {
                $groups[$brandId] = [
                    'provider_id' => $brandId,
                    'provider_name' => $brandName,
                    'products' => [],
                ];
            }

            $groups[$brandId]['products'][] = [
                'product_id' => $product->id,
                'name' => $product->nama_produk,
                'stock' => $stock,
                'stock_check_status' => (int) $product->stock_check_status,
                'check_state' => $isVerified ? 'system' : 'unchecked',
                'pcs' => $stock,
            ];
        }

        $providers = collect($groups)
            ->sortBy('provider_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $group) {
                $group['product_count'] = count($group['products']);

                return $group;
            })
            ->all();

        return [
            'providers' => $providers,
        ];
    }

    /**
     * @param  array<int, array{product_id: int, check_state: string, pcs: int}>  $items
     */
    public function saveProgress(int $userId, array $items): void
    {
        DB::transaction(function () use ($userId, $items) {
            foreach ($items as $item) {
                $product = Product::query()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                $pcs = (int) $item['pcs'];
                $currentStock = $this->productStockService->calculateTotalStock($product->id);

                if ($pcs !== $currentStock) {
                    $this->adjustProductStock($product->id, $pcs);
                }

                if ($item['check_state'] === 'manual') {
                    $product->update([
                        'stock_check_status' => Product::STOCK_CHECK_VERIFIED,
                    ]);
                }
            }
        });
    }

    public function adjustProductStock(int $productId, int $newTotal): void
    {
        $details = DetailProduct::query()
            ->where('product_id', $productId)
            ->orderBy('expired')
            ->lockForUpdate()
            ->get();

        $currentTotal = (int) $details->sum('stok');
        $diff = $newTotal - $currentTotal;

        if ($diff === 0) {
            return;
        }

        if ($diff > 0) {
            $target = $details->last();

            if ($target) {
                $target->increment('stok', $diff);
            } else {
                DetailProduct::create([
                    'product_id' => $productId,
                    'expired' => now()->addYear()->toDateString(),
                    'stok' => $diff,
                ]);
            }
        } else {
            $remaining = abs($diff);

            foreach ($details as $detail) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((int) $detail->stok, $remaining);
                $detail->decrement('stok', $take);
                $remaining -= $take;
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'items' => 'Stok tidak boleh negatif.',
                ]);
            }
        }
    }
}
