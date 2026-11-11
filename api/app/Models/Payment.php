<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'class_group_id', 'month', 'amount', 'method', 'status', 'approved_by', 'approved_at'])]
class Payment extends Model
{
    use BelongsToTenant;

    public const METHODS = ['bank', 'cash', 'card'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'approved_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }
}
