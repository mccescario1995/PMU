<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeatherData;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function index(Request $request)
    {
        $query = WeatherData::query()->orderBy('weather_date');

        if ($request->filled('date')) {
            $query->whereDate('weather_date', $request->input('date'));
        }

        return response()->json(
            $query->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'weather_date' => 'required|date',
            'rainfall_mm' => 'nullable|numeric',
            'wind_speed' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
        ]);

        return response()->json(WeatherData::create($data), 201);
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

        $weather->update($data);

        return response()->json($weather);
    }

    public function destroy(WeatherData $weather)
    {
        $weather->delete();

        return response()->noContent();
    }
}
