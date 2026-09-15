<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionRevenueFeature;
use Illuminate\Http\Request;

class TransactionRevenueFeatureController extends Controller
{
    public function index()
    {
        return TransactionRevenueFeature::orderBy('report_date')->paginate(request('per_page', 50));
    }

    public function show(string $date)
    {
        return TransactionRevenueFeature::where('report_date', $date)->firstOrFail();
    }
}