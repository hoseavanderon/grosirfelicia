<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class TestWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full'; // Agar tampil full width (opsional)

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('nota')
                    ->label('No. Nota')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Pelanggan'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i'),
                TextColumn::make('sub_total')
                    ->label('Total')
                    ->money('IDR'),
            ]);
    }
}
