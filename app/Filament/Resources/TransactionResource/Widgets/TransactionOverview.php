<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TransactionOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $todayTotal = Transaction::whereDate('created_at', today())
            ->where('user_id', $userId)
            ->count();

        $monthTotal = Transaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('user_id', $userId)
            ->count();

        $monthRevenue = Transaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('user_id', $userId)
            ->sum('sub_total');

        return [
            Stat::make('Transaksi Hari Ini', $todayTotal),
            Stat::make('Transaksi Bulan Ini', $monthTotal),
            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format($monthRevenue, 0, ',', '.')),
        ];
    }
}
