<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A class or batch, e.g. "Combined Maths 2027 · Theory". */
#[Fillable(['name', 'subject', 'mode', 'day_of_week', 'start_time', 'monthly_fee', 'meeting_link', 'hall'])]
class ClassGroup extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'monthly_fee' => 'integer'];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')->withPivot('tenant_id')->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
