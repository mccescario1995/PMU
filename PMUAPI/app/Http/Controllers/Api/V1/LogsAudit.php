<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait LogsAudit
{
    protected function logAudit(string $action, string $tableName, int $recordId, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    protected function modelToArray($model, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            $value = $model->{$field} ?? null;
            if ($value instanceof \DateTimeInterface) {
                $value = $value->toDateString();
            }
            $data[$field] = $value;
        }
        return $data;
    }
}
