<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StakeholderType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function stakeholders(): HasMany
    {
        return $this->hasMany(Stakeholder::class, 'stakeholder_type_id');
    }
}
