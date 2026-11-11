<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'parent_name', 'parent_phone', 'grade', 'status'])]
class Student extends Model
{
    use BelongsToTenant, HasFactory;

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'enrollments')->withPivot('tenant_id')->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
