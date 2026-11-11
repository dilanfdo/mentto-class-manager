<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Creates a client with its owner login and starting features. */
class TenantProvisioner
{
    /**
     * @param  array{name: string, type: string, contact_name: string, contact_email: string, contact_phone?: ?string, status?: string, notes?: ?string}  $data
     */
    public function create(array $data, string $ownerPassword): Tenant
    {
        return DB::transaction(function () use ($data, $ownerPassword) {
            $tenant = Tenant::create($data + ['slug' => $this->uniqueSlug($data['name'])]);

            foreach (Features::defaultsFor($tenant->type) as $key) {
                $tenant->features()->create(['feature' => $key, 'enabled' => true]);
            }

            $tenant->users()->create([
                'name' => $data['contact_name'],
                'email' => $data['contact_email'],
                'password' => $ownerPassword,
                'role' => 'owner',
            ]);

            return $tenant->load('features');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'client';
        $slug = $base;
        for ($i = 2; Tenant::where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
