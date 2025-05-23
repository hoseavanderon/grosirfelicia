<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action; // ✅ ini yang benar
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\DetailProduct;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    public int $userId;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $detailTransactions = $this->form->getState()['detailTransactions'] ?? [];

        foreach ($detailTransactions as $detail) {
            $detailProduct = DetailProduct::find($detail['detail_product_id']);

            if (! $detailProduct || $detailProduct->stok < $detail['pcs']) {
                Notification::make()
                    ->title('Stok Tidak Cukup')
                    ->danger()
                    ->body("Stok untuk produk tidak mencukupi. Silakan periksa kembali jumlah pembelian.")
                    ->send();

                abort(400, 'Stok tidak cukup.');
            }
        }

        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->record->detailTransactions as $detail) {
            $dp = $detail->detailProduct;

            if ($dp && $dp->stok >= $detail->pcs) {
                $pcs = intval($detail->pcs);
                $dp->stok -= $pcs;
                $dp->save();
            } else {
                Notification::make()
                    ->title('Stok Tidak Cukup')
                    ->danger()
                    ->body("Stok untuk produk {$dp->product->nama_produk} tidak mencukupi. Periksa kembali jumlah yang dibeli.")
                    ->send();

                abort(400, 'Stok tidak cukup untuk produk: ' . $dp->product->nama_produk);
            }
        }
    }

}
