<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RevenueForecastLinearRegression extends Model
{
    use HasFactory;

    protected $table = 'revenue_forecasts_linear_regression';

    protected $fillable = [
        'forecast_date',
        'predicted_revenue',
        'season',
        'model_version',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_revenue' => 'decimal:2',
    ];

    /**
     * Compute data-driven peak months from revenue history.
     * A month is "Peak" if its average revenue is above the annual average.
     * Results are cached for 1 hour to avoid repeated queries.
     */
    public static function computePeakMonths(): array
    {
        return Cache::remember('peak_months_linear', 3600, function () {
            $monthlyAvg = \App\Models\RevenueHistory::selectRaw('MONTH(revenue_date) as month, AVG(total_revenue) as avg_rev')
                ->groupBy('month')
                ->pluck('avg_rev', 'month')
                ->toArray();

            if (empty($monthlyAvg)) {
                return [1, 2, 3, 4, 5, 6]; // fallback to old default
            }

            $annualAvg = array_sum($monthlyAvg) / count($monthlyAvg);

            $peak = [];
            for ($m = 1; $m <= 12; $m++) {
                if (isset($monthlyAvg[$m]) && $monthlyAvg[$m] > $annualAvg) {
                    $peak[] = $m;
                }
            }

            return $peak;
        });
    }

    /**
     * Fallback accessor: derive season from date using data-driven peak months.
     * Only used when the 'season' column is null.
     */
    public function getSeasonFromDateAttribute(): string
    {
        $month = (int) $this->forecast_date->format('n');

        return in_array($month, $this->computePeakMonths()) ? 'Peak' : 'Off-Peak';
    }
}