<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function income(Request $request): JsonResponse
    {
        $month = $request->validate(['month' => ['sometimes', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $approved = Payment::where('month', $month)->where('status', 'approved');

        $classes = ClassGroup::with('students:id,name')->get();
        $paid = Payment::where('month', $month)->where('status', 'approved')->get(['student_id', 'class_group_id'])
            ->map(fn ($p) => "$p->student_id:$p->class_group_id")->flip();

        $outstanding = [];
        foreach ($classes as $class) {
            foreach ($class->students as $student) {
                if (! $paid->has("$student->id:$class->id")) {
                    $outstanding[] = ['student_id' => $student->id, 'name' => $student->name, 'class' => $class->name, 'amount' => $class->monthly_fee];
                }
            }
        }

        return response()->json([
            'month' => $month,
            'total' => (int) (clone $approved)->sum('amount'),
            'by_method' => (clone $approved)->selectRaw('method, sum(amount) as total')->groupBy('method')->pluck('total', 'method')->map(fn ($v) => (int) $v),
            'by_class' => $classes->map(fn ($c) => [
                'class' => $c->name,
                'total' => (int) (clone $approved)->where('class_group_id', $c->id)->sum('amount'),
            ]),
            'outstanding' => $outstanding,
            'outstanding_total' => array_sum(array_column($outstanding, 'amount')),
        ]);
    }
}
