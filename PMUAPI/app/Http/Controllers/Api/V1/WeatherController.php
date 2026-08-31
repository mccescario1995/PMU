<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeatherData;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    use LogsAudit;

    public function forecast(WeatherService $weather, Request $request)
    {
        $days = (int) $request->input('days', 7);
        $days = max(1, min($days, 8));

        $data = $weather->fetchForecast($days);

        return response()->json([
            'location' => config('services.openweather.default_location'),
            'source' => 'onecall_forecast',
            'forecast' => $data,
        ]);
    }

    public function index(Request $request)
    {
        $query = WeatherData::query()->orderBy('weather_date');

        if ($request->filled('date')) {
            $query->whereDate('weather_date', $request->input('date'));
        }

        if ($request->has('page')) {
            return response()->json($query->paginate(request('per_page', 10)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'weather_date' => 'required|date',
            'rainfall_mm' => 'nullable|numeric',
            'wind_speed' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
        ]);

        $weatherData = WeatherData::create($data);

        $this->logAudit('create', 'weather_data', $weatherData->id, null, $this->modelToArray($weatherData, ['weather_date', 'rainfall_mm', 'wind_speed', 'temperature']));

        return response()->json($weatherData, 201);
    }

    public function show(WeatherData $weather)
    {
        return response()->json($weather);
    }

    public function update(Request $request, WeatherData $weather)
    {
        $data = $request->validate([
            'weather_date' => 'sometimes|required|date',
            'rainfall_mm' => 'nullable|numeric',
            'wind_speed' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
        ]);

        $oldValues = $this->modelToArray($weather, ['weather_date', 'rainfall_mm', 'wind_speed', 'temperature']);

        $weather->update($data);

        $this->logAudit('update', 'weather_data', $weather->id, $oldValues, $this->modelToArray($weather, ['weather_date', 'rainfall_mm', 'wind_speed', 'temperature']));

        return response()->json($weather);
    }

    public function destroy(WeatherData $weather)
    {
        $this->logAudit('delete', 'weather_data', $weather->id, $this->modelToArray($weather, ['weather_date', 'rainfall_mm', 'wind_speed', 'temperature']), null);

        $weather->delete();

        return response()->noContent();
    }
}
