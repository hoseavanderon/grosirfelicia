<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Transaction;

trait FormatsTransactions
{
    protected function formatTransaction(Transaction $transaction): array
    {
        $items = $transaction->detailTransactions->map(function ($detail) {
            $expired = $detail->detailProduct?->expired;
            $productName = $detail->detailProduct?->product?->nama_produk ?? 'Produk';

            return [
                'detail_product_id' => $detail->detail_product_id,
                'product_name' => $productName,
                'expired' => $expired?->format('Y-m-d'),
                'expired_label' => $expired?->translatedFormat('d M Y') ?? '-',
                'qty' => (int) $detail->pcs,
                'unit_price' => (int) $detail->harga_jual,
                'line_total' => (int) ($detail->pcs * $detail->harga_jual),
            ];
        })->values();

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
            'items' => $items,
        ];
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
                'copy_status' => 'Lunas',
            ],
            'tf' => [
                'label' => 'Lunas - Transfer',
                'class' => 'badge-paid-tf',
                'copy_status' => 'Lunas',
            ],
            'setor_bos' => [
                'label' => 'Lunas',
                'class' => 'badge-setor-bos',
                'copy_status' => 'Setor Bos',
            ],
            default => [
                'label' => 'Belum Bayar',
                'class' => 'badge-unpaid',
                'copy_status' => 'Belum Bayar',
            ],
        };
    }
}
