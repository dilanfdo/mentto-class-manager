<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\TenantRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with(['student:id,name', 'classGroup:id,name'])
            ->when($request->query('month'), fn (Builder $q, string $m) => $q->where('month', $m))
            ->when($request->query('status'), fn (Builder $q, string $s) => $q->where('status', $s))
            ->latest()
            ->limit(500)
            ->get();

        return response()->json(['data' => $payments]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', TenantRules::exists('students')],
            'class_group_id' => ['required', 'integer', TenantRules::exists('class_groups')],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'method' => ['required', Rule::in(Payment::METHODS)],
        ]);

        // Cash taken at the counter is approved straight away; bank slips wait for a check.
        $approved = $data['method'] === 'cash';
        $payment = Payment::create($data + [
            'status' => $approved ? 'approved' : 'pending',
            'approved_by' => $approved ? $request->user()->id : null,
            'approved_at' => $approved ? now() : null,
        ]);

        return response()->json(['data' => $payment->load(['student:id,name', 'classGroup:id,name'])], 201);
    }

    public function approve(Request $request, Payment $payment): JsonResponse
    {
        $payment->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

        return response()->json(['data' => $payment->load(['student:id,name', 'classGroup:id,name'])]);
    }
}
