<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = LeaveRequest::with(['employee:id,name,email,branch', 'decidedBy:id,name'])
            ->latest();

        if (! $user->isAdmin()) {
            $query->where('employee_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $leaves = $query->paginate(25);

        return response()->json($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|in:annual,sick,family_responsibility,study,unpaid,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $days = LeaveRequest::countBusinessDays(
            \Carbon\Carbon::parse($request->start_date),
            \Carbon\Carbon::parse($request->end_date)
        );

        $leave = LeaveRequest::create([
            'employee_id' => $user->id,
            'reason' => $request->reason,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $days,
            'notes' => $request->notes,
            'status' => LeaveStatus::SUBMITTED,
        ]);

        AuditEvent::log('LeaveRequest', $leave->id, 'submitted', $user->id, [
            'reason' => $request->reason,
            'days' => $days,
        ]);

        $admins = User::where('role', UserRole::ADMIN)->get();
        foreach ($admins as $admin) {
            Notification::notify(
                $admin,
                'leave_submitted',
                'New Leave Request',
                "{$user->name} requested {$days} day(s) of {$request->reason} leave.",
                'LeaveRequest',
                $leave->id
            );
        }

        $leave->load('employee:id,name,email,branch');

        return response()->json($leave, 201);
    }

    public function approve(Request $request, LeaveRequest $leave): JsonResponse
    {
        $request->validate(['comment' => 'nullable|string']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $leave->update([
            'status' => LeaveStatus::APPROVED,
            'decided_at' => now(),
            'decided_by_id' => $user->id,
            'decision_comment' => $request->comment,
        ]);

        AuditEvent::log('LeaveRequest', $leave->id, 'approved', $user->id);

        Notification::notify(
            $leave->employee,
            'leave_approved',
            'Leave Approved',
            "Your {$leave->reason?->label()} request ({$leave->days} days) has been approved.",
            'LeaveRequest',
            $leave->id
        );

        return response()->json($leave->fresh()->load('employee:id,name,email,branch'));
    }

    public function deny(Request $request, LeaveRequest $leave): JsonResponse
    {
        $request->validate(['comment' => 'required|string']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $leave->update([
            'status' => LeaveStatus::DENIED,
            'decided_at' => now(),
            'decided_by_id' => $user->id,
            'decision_comment' => $request->comment,
        ]);

        AuditEvent::log('LeaveRequest', $leave->id, 'denied', $user->id, [
            'comment' => $request->comment,
        ]);

        Notification::notify(
            $leave->employee,
            'leave_denied',
            'Leave Denied',
            "Your {$leave->reason?->label()} request was denied: {$request->comment}",
            'LeaveRequest',
            $leave->id
        );

        return response()->json($leave->fresh()->load('employee:id,name,email,branch'));
    }
}
