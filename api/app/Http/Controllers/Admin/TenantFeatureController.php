<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Features;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantFeatureController extends Controller
{
    public function update(Request $request, Tenant $tenant, string $feature): JsonResponse
    {
        abort_unless(Features::exists($feature), 404, 'Unknown feature.');
        abort_if(Features::isLocked($feature), 422, 'This feature is part of every account and cannot be switched off.');

        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        $tenant->features()->updateOrCreate(
            ['feature' => $feature],
            ['enabled' => $data['enabled'], 'updated_by' => $request->user()->id],
        );

        return response()->json([
            'feature' => $feature,
            'enabled' => $data['enabled'],
            'features' => $tenant->load('features')->enabledFeatures(),
        ]);
    }
}
