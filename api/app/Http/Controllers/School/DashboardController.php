<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $month = now()->format('Y-m');

        return response()->json([
            'active_students' => Student::where('status', 'active')->count(),
            'classes' => ClassGroup::count(),
            'collected_this_month' => (int) Payment::where('month', $month)->where('status', 'approved')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'today' => ClassGroup::where('day_of_week', now()->dayOfWeekIso)
                ->withCount('students')
                ->orderBy('start_time')
                ->get(['id', 'name', 'start_time', 'mode', 'hall']),
        ]);
    }
}
