<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DetailTransaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
         $userId = Auth::id();

         $todayTransactionCount = Transaction::whereDate('created_at', Carbon::today())
            ->where('user_id', $userId)
            ->count();

        $monthTransactionCount = Transaction::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('user_id', $userId)
            ->count();

        $bulanIniTotalOmzet = DetailTransaction::whereHas('transaction', function ($query) use ($userId) {
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year)
                      ->where('user_id', $userId);
            })
            ->select(DB::raw('SUM(CAST(harga_jual AS UNSIGNED) * CAST(pcs AS UNSIGNED)) as total'))
            ->value('total') ?? 0;

        return [
            Stat::make('Transaksi Hari Ini', $todayTransactionCount)
                ->description('💵 Hari ini')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Transaksi Bulan Ini', $monthTransactionCount)
                ->description('🗓️ Bulan Ini')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Omzet Bulan Ini', number_format($bulanIniTotalOmzet, 0, ',', '.'))
                ->description('💰 Total penjualan bulan ini')
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }
}
