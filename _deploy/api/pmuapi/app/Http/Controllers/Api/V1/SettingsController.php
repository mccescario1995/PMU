<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $query = Setting::query();

        if (request()->has('page')) {
            return response()->json($query->paginate(request('per_page', 10)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $setting = Setting::create($data);

        $this->logAudit('create', 'settings', $setting->id, null, $this->modelToArray($setting, ['key', 'value', 'type', 'description']));

        return response()->json($setting, 201);
    }

    public function show(Setting $setting)
    {
        return response()->json($setting);
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $request->validate([
            'key' => 'sometimes|required|string|max:255|unique:settings,key,'.$setting->id,
            'value' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $oldValues = $this->modelToArray($setting, ['key', 'value', 'type', 'description']);

        $setting->update($data);

        $this->logAudit('update', 'settings', $setting->id, $oldValues, $this->modelToArray($setting, ['key', 'value', 'type', 'description']));

        return response()->json($setting);
    }

    public function destroy(Setting $setting)
    {
        $this->logAudit('delete', 'settings', $setting->id, $this->modelToArray($setting, ['key', 'value', 'type', 'description']), null);

        $setting->delete();

        return response()->noContent();
    }
}
