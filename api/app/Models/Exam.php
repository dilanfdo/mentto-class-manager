<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['class_group_id', 'name', 'held_on', 'max_marks'])]
class Exam extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['held_on' => 'date:Y-m-d', 'max_marks' => 'integer'];
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(ExamMark::class);
    }
}
