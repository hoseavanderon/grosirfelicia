<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_pelanggan')
                    ->label('Nama Pelanggan')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('no_telp')
                    ->label('No. Telepon')
                    ->tel()
                    ->required()
                    ->maxLength(20)
                    ->afterStateUpdated(function ($state, $set) {
                        // Mengubah nomor telepon yang dimulai dengan '08' menjadi '62'
                        if (substr($state, 0, 2) === '08') {
                            // Mengganti '08' dengan '62' untuk format internasional
                            $set('no_telp', '62' . substr($state, 1));
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('No')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('nama_pelanggan')
                    ->label('Nama Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_telp')
                    ->label('No. Telepon'),
            ])
            ->filters([
                QueryBuilder::make('user_id') 
                    ->label('User ID')  
                    ->query(fn ($query) => $query->where('user_id', Auth::id())), 
            ])
            ->actions([
                Tables\Actions\Action::make('Lihat Transaksi')
                    ->label('Lihat Transaksi')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Transaksi ' . $record->nama_pelanggan)
                    ->modalButton('Tutup')
                    ->modalWidth('4xl')
                    ->action(fn () => null) // tidak melakukan apa-apa
                    ->modalContent(function ($record) {
                        return view('filament.customers.customer-transactions-modal', [
                            'customer' => $record,
                            'transactions' => $record->transactions()->with('detailTransactions')->latest()->get(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
