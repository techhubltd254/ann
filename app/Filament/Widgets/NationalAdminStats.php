<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\County;
use App\Models\Marketplace\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class NationalAdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Counties', County::count())
                ->icon('heroicon-o-map'),
            Stat::make('Products', Product::count())
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('Bookings', Booking::count())
                ->icon('heroicon-o-receipt-percent'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('national_admin') ?? false;
    }
}