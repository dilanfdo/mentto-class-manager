<?php

namespace App\Support;

use App\Models\Tenant;

/**
 * The client (tenant) the current request acts for. Set by the tenant
 * middleware from the signed-in user; empty for platform admins.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }
}
