<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RevenueForecast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryPlanningController extends Controller
{
    public function index()
    {
        $forecasts = RevenueForecast::orderBy('forecast_date')->get();

        $peakForecasts = $forecasts->filter(fn ($f) => $this->isPeakSeason($f->forecast_date));
        $offPeakForecasts = $forecasts->filter(fn ($f) => $this->isOffPeakSeason($f->forecast_date));

        $items = InventoryItem::orderBy('item_name')->get();

        $byCategoryType = $items->groupBy('category_type')->map(function ($group) {
            return [
                'total_items' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
                'low_stock_count' => $group->whereIn('status', ['low_stock', 'damaged'])->count(),
            ];
        });

        $totalPeakRevenue = $peakForecasts->sum('predicted_revenue');
        $totalOffPeakRevenue = $offPeakForecasts->sum('predicted_revenue');

        $inventoryValue = $items->sum(function ($item) {
            return $item->quantity;
        });

        $recommendedStock = $this->calculateRecommendedStock($items, $forecasts);

        return response()->json([
            'peak_season' => [
                'label' => 'January – June',
                'forecast_count' => $peakForecasts->count(),
                'total_revenue' => (float) $totalPeakRevenue,
                'avg_revenue' => $peakForecasts->count() > 0 ? (float) ($totalPeakRevenue / $peakForecasts->count()) : 0,
            ],
            'off_peak_season' => [
                'label' => 'July – December',
                'forecast_count' => $offPeakForecasts->count(),
                'total_revenue' => (float) $totalOffPeakRevenue,
                'avg_revenue' => $offPeakForecasts->count() > 0 ? (float) ($totalOffPeakRevenue / $offPeakForecasts->count()) : 0,
            ],
            'inventory_summary' => [
                'total_items' => $items->count(),
                'total_quantity' => (int) $inventoryValue,
                'by_category_type' => $byCategoryType,
                'low_stock_items' => $items->whereIn('status', ['low_stock', 'damaged'])->count(),
            ],
            'recommended_stock' => $recommendedStock,
            'budget_guidance' => $this->generateBudgetGuidance($items, $forecasts),
        ]);
    }

    public function planningView()
    {
        $items = InventoryItem::orderBy('category_type')->orderBy('item_name')->get();
        $forecasts = RevenueForecast::orderBy('forecast_date')->get();

        $lowStockItems = $items->whereIn('status', ['low_stock', 'damaged']);

        $forecastByMonth = [];
        foreach ($forecasts as $f) {
            $month = (int) $f->forecast_date->format('n');
            $forecastByMonth[$month] = [
                'date' => $f->forecast_date->format('Y-m'),
                'revenue' => (float) $f->predicted_revenue,
                'season' => $f->season,
            ];
        }

        return response()->json([
            'items' => $items,
            'forecasts' => $forecasts,
            'low_stock_items' => $lowStockItems,
            'forecast_by_month' => $forecastByMonth,
            'peak_months' => range(1, 6),
            'off_peak_months' => range(7, 12),
        ]);
    }

    private function isPeakSeason($date): bool
    {
        $month = (int) $date->format('n');
        return $month >= 1 && $month <= 6;
    }

    private function isOffPeakSeason($date): bool
    {
        $month = (int) $date->format('n');
        return $month >= 7 && $month <= 12;
    }

    private function calculateRecommendedStock($items, $forecasts): array
    {
        $recommended = [];
        $peakRevenue = $forecasts->filter(fn ($f) => $this->isPeakSeason($f->forecast_date))->sum('predicted_revenue');
        $offPeakRevenue = $forecasts->filter(fn ($f) => $this->isOffPeakSeason($f->forecast_date))->sum('predicted_revenue');
        $totalRevenue = $peakRevenue + $offPeakRevenue;

        foreach ($items as $item) {
            $ratio = $totalRevenue > 0 ? ($item->quantity / $totalRevenue) : 0;
            $seasonFactor = $ratio > 0.01 ? 1.2 : 0.8;

            $recommended[] = [
                'item_id' => $item->id,
                'item_name' => $item->item_name,
                'current_quantity' => $item->quantity,
                'category_type' => $item->category_type,
                'status' => $item->status,
                'recommended_min' => max(1, (int) ($item->quantity * $seasonFactor)),
                'needs_reorder' => $item->quantity <= 10,
            ];
        }

        return $recommended;
    }

    private function generateBudgetGuidance($items, $forecasts): array
    {
        $peakRevenue = $forecasts->filter(fn ($f) => $this->isPeakSeason($f->forecast_date))->sum('predicted_revenue');
        $offPeakRevenue = $forecasts->filter(fn ($f) => $this->isOffPeakSeason($f->forecast_date))->sum('predicted_revenue');

        $equipmentTotal = $items->where('category_type', 'equipment')->sum('quantity');
        $materialsTotal = $items->where('category_type', 'materials')->sum('quantity');
        $suppliesTotal = $items->where('category_type', 'supplies')->sum('quantity');

        return [
            'peak_budget_allocation' => [
                'equipment' => (int) ($peakRevenue * 0.4),
                'materials' => (int) ($peakRevenue * 0.35),
                'supplies' => (int) ($peakRevenue * 0.25),
            ],
            'off_peak_budget_allocation' => [
                'equipment' => (int) ($offPeakRevenue * 0.3),
                'materials' => (int) ($offPeakRevenue * 0.4),
                'supplies' => (int) ($offPeakRevenue * 0.3),
            ],
            'current_stock_levels' => [
                'equipment' => (int) $equipmentTotal,
                'materials' => (int) $materialsTotal,
                'supplies' => (int) $suppliesTotal,
            ],
            'peak_revenue' => (float) $peakRevenue,
            'off_peak_revenue' => (float) $offPeakRevenue,
        ];
    }
}