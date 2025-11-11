<?php

namespace App\Filament\Widgets;

use App\Models\Pereaksi;
use App\Models\RestockHistory;
use App\Models\UsageHistory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReagentStatsOverview extends BaseWidget
{
    protected static ?int $sort = 3; // Muncul di urutan teratas di dashboard

    protected function getStats(): array
    {
        // Total Usage: Jumlah reagent unik yang pernah digunakan
        $totalUsageCount = UsageHistory::distinct('kode_reagent')->count();

        // Under Stock: Stok < min_stock dan > 0
        $underStockCount = Pereaksi::whereRaw('Stock < min_stock')
            ->where('Stock', '>', 0)
            ->count();

        // Out of Stock: Stok = 0
        $outOfStockCount = Pereaksi::where('Stock', 0)->count();

        // Expiring in 6 Months: Kadaluarsa dalam 6 bulan
        $expiringCount = RestockHistory::where('expired_date', '<=', now()->addMonths(6))
            ->where('expired_date', '>=', now())
            ->count();

        return [
            Stat::make('Total Usage', $totalUsageCount)
                ->description('Unique reagents used')
                ->descriptionIcon('heroicon-o-beaker')
                ->color('primary')
                ->extraAttributes(['class' => 'cursor-pointer'])
                ->descriptionColor('gray'),
            Stat::make('Expiring in 6 Months', $expiringCount)
                ->description('Reagents nearing expiration')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success')
                ->chart($this->getExpirationChart($expiringCount))
                ->extraAttributes(['class' => 'cursor-pointer']),
                
            Stat::make('Under Stock Reagents', $underStockCount)
                ->description('Reagents below minimum stock')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart($this->getStockChart($underStockCount))
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Out of Stock Reagents', $outOfStockCount)
                ->description('Reagents completely depleted')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getStockChart($outOfStockCount))
                ->extraAttributes(['class' => 'cursor-pointer']),

        ];
    }

    /**
     * Membuat data chart sederhana untuk stok
     */
    protected function getStockChart(int $count): array
    {
        return array_fill(0, 7, rand(0, $count)); // Simulasi data 7 hari
    }

    /**
     * Membuat data chart sederhana untuk kadaluarsa
     */
    protected function getExpirationChart(int $count): array
    {
        return array_fill(0, 6, rand(0, $count)); // Simulasi data 6 bulan
    }
}