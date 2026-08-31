<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusesController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = Status::query();

        if (request()->has('page')) {
            return response()->json($query->paginate(request('per_page', 10)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'color' => 'required|string|max:255',
        ]);

        $status = Status::create($data);

        $this->logAudit('create', 'statuses', $status->id, null, $this->modelToArray($status, ['name', 'type', 'color']));

        return response()->json($status, 201);
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

        $oldValues = $this->modelToArray($status, ['name', 'type', 'color']);

        $status->update($data);

        $this->logAudit('update', 'statuses', $status->id, $oldValues, $this->modelToArray($status, ['name', 'type', 'color']));

        return response()->json($status);
    }

    public function destroy(Status $status)
    {
        $this->logAudit('delete', 'statuses', $status->id, $this->modelToArray($status, ['name', 'type', 'color']), null);

        $status->delete();

        return response()->noContent();
    }
}
