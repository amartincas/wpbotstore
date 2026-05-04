<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UnprocessedLeadsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $storeId = Auth::user()->store_id;

        $unprocessedCount = Lead::where('store_id', $storeId)
            ->where('is_processed', false)
            ->count();

        $totalCount = Lead::where('store_id', $storeId)->count();
        $processedCount = $totalCount - $unprocessedCount;

        return [
            Stat::make('Unprocessed Leads', $unprocessedCount)
                ->description('Leads awaiting follow-up')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->url('/admin/leads?tableFilters%5Bis_processed%5D%5Bvalue%5D=false'),

            Stat::make('Processed Leads', $processedCount)
                ->description('Successfully handled leads')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Leads', $totalCount)
                ->description('All leads combined')
                ->descriptionIcon('heroicon-m-document')
                ->color('info'),
        ];
    }
}
