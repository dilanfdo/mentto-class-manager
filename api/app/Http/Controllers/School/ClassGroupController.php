<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassGroupController extends Controller
{
    public function __construct(private TenantContext $context) {}

    public function index(): JsonResponse
    {
        $classes = ClassGroup::withCount('students')->orderBy('day_of_week')->orderBy('start_time')->get();

        return response()->json(['data' => $classes->map(fn (ClassGroup $c) => $this->present($c))]);
    }

    public function store(Request $request): JsonResponse
    {
        $class = ClassGroup::create($this->validated($request));

        return response()->json(['data' => $this->present($class->loadCount('students'))], 201);
    }

    public function show(ClassGroup $class): JsonResponse
    {
        return response()->json(['data' => [
            ...$this->present($class->loadCount('students')),
            'students' => $class->students()->orderBy('name')->get(['students.id', 'name', 'phone']),
        ]]);
    }

    public function update(Request $request, ClassGroup $class): JsonResponse
    {
        $class->update($this->validated($request, partial: true));

        return response()->json(['data' => $this->present($class->loadCount('students'))]);
    }

    public function destroy(ClassGroup $class): JsonResponse
    {
        $class->delete();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $tenant = $this->context->get();

        $data = $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:80'],
            'mode' => [$required, Rule::in(['online', 'physical'])],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'monthly_fee' => [$required, 'integer', 'min:0', 'max:1000000'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'hall' => ['nullable', 'string', 'max:60'],
        ]);

        // Fields for switched-off features are ignored rather than stored.
        if (! $tenant->hasFeature('online_links')) {
            unset($data['meeting_link']);
        }
        if (! $tenant->hasFeature('halls')) {
            unset($data['hall']);
        }

        return $data;
    }

    private function present(ClassGroup $class): array
    {
        $tenant = $this->context->get();
        $data = $class->toArray();
        if (! $tenant->hasFeature('online_links')) {
            unset($data['meeting_link']);
        }
        if (! $tenant->hasFeature('halls')) {
            unset($data['hall']);
        }

        return $data;
    }
}
