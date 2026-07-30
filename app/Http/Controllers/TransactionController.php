<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FormatsTransactions;
use App\Models\Customer;
use App\Models\DetailProduct;
use App\Models\DetailTransaction;
use App\Models\Transaction;
use App\Services\JejakProdukService;
use App\Services\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    use FormatsTransactions;

    public function __construct(
        private readonly ProductStockService $productStockService,
        private readonly JejakProdukService $jejakProdukService,
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.detail_product_id' => ['required', 'integer', 'exists:detail_products,id'],
            'items.*.harga_jual' => ['required', 'integer', 'min:0'],
            'items.*.pcs' => ['required', 'integer', 'min:1'],
        ]);

        $userId = Auth::id();

        Customer::query()
            ->where('user_id', $userId)
            ->findOrFail($validated['customer_id']);

        $transaction = DB::transaction(function () use ($validated, $userId) {
            $transaction = Transaction::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => $userId,
                'nomor_nota' => $this->generateNomorNota(),
                'metode_pembayaran' => 'belum_bayar',
            ]);

            foreach ($validated['items'] as $item) {
                $this->deductStock($userId, $item['detail_product_id'], $item['pcs'], $transaction);

                DetailTransaction::create([
                    'transaction_id' => $transaction->id,
                    'detail_product_id' => $item['detail_product_id'],
                    'harga_jual' => $item['harga_jual'],
                    'pcs' => $item['pcs'],
                ]);
            }

            return $transaction;
        });

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'nomor_nota' => $transaction->nomor_nota,
        ]);
    }

    public function detailProducts(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,id'],
        ]);

        $userId = Auth::id();
        $includedIds = [];

        if (! empty($validated['transaction_id'])) {
            $includedIds = DetailTransaction::query()
                ->whereHas(
                    'transaction',
                    fn ($query) => $query
                        ->where('user_id', $userId)
                        ->where('id', $validated['transaction_id']),
                )
                ->pluck('detail_product_id')
                ->all();
        }

        $products = DetailProduct::query()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->where('user_id', $userId))
            ->where(function ($query) use ($includedIds) {
                $query->where('stok', '>', 0);

                if ($includedIds !== []) {
                    $query->orWhereIn('id', $includedIds);
                }
            })
            ->orderBy('product_id')
            ->orderBy('expired')
            ->get()
            ->map(function (DetailProduct $detailProduct) {
                $expired = $detailProduct->expired;

                return [
                    'id' => $detailProduct->id,
                    'product_id' => $detailProduct->product_id,
                    'name' => $detailProduct->product?->nama_produk ?? 'Produk',
                    'expired' => $expired?->format('Y-m-d'),
                    'expired_label' => $expired?->translatedFormat('d M Y') ?? '-',
                    'stock' => (int) $detailProduct->stok,
                    'price' => (int) ($detailProduct->product?->harga_jual ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'products' => $products,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.detail_product_id' => ['required', 'integer', 'exists:detail_products,id'],
            'items.*.harga_jual' => ['required', 'integer', 'min:0'],
            'items.*.pcs' => ['required', 'integer', 'min:1'],
        ]);

        $userId = Auth::id();

        Customer::query()
            ->where('user_id', $userId)
            ->findOrFail($validated['customer_id']);

        $transaction = DB::transaction(function () use ($validated, $userId, $id) {
            $transaction = Transaction::query()
                ->with('detailTransactions')
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->findOrFail($id);

            foreach ($transaction->detailTransactions as $detail) {
                $this->restoreStock($userId, $detail->detail_product_id, (int) $detail->pcs, $transaction);
            }

            $transaction->detailTransactions()->delete();

            foreach ($validated['items'] as $item) {
                $this->deductStock($userId, $item['detail_product_id'], $item['pcs'], $transaction);

                DetailTransaction::create([
                    'transaction_id' => $transaction->id,
                    'detail_product_id' => $item['detail_product_id'],
                    'harga_jual' => $item['harga_jual'],
                    'pcs' => $item['pcs'],
                ]);
            }

            $transaction->update([
                'customer_id' => $validated['customer_id'],
            ]);

            return $transaction->fresh([
                'customer',
                'detailTransactions.detailProduct.product',
            ]);
        });

        return response()->json([
            'success' => true,
            'transaction' => $this->formatTransaction($transaction),
        ]);
    }

    public function destroy(int $id)
    {
        $userId = Auth::id();

        DB::transaction(function () use ($userId, $id) {
            $transaction = Transaction::query()
                ->with('detailTransactions')
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->findOrFail($id);

            foreach ($transaction->detailTransactions as $detail) {
                $this->restoreStock($userId, $detail->detail_product_id, (int) $detail->pcs, $transaction);
            }

            $transaction->detailTransactions()->delete();
            $transaction->delete();
        });

        return response()->json([
            'success' => true,
        ]);
    }

    private function deductStock(int $userId, int $detailProductId, int $pcs, Transaction $transaction): DetailProduct
    {
        $detailProduct = DetailProduct::query()
            ->whereHas('product', fn ($query) => $query->where('user_id', $userId))
            ->lockForUpdate()
            ->findOrFail($detailProductId);

        if ($detailProduct->stok < $pcs) {
            throw ValidationException::withMessages([
                'items' => 'Stok tidak cukup untuk salah satu produk.',
            ]);
        }

        $detailProduct->decrement('stok', $pcs);

        $this->jejakProdukService->logKeluar(
            $userId,
            $detailProduct->product_id,
            $pcs,
            $transaction->id,
            $transaction->nomor_nota,
        );

        $this->productStockService->markNeedsStockCheck($detailProduct->product_id);

        return $detailProduct;
    }

    private function restoreStock(int $userId, int $detailProductId, int $pcs, Transaction $transaction): void
    {
        $detailProduct = DetailProduct::query()
            ->whereHas('product', fn ($query) => $query->where('user_id', $userId))
            ->lockForUpdate()
            ->findOrFail($detailProductId);

        $detailProduct->increment('stok', $pcs);

        $this->jejakProdukService->logBatal(
            $userId,
            $detailProduct->product_id,
            $pcs,
            $transaction->id,
            $transaction->nomor_nota,
        );

        $this->productStockService->markNeedsStockCheck($detailProduct->product_id);
    }

    private function generateNomorNota(): string
    {
        $date = now()->format('Ymd');
        $prefix = "TRX-{$date}-";

        $lastNumber = Transaction::withTrashed()
            ->where('nomor_nota', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('nomor_nota');

        $sequence = 1;

        if ($lastNumber) {
            $sequence = ((int) substr($lastNumber, -4)) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
