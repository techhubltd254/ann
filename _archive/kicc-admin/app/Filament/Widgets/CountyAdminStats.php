<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Marketplace\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CountyAdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $countyId = $user?->county_id;

        return [
            Stat::make('My County Products', Product::where('county_id', $countyId)->count())
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('My County Bookings', Booking::whereHas('exhibition', fn ($q) => $q->where('county_id', $countyId))->count())
                ->icon('heroicon-o-receipt-percent'),
            Stat::make('Revenue (KES)', number_format(
                Booking::whereHas('exhibition', fn ($q) => $q->where('county_id', $countyId))->sum('total'), 2
            ))->icon('heroicon-o-currency-dollar'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('county_admin') ?? false;
    }
}