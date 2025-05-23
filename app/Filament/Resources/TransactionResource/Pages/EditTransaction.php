<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\DetailTransaction;
use App\Models\DetailProduct;
use Filament\Notifications\Notification;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        // Kembalikan stok lama
        foreach ($this->record->detailTransactions as $oldDetail) {
            $dp = DetailProduct::find($oldDetail->detail_product_id);
            if ($dp) {
                $dp->increment('stok', $oldDetail->pcs);
            }
        }

        // Soft delete detail lama
        $this->record->detailTransactions()->delete();
    }

    protected function afterSave(): void
    {
        // Tambah detail baru dan update stok
        foreach ($this->data['detailTransactions'] as $detail) {
            $this->record->detailTransactions()->create($detail);

            $dp = DetailProduct::find($detail['detail_product_id']);
            if ($dp) {
                $dp->decrement('stok', $detail['pcs']);
            }
        }

        Notification::make()
            ->title('Transaksi berhasil diperbarui')
            ->success()
            ->send();

        $this->redirect($this->getRedirectUrl());
    }
}
