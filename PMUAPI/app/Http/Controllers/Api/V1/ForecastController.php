<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RevenueForecast;
use Illuminate\Http\Request;

class ForecastController extends Controller
{
    public function index()
    {
        return response()->json(
            RevenueForecast::orderBy('forecast_date')->get()
        );
    }

    public function chart()
    {
        return response()->json(
            RevenueForecast::orderBy('forecast_date')
                ->get(['forecast_date', 'predicted_revenue', 'season'])
        );
    }

    public function show(RevenueForecast $forecast)
    {
        return response()->json($forecast);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'forecast_date' => 'required|date',
            'predicted_revenue' => 'required|numeric|min:0',
            'season' => 'nullable|string',
            'model_version' => 'nullable|string',
        ]);

        $forecast = RevenueForecast::create($data);

        return response()->json($forecast, 201);
    }
}
