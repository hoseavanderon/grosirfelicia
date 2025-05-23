<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Forms\Components\HasManyRepeater;
use Filament\Forms\Components\DatePicker;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Kategori')
                    ->options(\App\Models\Category::all()->pluck('category', 'id'))
                    ->required(),

                Select::make('brand_id')
                    ->label('Provider')
                    ->options(\App\Models\Brand::all()->pluck('brand', 'id'))
                    ->required(),

                TextInput::make('nama_produk')
                    ->label('Nama Barang')
                    ->required()
                    ->maxLength(255),

                TextInput::make('harga_jual')
                    ->numeric()
                    ->label('Harga Jual')
                    ->required(),
                
                HasManyRepeater::make('detailProducts')
                    ->label('Stok Produk')
                    ->schema([
                        DatePicker::make('expired')
                            ->required()
                            ->label('Tanggal Expired')
                            ->displayFormat('d-m-Y'),
        
                        TextInput::make('stok')
                            ->required()
                            ->numeric()
                            ->label('Jumlah Stok'),
                    ])
                    ->createItemButtonLabel('Tambah Stok Baru')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('No')
                    ->rowIndex(),
                TextColumn::make('brand.brand')->label('Merk'),
                TextColumn::make('nama_produk')->searchable()->label('Nama'),
                TextColumn::make('harga_jual')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                TextColumn::make('total_stok')
                    ->label('Total Stok')
                    ->getStateUsing(function ($record) {
                        return $record->detailProducts->sum('stok'); // pastikan relasinya 'detailProducts'
                    }),
            ])
            ->filters([
                QueryBuilder::make('user_id') 
                    ->label('User ID')  
                    ->query(fn ($query) => $query->where('user_id', Auth::id())), 
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function applyFilters(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }
}
