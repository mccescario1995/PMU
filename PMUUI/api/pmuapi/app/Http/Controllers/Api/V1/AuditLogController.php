<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        return response()->json(
            AuditLog::with('user')->latest()->paginate(request('per_page', 20))
        );
    }

    public function show(AuditLog $auditLog)
    {
        return response()->json($auditLog->load('user'));
    }
}
