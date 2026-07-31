<?php

namespace App\Services;

use App\Models\JejakProduk;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class JejakProdukService
{
    public function __construct(
        private readonly ProductStockService $productStockService,
    ) {}

    public function logKeluar(
        int $userId,
        int $productId,
        int $qty,
        int $transactionId,
        string $nomorNota,
    ): JejakProduk {
        return $this->append($userId, $productId, JejakProduk::TYPE_KELUAR, -abs($qty), $transactionId, $nomorNota);
    }

    public function logMasuk(
        int $userId,
        int $productId,
        int $qty,
        string $nomorNota,
    ): JejakProduk {
        return $this->logMasukEntry($userId, $productId, $qty, $nomorNota);
    }

    public function logMasukEntry(
        int $userId,
        int $productId,
        int $qty,
        ?string $nomorNota = null,
        ?int $stockAfter = null,
    ): JejakProduk {
        return JejakProduk::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'movement_type' => JejakProduk::TYPE_MASUK,
            'qty' => abs($qty),
            'transaction_id' => null,
            'nomor_nota' => $nomorNota,
            'stock_after' => $stockAfter ?? $this->productStockService->calculateTotalStock($productId),
        ]);
    }

    public function logBatal(
        int $userId,
        int $productId,
        int $qty,
        int $transactionId,
        string $nomorNota,
    ): JejakProduk {
        return $this->append($userId, $productId, JejakProduk::TYPE_BATAL, abs($qty), $transactionId, $nomorNota);
    }

    public function logBatalEntry(
        int $userId,
        int $productId,
        int $qty,
        ?string $nomorNota = null,
        ?int $stockAfter = null,
    ): JejakProduk {
        return JejakProduk::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'movement_type' => JejakProduk::TYPE_BATAL,
            'qty' => -abs($qty),
            'transaction_id' => null,
            'nomor_nota' => $nomorNota,
            'stock_after' => $stockAfter ?? $this->productStockService->calculateTotalStock($productId),
        ]);
    }

    public function generateGoodsReceiptNota(): string
    {
        $date = now()->format('Ymd');
        $prefix = "RECV-{$date}-";

        $lastNumber = JejakProduk::query()
            ->where('nomor_nota', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_nota');

        $sequence = 1;

        if ($lastNumber) {
            $sequence = ((int) substr($lastNumber, -3)) + 1;
        }

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{
     *     summary: array{current_stock: int, total_in: int, total_out: int},
     *     entries: Collection<int, array<string, mixed>>
     * }
     */
    public function getTrail(int $userId, int $productId, string $fromDate, string $toDate): array
    {
        $product = Product::query()
            ->where('user_id', $userId)
            ->findOrFail($productId);

        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $entries = JejakProduk::query()
            ->with([
                'transaction:id,customer_id',
                'transaction.customer:id,nama_pelanggan',
            ])
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (JejakProduk $entry) => $this->formatEntry($entry))
            ->values();

        $totalIn = (int) JejakProduk::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('movement_type', JejakProduk::TYPE_MASUK)
            ->whereBetween('created_at', [$from, $to])
            ->sum('qty');

        $totalOut = (int) JejakProduk::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('movement_type', JejakProduk::TYPE_KELUAR)
            ->whereBetween('created_at', [$from, $to])
            ->sum('qty');

        return [
            'summary' => [
                'current_stock' => (int) $product->stok,
                'total_in' => $totalIn,
                'total_out' => abs($totalOut),
            ],
            'entries' => $entries,
        ];
    }

    private function append(
        int $userId,
        int $productId,
        string $movementType,
        int $qty,
        ?int $transactionId,
        string $nomorNota,
    ): JejakProduk {
        return JejakProduk::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'qty' => $qty,
            'transaction_id' => $transactionId,
            'nomor_nota' => $nomorNota,
            'stock_after' => $this->productStockService->calculateTotalStock($productId),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEntry(JejakProduk $entry): array
    {
        $createdAt = $entry->created_at ?? now();

        return [
            'id' => $entry->id,
            'datetime' => $createdAt->toIso8601String(),
            'datetime_label' => $createdAt->translatedFormat('d M Y') . ' • ' . $createdAt->format('H:i'),
            'movement_type' => $entry->movement_type,
            'movement_label' => $this->movementLabel($entry->movement_type),
            'qty' => (int) $entry->qty,
            'qty_label' => $this->formatQtyLabel((int) $entry->qty),
            'nomor_nota' => $entry->nomor_nota,
            'reference_label' => $entry->movement_type === JejakProduk::TYPE_MASUK ? 'Nomor Nota' : 'Nomor Nota',
            'store_name' => $this->resolveStoreName($entry),
            'stock_after' => (int) $entry->stock_after,
        ];
    }

    private function resolveStoreName(JejakProduk $entry): ?string
    {
        // Stock In (incoming goods) never shows a store name.
        if ($entry->movement_type === JejakProduk::TYPE_MASUK) {
            return null;
        }

        // Store Order / Cancelled Store Order are linked to a transaction.
        if (! $entry->transaction_id) {
            return null;
        }

        $storeName = trim((string) ($entry->transaction?->customer?->nama_pelanggan ?? ''));

        return $storeName !== '' ? $storeName : null;
    }

    private function movementLabel(string $movementType): string
    {
        return match ($movementType) {
            JejakProduk::TYPE_KELUAR => 'STOCK OUT',
            JejakProduk::TYPE_MASUK => 'STOCK IN',
            JejakProduk::TYPE_BATAL => 'CANCEL',
            default => strtoupper($movementType),
        };
    }

    private function formatQtyLabel(int $qty): string
    {
        $sign = $qty > 0 ? '+' : '';

        return $sign . $qty . ' pcs';
    }
}
