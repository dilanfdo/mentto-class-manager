<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Support\TenantRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Exam::with('classGroup:id,name')->withCount('marks')->latest('held_on')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_group_id' => ['required', 'integer', TenantRules::exists('class_groups')],
            'name' => ['required', 'string', 'max:120'],
            'held_on' => ['required', 'date_format:Y-m-d'],
            'max_marks' => ['required', 'integer', 'between:1,1000'],
            'marks' => ['sometimes', 'array'],
            'marks.*.student_id' => ['required', 'integer', TenantRules::exists('students')],
            'marks.*.marks' => ['required', 'integer', 'min:0', 'lte:max_marks'],
        ]);

        $exam = DB::transaction(function () use ($data) {
            $exam = Exam::create($data);
            foreach ($data['marks'] ?? [] as $mark) {
                $exam->marks()->create($mark);
            }

            return $exam;
        });

        return $this->show($exam)->setStatusCode(201);
    }

    public function show(Exam $exam): JsonResponse
    {
        $marks = $exam->marks()->with('student:id,name')->orderByDesc('marks')->get();

        // Equal marks share a rank: 90, 85, 85, 70 → 1, 2, 2, 4.
        $ranked = [];
        foreach ($marks as $i => $mark) {
            $rank = $i > 0 && $mark->marks === $marks[$i - 1]->marks ? $ranked[$i - 1]['rank'] : $i + 1;
            $ranked[] = ['student_id' => $mark->student_id, 'name' => $mark->student->name, 'marks' => $mark->marks, 'rank' => $rank];
        }

        return response()->json(['data' => [...$exam->load('classGroup:id,name')->toArray(), 'results' => $ranked]]);
    }
}
