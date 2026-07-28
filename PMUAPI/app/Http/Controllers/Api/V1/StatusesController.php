<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusesController extends Controller
{
    public function index()
    {
        return response()->json(Status::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'color' => 'required|string|max:255',
        ]);

        return response()->json(Status::create($data), 201);
    }

    public function show(Status $status)
    {
        return response()->json($status);
    }

    public function update(Request $request, Status $status)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:255',
        ]);

        $status->update($data);

        return response()->json($status);
    }

    public function destroy(Status $status)
    {
        $status->delete();

        return response()->noContent();
    }
}
