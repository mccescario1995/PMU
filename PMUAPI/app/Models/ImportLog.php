<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'filename',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'errors',
        'status',
    ];

    protected $casts = [
        'errors' => 'array',
    ];
}
