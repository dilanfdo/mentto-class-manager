<?php

namespace App\Models;

use App\Support\Features;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'type', 'status', 'contact_name', 'contact_email', 'contact_phone', 'notes'])]
class Tenant extends Model
{
    use HasFactory;

    public const TYPES = ['tutor', 'institute'];

    public const STATUSES = ['active', 'trial', 'suspended'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(TenantFeature::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return list<string> keys of features this client can use */
    public function enabledFeatures(): array
    {
        $stored = $this->features->pluck('enabled', 'feature');

        return array_values(array_filter(
            Features::keys(),
            fn (string $key) => Features::isLocked($key) || (bool) ($stored[$key] ?? false),
        ));
    }

    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->enabledFeatures(), true);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
