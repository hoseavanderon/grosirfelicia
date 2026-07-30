<?php

namespace App\Services;

use App\Models\BarangMasukLog;
use App\Models\DetailProduct;
use App\Models\JejakProduk;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncomingGoodsService
{
    public function __construct(
        private readonly JejakProdukService $jejakProdukService,
        private readonly ProductStockService $productStockService,
    ) {}

    /**
     * @param  array<int, array{product_id: int, expired: string, quantity: int}>  $rows
     * @return Collection<int, BarangMasukLog>
     */
    public function process(int $userId, array $rows): Collection
    {
        $tanggalMasuk = now()->toDateString();

        return DB::transaction(function () use ($userId, $rows, $tanggalMasuk) {
            $logs = collect();

            foreach ($rows as $row) {
                $product = Product::query()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->findOrFail($row['product_id']);

                $detailProduct = DetailProduct::query()
                    ->where('product_id', $product->id)
                    ->whereDate('expired', $row['expired'])
                    ->lockForUpdate()
                    ->first();

                if ($detailProduct) {
                    $detailProduct->increment('stok', $row['quantity']);
                    $detailProduct->refresh();
                } else {
                    $detailProduct = DetailProduct::create([
                        'product_id' => $product->id,
                        'expired' => $row['expired'],
                        'stok' => $row['quantity'],
                    ]);
                }

                $nomorNota = $this->jejakProdukService->generateGoodsReceiptNota();

                $this->jejakProdukService->logMasuk(
                    $userId,
                    $product->id,
                    $row['quantity'],
                    $nomorNota,
                );

                $this->productStockService->markNeedsStockCheck($product->id);

                $logs->push(
                    BarangMasukLog::create([
                        'detail_product_id' => $detailProduct->id,
                        'jumlah_masuk' => $row['quantity'],
                        'nomor_nota' => $nomorNota,
                        'tanggal_masuk' => $tanggalMasuk,
                        'user_id' => $userId,
                    ]),
                );
            }

            return $logs;
        });
    }

    public function deleteLog(int $userId, int $logId): array
    {
        return DB::transaction(function () use ($userId, $logId) {
            $log = BarangMasukLog::query()
                ->with(['detailProduct'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->findOrFail($logId);

            $detailProduct = DetailProduct::query()
                ->where('id', $log->detail_product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStock = $detailProduct->stok - $log->jumlah_masuk;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'item' => 'Stok tidak cukup untuk membatalkan barang masuk ini.',
                ]);
            }

            $detailProduct->update(['stok' => $newStock]);

            $nomorNota = $log->nomor_nota ?? JejakProduk::query()
                ->where('user_id', $userId)
                ->where('product_id', $detailProduct->product_id)
                ->where('movement_type', JejakProduk::TYPE_MASUK)
                ->where('qty', $log->jumlah_masuk)
                ->whereDate('created_at', $log->tanggal_masuk)
                ->orderByDesc('id')
                ->value('nomor_nota');

            $this->jejakProdukService->logBatalEntry(
                $userId,
                (int) $detailProduct->product_id,
                (int) $log->jumlah_masuk,
                $nomorNota,
            );

            $this->productStockService->markNeedsStockCheck((int) $detailProduct->product_id);

            $dateKey = $log->tanggal_masuk->toDateString();

            $log->delete();

            $remaining = BarangMasukLog::query()
                ->where('user_id', $userId)
                ->whereDate('tanggal_masuk', $dateKey)
                ->count();

            return [
                'date_key' => $dateKey,
                'remaining_count' => $remaining,
            ];
        });
    }

    public function formatExpired(?Carbon $date): string
    {
        return $date?->format('d/m/Y') ?? '-';
    }
}
