<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\CashRequisition;
use App\Models\CashRequisitionAttachment;
use App\Models\Notification;
use App\Models\User;
use App\Enums\RequisitionStatus;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequisitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CashRequisition::with(['requester:id,name,email,branch', 'attachments'])
            ->latest();

        if (! $user->isAdmin()) {
            $query->where('requester_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $requisitions = $query->paginate(25);

        return response()->json($requisitions);
    }

    public function show(CashRequisition $requisition): JsonResponse
    {
        $requisition->load([
            'requester:id,name,email,branch',
            'decidedBy:id,name',
            'stage1ApprovedBy:id,name',
            'processedBy:id,name',
            'fulfilledBy:id,name',
            'closedBy:id,name',
            'attachments',
        ]);

        return response()->json($requisition);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'requisition_type' => 'required|in:cash,purchase',
            'project_name' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:10',
            'purpose' => 'required|string',
            'cost_center' => 'nullable|string|max:100',
            'budget_code' => 'nullable|string|max:100',
            'needed_by' => 'required|date|after_or_equal:today',
            'requisition_for' => 'nullable|string',
            'client_ref' => 'nullable|string|max:100',
            'order_ref' => 'nullable|string|max:100',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:' . (config('requisition.attachment_max_mb', 10) * 1024),
        ]);

        $user = $request->user();

        $requisition = CashRequisition::create([
            'requester_id' => $user->id,
            'branch' => $user->branch,
            'requisition_type' => $request->requisition_type,
            'project_name' => $request->project_name,
            'category' => $request->category,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'purpose' => $request->purpose,
            'cost_center' => $request->cost_center,
            'budget_code' => $request->budget_code,
            'needed_by' => $request->needed_by,
            'requisition_for' => $request->requisition_for,
            'client_ref' => $request->client_ref,
            'order_ref' => $request->order_ref,
            'status' => RequisitionStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('requisition-attachments', 'local');
                CashRequisitionAttachment::create([
                    'requisition_id' => $requisition->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'storage_path' => $path,
                    'uploaded_by_id' => $user->id,
                ]);
            }
        }

        AuditEvent::log('CashRequisition', $requisition->id, 'submitted', $user->id, [
            'amount' => $requisition->amount,
            'reference_no' => $requisition->reference_no,
        ]);

        $admins = User::where('role', UserRole::ADMIN)->get();
        foreach ($admins as $admin) {
            Notification::notify(
                $admin,
                'requisition_submitted',
                'New Requisition Submitted',
                "{$user->name} submitted {$requisition->reference_no} for {$requisition->currency} {$requisition->amount}",
                'CashRequisition',
                $requisition->id
            );
        }

        $requisition->load(['requester:id,name,email,branch', 'attachments']);

        return response()->json($requisition, 201);
    }

    public function approve(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate(['comment' => 'nullable|string']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($requisition->requester_id === $user->id) {
            return response()->json(['message' => 'Cannot self-approve'], 403);
        }

        if ($requisition->status === RequisitionStatus::SUBMITTED) {
            if ($requisition->requires_additional_approval) {
                $requisition->update([
                    'status' => RequisitionStatus::STAGE1_APPROVED,
                    'stage1_approved_at' => now(),
                    'stage1_approved_by_id' => $user->id,
                    'stage1_comment' => $request->comment,
                ]);
                AuditEvent::log('CashRequisition', $requisition->id, 'stage1_approved', $user->id);
            } else {
                $hours = $requisition->submitted_at ? (int) now()->diffInHours($requisition->submitted_at) : null;
                $requisition->update([
                    'status' => RequisitionStatus::APPROVED,
                    'decided_at' => now(),
                    'decided_by_id' => $user->id,
                    'decision_comment' => $request->comment,
                    'approval_turnaround_hours' => $hours,
                ]);
                AuditEvent::log('CashRequisition', $requisition->id, 'approved', $user->id);
            }
        } elseif ($requisition->status === RequisitionStatus::STAGE1_APPROVED) {
            $hours = $requisition->submitted_at ? (int) now()->diffInHours($requisition->submitted_at) : null;
            $requisition->update([
                'status' => RequisitionStatus::APPROVED,
                'decided_at' => now(),
                'decided_by_id' => $user->id,
                'decision_comment' => $request->comment,
                'approval_turnaround_hours' => $hours,
            ]);
            AuditEvent::log('CashRequisition', $requisition->id, 'approved', $user->id);
        } else {
            return response()->json(['message' => 'Cannot approve in current status'], 422);
        }

        Notification::notify(
            $requisition->requester,
            'requisition_approved',
            'Requisition Approved',
            "Your requisition {$requisition->reference_no} has been approved.",
            'CashRequisition',
            $requisition->id
        );

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function deny(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate(['comment' => 'required|string']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requisition->update([
            'status' => RequisitionStatus::DENIED,
            'decided_at' => now(),
            'decided_by_id' => $user->id,
            'decision_comment' => $request->comment,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'denied', $user->id, [
            'comment' => $request->comment,
        ]);

        Notification::notify(
            $requisition->requester,
            'requisition_denied',
            'Requisition Denied',
            "Your requisition {$requisition->reference_no} was denied: {$request->comment}",
            'CashRequisition',
            $requisition->id
        );

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function requestModification(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate(['comment' => 'required|string']);
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $requisition->update([
            'status' => RequisitionStatus::MODIFICATION_REQUESTED,
            'decision_comment' => $request->comment,
            'decided_by_id' => $user->id,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'modification_requested', $user->id, [
            'comment' => $request->comment,
        ]);

        Notification::notify(
            $requisition->requester,
            'modification_requested',
            'Modification Requested',
            "Your requisition {$requisition->reference_no} needs modification: {$request->comment}",
            'CashRequisition',
            $requisition->id
        );

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function process(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string',
            'payment_date' => 'required|date',
            'finance_comment' => 'nullable|string',
        ]);

        $user = $request->user();

        $requisition->update([
            'status' => RequisitionStatus::PAID,
            'processed_by_id' => $user->id,
            'processed_at' => now(),
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_date' => $request->payment_date,
            'finance_comment' => $request->finance_comment,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'paid', $user->id);

        Notification::notify(
            $requisition->requester,
            'requisition_paid',
            'Requisition Paid',
            "Your requisition {$requisition->reference_no} has been paid/disbursed.",
            'CashRequisition',
            $requisition->id
        );

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function fulfil(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate([
            'actual_amount' => 'nullable|numeric|min:0',
            'variance_reason' => 'nullable|string',
            'fulfilment_notes' => 'nullable|string',
        ]);

        $user = $request->user();

        $requisition->update([
            'status' => RequisitionStatus::FULFILLED,
            'fulfilled_at' => now(),
            'fulfilled_by_id' => $user->id,
            'actual_amount' => $request->actual_amount ?? $requisition->amount,
            'variance_reason' => $request->variance_reason,
            'fulfilment_notes' => $request->fulfilment_notes,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'fulfilled', $user->id);

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function close(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate(['closure_comment' => 'nullable|string']);
        $user = $request->user();

        $requisition->update([
            'status' => RequisitionStatus::CLOSED,
            'closed_at' => now(),
            'closed_by_id' => $user->id,
            'closure_comment' => $request->closure_comment,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'closed', $user->id);

        return response()->json($requisition->fresh()->load('requester:id,name,email,branch'));
    }

    public function uploadAttachment(Request $request, CashRequisition $requisition): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:' . (config('requisition.attachment_max_mb', 10) * 1024),
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->store('requisition-attachments', 'local');

        $attachment = CashRequisitionAttachment::create([
            'requisition_id' => $requisition->id,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'storage_path' => $path,
            'uploaded_by_id' => $user->id,
        ]);

        AuditEvent::log('CashRequisition', $requisition->id, 'attachment_uploaded', $user->id, [
            'file_name' => $file->getClientOriginalName(),
        ]);

        return response()->json($attachment, 201);
    }
}
