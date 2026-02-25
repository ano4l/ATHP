<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\CashRequisition;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Enums\RequisitionStatus;
use App\Enums\LeaveStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalRequisitions = CashRequisition::count();
        $pendingApprovals = CashRequisition::whereIn('status', [
            RequisitionStatus::SUBMITTED,
            RequisitionStatus::STAGE1_APPROVED,
        ])->count();
        $pendingLeaves = LeaveRequest::where('status', LeaveStatus::SUBMITTED)->count();

        $avgTurnaround = CashRequisition::whereNotNull('approval_turnaround_hours')
            ->avg('approval_turnaround_hours');

        $unreadNotifications = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'total_requisitions' => $totalRequisitions,
            'pending_approvals' => $pendingApprovals,
            'pending_leaves' => $pendingLeaves,
            'avg_turnaround_hours' => $avgTurnaround ? round($avgTurnaround) : null,
            'unread_notifications' => $unreadNotifications,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markNotificationRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function auditLog(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $events = AuditEvent::with('actor:id,name')
            ->latest()
            ->paginate(50);

        return response()->json($events);
    }

    public function reports(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $byCategory = CashRequisition::selectRaw('category, count(*) as count, sum(amount) as total')
            ->groupBy('category')
            ->get();

        $byBranch = CashRequisition::selectRaw('branch, count(*) as count, sum(amount) as total')
            ->groupBy('branch')
            ->get();

        $byStatus = CashRequisition::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $byMonth = CashRequisition::selectRaw("strftime('%Y-%m', created_at) as month, count(*) as count, sum(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        $totalSpend = CashRequisition::whereIn('status', [
            RequisitionStatus::PAID->value,
            RequisitionStatus::FULFILLED->value,
            RequisitionStatus::CLOSED->value,
        ])->sum('amount');

        $approvalRate = CashRequisition::count() > 0
            ? round(CashRequisition::whereNotIn('status', [RequisitionStatus::DENIED->value])->count() / CashRequisition::count() * 100)
            : 0;

        return response()->json([
            'by_category' => $byCategory,
            'by_branch' => $byBranch,
            'by_status' => $byStatus,
            'by_month' => $byMonth,
            'total_spend' => $totalSpend,
            'approval_rate' => $approvalRate,
            'total_requisitions' => CashRequisition::count(),
        ]);
    }
}
