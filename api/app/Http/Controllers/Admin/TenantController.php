<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Features;
use App\Support\TenantProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** NexusDigitalLabs' view of every client using the system. */
class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->with('features')
            ->withCount(['students' => fn (Builder $q) => $q->where('status', 'active')])
            ->when($request->query('status'), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->query('search'), fn (Builder $q, string $search) => $q->where(
                fn (Builder $q) => $q->where('name', 'like', "%$search%")->orWhere('contact_email', 'like', "%$search%"),
            ))
            ->latest()
            ->get();

        return response()->json(['data' => $tenants->map(fn (Tenant $t) => $this->summary($t))]);
    }

    public function store(Request $request, TenantProvisioner $provisioner): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Tenant::TYPES)],
            'status' => ['sometimes', Rule::in(Tenant::STATUSES)],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $password = $data['owner_password'];
        unset($data['owner_password']);
        $tenant = $provisioner->create($data, $password);

        return response()->json(['data' => $this->detail($tenant)], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json(['data' => $this->detail($tenant)]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', Rule::in(Tenant::TYPES)],
            'status' => ['sometimes', Rule::in(Tenant::STATUSES)],
            'contact_name' => ['sometimes', 'string', 'max:120'],
            'contact_email' => ['sometimes', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return response()->json(['data' => $this->detail($tenant)]);
    }

    private function summary(Tenant $tenant): array
    {
        $enabled = $tenant->enabledFeatures();
        $students = $tenant->students_count ?? $tenant->students()->where('status', 'active')->count();

        return [
            ...$tenant->only(['id', 'name', 'slug', 'type', 'status', 'contact_name', 'contact_email', 'contact_phone', 'created_at']),
            'active_students' => $students,
            'features' => $enabled,
            'addons' => array_values(array_filter($enabled, fn ($k) => Features::all()[$k]['group'] === 'addon')),
            'monthly_estimate' => Features::monthlyEstimate($enabled, $students),
        ];
    }

    private function detail(Tenant $tenant): array
    {
        $tenant->load(['features.updatedBy:id,name', 'users:id,tenant_id,name,email,role,created_at']);
        $stored = $tenant->features->keyBy('feature');

        $features = [];
        foreach (Features::all() as $key => $feature) {
            $row = $stored[$key] ?? null;
            $features[] = [
                'key' => $key,
                'name' => $feature['name'],
                'description' => $feature['description'],
                'group' => $feature['group'],
                'locked' => $feature['locked'],
                'monthly' => $feature['monthly'] ?? 0,
                'per_student' => $feature['per_student'] ?? 0,
                'enabled' => $feature['locked'] || (bool) $row?->enabled,
                'updated_at' => $row?->updated_at,
                'updated_by' => $row?->updatedBy?->name,
            ];
        }

        return [
            ...$this->summary($tenant),
            'notes' => $tenant->notes,
            'feature_list' => $features,
            'users' => $tenant->users,
            'counts' => [
                'classes' => $tenant->classGroups()->count(),
                'collected_this_month' => (int) $tenant->payments()->where('month', now()->format('Y-m'))->where('status', 'approved')->sum('amount'),
            ],
        ];
    }
}
