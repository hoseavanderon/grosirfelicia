<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Select, Repeater, Card, Hidden, Grid, Placeholder};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Notifications\Notification;
use Filament\Resources\ViewRecord;
use Filament\Resources\Pages\ViewRecord\Concerns\InteractsWithRecord;
use App\Filament\Resources\TransactionResource\Pages\ViewTransaction;
use Filament\Tables\Actions\Action;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(['default' => 3])
                ->schema([

                    // KIRI: Input customer + produk
                    Card::make([
                        Hidden::make('nomor_nota')
                            ->dehydrated(),

                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'nama_pelanggan',
                                modifyQueryUsing: fn ($query) => $query->where('user_id', Auth::id())
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $customer = \App\Models\Customer::find($state);
                                if ($customer) {
                                    $tanggal = now()->format('Ymd');
                                    $count = \App\Models\Transaction::withTrashed()
                                        ->whereDate('created_at', today())
                                        ->count() + 1;
                                    $number = str_pad($count, 4, '0', STR_PAD_LEFT);
                                    $nomorNota = "TRX-{$tanggal}-{$number}";

                                    $set('nomor_nota', $nomorNota);
                                }
                            }),

                            Repeater::make('detailTransactions')
                                ->label('Produk')
                                ->relationship('detailTransactions') 
                                ->schema([
                                    Select::make('detail_product_id')
                                        ->label('Produk')
                                        ->required()
                                        ->columnSpan(3)
                                        ->searchable()
                                        ->reactive()
                                        ->options(function () {
                                            return \App\Models\DetailProduct::with('product')
                                                ->whereHas('product', fn ($query) =>
                                                    $query->where('user_id', Auth::id())
                                                )
                                                ->where('stok', '>', 0)
                                                ->get()
                                                ->mapWithKeys(fn ($dp) => [
                                                    $dp->id => "{$dp->product->nama_produk} - {$dp->stok} pcs - Exp: " . $dp->expired,
                                                ]);
                                        })
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $dp = \App\Models\DetailProduct::with('product')->find($state);
                                            if ($dp) {
                                                $set('harga_jual', $dp->product->harga_jual);
                                                $set('pcs', 1);
                                            }
                                        }),

                                    TextInput::make('pcs')
                                        ->numeric()
                                        ->default(1)
                                        ->reactive()
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('harga_jual')
                                        ->numeric()
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(2),
                                ])
                                ->columns(6)
                                ->defaultItems(1)
                                ->createItemButtonLabel('Tambah Produk')
                                ->reactive()
                                ->live() // ⬅️ ini penting untuk update parent field
                    ])->columnSpan(2),

                    // KANAN: Ringkasan + kolom tersembunyi
                    Card::make([
                        Placeholder::make('Ringkasan Belanja')
                            ->content(fn ($get) =>
                                'Total Item: ' . collect($get('detailTransactions'))
                                    ->sum(fn ($item) => (int) $item['pcs'])
                            ),

                        Placeholder::make('Subtotal')
                            ->content(fn ($get) =>
                                'Rp ' . number_format(
                                    collect($get('detailTransactions'))
                                        ->sum(fn ($item) =>
                                            ((int) $item['pcs']) * ((int) $item['harga_jual'])
                                        ),
                                    0, ',', '.'
                                )
                            ),

                        Select::make('metode_pembayaran')
                            ->default('cash')
                            ->hidden(),

                    ])->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('No')
                    ->state(fn ($record, $livewire) =>
                        ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1)) + $livewire->getTableRecords()->search($record) + 1
                    ),

                Tables\Columns\TextColumn::make('customer.nama_pelanggan')
                    ->label('Toko')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->detailTransactions->sum(function ($detail) {
                            return (int) $detail->harga_jual * (int) $detail->pcs;
                        });
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
            
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (string $state) => $state === 'cash' ? 'green' : 'blue'),
        
            ])
            ->filters([
                    SelectFilter::make('filter_transaksi')
                        ->label('Filter Transaksi')
                        ->options([
                            'all' => 'Semua Transaksi',
                            'today' => 'Transaksi Hari Ini',
                        ])
                        ->query(function ($query, $data) {
                            if (($data ?? null) === 'today') {
                                return $query->whereDate('created_at', today())
                                            ->where('user_id', Auth::id());
                            }

                            return $query->where('user_id', Auth::id());
                        }),
                ])
            ->actions([
                Tables\Actions\Action::make('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Transaksi')
                    ->modalButton('Tutup')
                    ->modalWidth('md')
                    ->form([]) // kosong karena kita hanya menampilkan
                    ->modalContent(fn ($record) => view('filament.transactions.modal-view', [
                        'record' => $record,
                    ])),

                Tables\Actions\Action::make('Kirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->url(fn ($record) => 'https://api.whatsapp.com/send?phone=' . $record->customer->no_telp . '&text=' . urlencode(self::generateWhatsAppMessage($record)))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    protected static function generateWhatsAppMessage($record)
    {
        $message = "Detail Nota :\n";
        $message .= "Nomor Nota: " . ($record->nomor_nota ?? 'N/A') . "\n";
        $message .= "Pelanggan: " . strtoupper($record->customer->nama_pelanggan ?? '-') . "\n";

        $totalPcs = $record->detailTransactions->sum('pcs');
        $message .= "Total Item: $totalPcs pcs\n";

        $message .= "========================\n";

        foreach ($record->detailTransactions as $item) {
            $productName = $item->detailProduct->product->nama_produk;
            $expired = \Carbon\Carbon::parse($item->detailProduct->expired)->format('d/m');
            $pcs = $item->pcs;
            $price = number_format($item->harga_jual, 0, ',', '.');

            $message .= "$productName (Exp: $expired) - $pcs x Rp $price\n";
        }

        $message .= "========================\n";
        $message .= "*Total: Rp " . number_format($record->detailTransactions->sum(fn($i) => $i->pcs * $i->harga_jual), 0, ',', '.') . "*\n";
        $message .= "\nTerima kasih atas pembelian Anda!";

        return $message;
    }


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

}
