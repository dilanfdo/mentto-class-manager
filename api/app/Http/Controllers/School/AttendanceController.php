<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassGroup;
use App\Support\TenantRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function show(Request $request, ClassGroup $class): JsonResponse
    {
        $date = $request->validate(['date' => ['required', 'date_format:Y-m-d']])['date'];
        $marked = AttendanceRecord::where('class_group_id', $class->id)->whereDate('date', $date)->pluck('present', 'student_id');

        return response()->json([
            'date' => $date,
            'marked' => $marked->isNotEmpty(),
            'students' => $class->students()->orderBy('name')->get(['students.id', 'name'])->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'present' => $marked->has($s->id) ? (bool) $marked[$s->id] : null,
            ]),
        ]);
    }

    public function store(Request $request, ClassGroup $class): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'records' => ['required', 'array'],
            'records.*.student_id' => ['required', 'integer', TenantRules::exists('students')],
            'records.*.present' => ['required', 'boolean'],
        ]);

        foreach ($data['records'] as $record) {
            AttendanceRecord::updateOrCreate(
                ['class_group_id' => $class->id, 'student_id' => $record['student_id'], 'date' => $data['date']],
                ['present' => $record['present']],
            );
        }

        return $this->show($request->merge(['date' => $data['date']]), $class);
    }
}
