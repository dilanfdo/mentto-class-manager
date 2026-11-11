<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Features;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $tenants = Tenant::with('features')
            ->withCount(['students' => fn (Builder $q) => $q->where('status', 'active')])
            ->get();

        $adoption = array_fill_keys(Features::keys(), 0);
        $monthly = 0;
        foreach ($tenants as $tenant) {
            $enabled = $tenant->enabledFeatures();
            foreach ($enabled as $key) {
                $adoption[$key]++;
            }
            if ($tenant->status === 'active') {
                $monthly += Features::monthlyEstimate($enabled, $tenant->students_count);
            }
        }

        return response()->json([
            'clients' => [
                'total' => $tenants->count(),
                'by_status' => $tenants->countBy('status'),
                'by_type' => $tenants->countBy('type'),
            ],
            'active_students' => $tenants->where('status', '!=', 'suspended')->sum('students_count'),
            'monthly_estimate' => $monthly,
            'feature_adoption' => collect(Features::all())->map(fn ($f, $key) => [
                'key' => $key,
                'name' => $f['name'],
                'group' => $f['group'],
                'clients' => $adoption[$key],
            ])->values(),
        ]);
    }
}
