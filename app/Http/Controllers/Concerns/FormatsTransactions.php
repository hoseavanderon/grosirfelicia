<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Transaction;
use Illuminate\Support\Collection;

trait FormatsTransactions
{
    protected function formatTransaction(Transaction $transaction): array
    {
        $items = $transaction->detailTransactions->map(function ($detail) {
            $detailProduct = $detail->detailProduct;
            $expired = $detailProduct?->expired;
            $productName = $detailProduct?->product?->nama_produk ?? 'Produk';

            return [
                'detail_product_id' => $detail->detail_product_id,
                'product_id' => $detailProduct?->product_id,
                'product_name' => $productName,
                'expired' => $expired?->format('Y-m-d'),
                'expired_label' => $expired?->translatedFormat('d M Y') ?? '-',
                'qty' => (int) $detail->pcs,
                'unit_price' => (int) $detail->harga_jual,
                'line_total' => (int) ($detail->pcs * $detail->harga_jual),
            ];
        })->values();

        $receiptItems = $this->groupItemsForReceipt($items);
        $amount = (int) $items->sum('line_total');
        $createdAt = $transaction->created_at;
        $payment = $this->paymentMeta($transaction->metode_pembayaran);

        return [
            'id' => $transaction->id,
            'customer_id' => $transaction->customer_id,
            'customer' => $transaction->customer?->nama_pelanggan ?? '-',
            'phone' => $transaction->customer?->no_telp ?? '',
            'amount' => $amount,
            'total_pcs' => (int) $items->sum('qty'),
            'time' => $createdAt->format('H:i'),
            'date_label' => $createdAt->translatedFormat('d F Y'),
            'time_label' => $createdAt->format('H:i'),
            'datetime' => $createdAt->format('Y-m-d H:i:s'),
            'datetime_label' => $createdAt->format('d M Y H:i'),
            'trx' => $transaction->nomor_nota,
            'metode_pembayaran' => $transaction->metode_pembayaran,
            'status' => $payment['copy_status'],
            'status_label' => $payment['label'],
            'status_class' => $payment['class'],
            // Detail/batch-level rows — used by edit mode / stock-aware UI.
            'items' => $items,
            // Product-level rows — used by receipt modal, print, and WhatsApp.
            'receipt_items' => $receiptItems,
        ];
    }

    /**
     * Aggregate detail-transaction rows by product_id for receipt display/print.
     * Quantities and line totals are summed; unit price is preserved when uniform.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function groupItemsForReceipt(Collection $items): Collection
    {
        return $items
            ->groupBy(function (array $item) {
                if (! empty($item['product_id'])) {
                    return 'product-'.$item['product_id'];
                }

                return 'name-'.mb_strtolower((string) ($item['product_name'] ?? 'produk'));
            })
            ->map(function (Collection $group) {
                $qty = (int) $group->sum('qty');
                $lineTotal = (int) $group->sum('line_total');
                $uniquePrices = $group->pluck('unit_price')->unique()->values();
                $unitPrice = $uniquePrices->count() === 1
                    ? (int) $uniquePrices->first()
                    : (int) round($lineTotal / max($qty, 1));

                $first = $group->first();

                return [
                    'product_id' => $first['product_id'] ?? null,
                    'product_name' => $first['product_name'] ?? 'Produk',
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            })
            ->values();
    }

    protected function formatPaymentPayload(Transaction $transaction): array
    {
        $payment = $this->paymentMeta($transaction->metode_pembayaran);

        return [
            'metode_pembayaran' => $transaction->metode_pembayaran,
            'status' => $payment['copy_status'],
            'status_label' => $payment['label'],
            'status_class' => $payment['class'],
        ];
    }

    protected function paymentMeta(?string $metode): array
    {
        return match ($metode) {
            'cash' => [
                'label' => 'Lunas - Cash',
                'class' => 'badge-paid-cash',
                'copy_status' => '✅',
            ],
            'tf' => [
                'label' => 'Lunas - Transfer',
                'class' => 'badge-paid-tf',
                'copy_status' => 'TF ✅',
            ],
            'setor_bos' => [
                'label' => 'Lunas',
                'class' => 'badge-setor-bos',
                'copy_status' => 'Setor Bos',
            ],
            default => [
                'label' => 'Belum Bayar',
                'class' => 'badge-unpaid',
                'copy_status' => '',
            ],
        };
    }
}
