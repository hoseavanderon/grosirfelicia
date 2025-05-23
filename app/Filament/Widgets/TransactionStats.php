<?php
namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class TransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Transaksi Hari Ini', Transaction::whereDate('created_at', Carbon::today())->count())
                ->description('💵 Hari ini')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Total Produk', Product::count())
                ->description('📦 Semua Produk')
                ->icon('heroicon-o-archive-box')
                ->color('info'),

            Stat::make('Pelanggan', Customer::count())
                ->description('👥 Terdaftar')
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}
