<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionRevenue;
use Illuminate\Http\Request;

class TransactionRevenueController extends Controller
{
    public function index()
    {
        return TransactionRevenue::orderBy('report_date')->paginate(request('per_page', 50));
    }

    public function show(string $date)
    {
        return TransactionRevenue::where('report_date', $date)->firstOrFail();
    }
}