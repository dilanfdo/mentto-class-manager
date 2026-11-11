<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/** Validation rules that only accept records belonging to the current client. */
class TenantRules
{
    public static function exists(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('tenant_id', app(TenantContext::class)->id());
    }
}
