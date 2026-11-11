<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['class_group_id', 'student_id', 'date', 'present'])]
class AttendanceRecord extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'present' => 'boolean'];
    }
}
