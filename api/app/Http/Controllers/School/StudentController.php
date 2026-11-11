<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\TenantContext;
use App\Support\TenantRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $month = now()->format('Y-m');

        $students = Student::query()
            ->with('classGroups:id,name')
            ->withExists(['payments as paid_this_month' => fn (Builder $q) => $q->where('month', $month)->where('status', 'approved')])
            ->when($request->query('search'), fn (Builder $q, string $s) => $q->where(
                fn (Builder $q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('parent_phone', 'like', "%$s%"),
            ))
            ->when($request->query('class_id'), fn (Builder $q, $id) => $q->whereRelation('classGroups', 'class_groups.id', $id))
            ->when($request->query('status', 'active'), fn (Builder $q, string $status) => $status === 'all' ? $q : $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $students]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $student = Student::create($data);
        $this->syncClasses($student, $data['class_ids'] ?? []);

        return response()->json(['data' => $student->load('classGroups:id,name')], 201);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json(['data' => $student->load([
            'classGroups:id,name,monthly_fee',
            'payments' => fn ($q) => $q->with('classGroup:id,name')->latest()->limit(24),
        ])]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $data = $this->validated($request, partial: true);
        $student->update($data);
        if (array_key_exists('class_ids', $data)) {
            $this->syncClasses($student, $data['class_ids']);
        }

        return response()->json(['data' => $student->load('classGroups:id,name')]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'parent_name' => ['nullable', 'string', 'max:120'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'grade' => ['nullable', 'string', 'max:40'],
            'status' => ['sometimes', Rule::in(['active', 'left'])],
            'class_ids' => ['sometimes', 'array'],
            'class_ids.*' => ['integer', TenantRules::exists('class_groups')],
        ]);
    }

    private function syncClasses(Student $student, array $classIds): void
    {
        $tenantId = app(TenantContext::class)->id();
        $student->classGroups()->sync(array_fill_keys($classIds, ['tenant_id' => $tenantId]));
    }
}
