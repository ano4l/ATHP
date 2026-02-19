<?php

namespace Database\Seeders;

use App\Enums\Branch;
use App\Enums\DeliveryStatus;
use App\Enums\LeaveReason;
use App\Enums\LeaveStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Enums\RequisitionCategory;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
use App\Enums\RequisitionType;
use App\Enums\UserRole;
use App\Models\CashRequisition;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@acetech.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => UserRole::ADMIN,
                'branch' => Branch::SOUTH_AFRICA,
            ]
        );

        // Employee user
        $employee = User::firstOrCreate(
            ['email' => 'employee@acetech.com'],
            [
                'name' => 'Jane Employee',
                'password' => Hash::make('employee123'),
                'role' => UserRole::EMPLOYEE,
                'branch' => Branch::ZAMBIA,
            ]
        );

        // Sample requisitions
        $requisitions = [
            [
                'requester_id' => $employee->id,
                'branch' => Branch::ZAMBIA,
                'requisition_type' => RequisitionType::CASH,
                'project_name' => 'Copperline Client Rollout',
                'project_code' => 'ZM-CL-001',
                'category' => RequisitionCategory::TRAVEL,
                'cost_center' => 'OPS-TRV-01',
                'budget_code' => 'FY26-TRAVEL',
                'requisition_for' => RequisitionFor::CLIENT,
                'client_ref' => 'CLT-001',
                'amount' => 5000.00,
                'currency' => 'ZMW',
                'purpose' => 'Client site visit transport and accommodation for project kickoff meeting',
                'needed_by' => now()->addDays(7),
                'status' => RequisitionStatus::SUBMITTED,
                'submitted_at' => now()->subDay(),
            ],
            [
                'requester_id' => $employee->id,
                'branch' => Branch::ZAMBIA,
                'requisition_type' => RequisitionType::PURCHASE,
                'project_name' => 'Warehouse PPE Refresh',
                'project_code' => 'ZM-WH-042',
                'category' => RequisitionCategory::PROCUREMENT,
                'cost_center' => 'OPS-SAFETY',
                'budget_code' => 'FY26-PROC',
                'requisition_for' => RequisitionFor::ORDER,
                'order_ref' => 'ORD-2024-042',
                'amount' => 12500.00,
                'currency' => 'ZMW',
                'purpose' => 'Purchase of safety equipment for warehouse team — helmets, gloves, boots',
                'needed_by' => now()->addDays(14),
                'status' => RequisitionStatus::DRAFT,
            ],
            [
                'requester_id' => $employee->id,
                'branch' => Branch::ZAMBIA,
                'requisition_type' => RequisitionType::CASH,
                'project_name' => 'Operations Internet Upgrade',
                'project_code' => 'ZM-OPS-NET',
                'category' => RequisitionCategory::OPERATIONS,
                'cost_center' => 'OPS-IT',
                'budget_code' => 'FY26-OPS',
                'requisition_for' => RequisitionFor::SELF,
                'amount' => 3200.00,
                'actual_amount' => 3150.00,
                'currency' => 'ZMW',
                'purpose' => 'Operations internet bandwidth upgrade for central branch coordination',
                'needed_by' => now()->subDays(9),
                'status' => RequisitionStatus::CLOSED,
                'submitted_at' => now()->subDays(12),
                'stage1_approved_at' => now()->subDays(11),
                'stage1_approved_by_id' => $admin->id,
                'stage1_comment' => 'Validated operational need.',
                'decided_at' => now()->subDays(10),
                'decided_by_id' => $admin->id,
                'decision_comment' => 'Approved and processed.',
                'processed_by_id' => $admin->id,
                'processed_at' => now()->subDays(9),
                'payment_method' => PaymentMethod::BANK_TRANSFER,
                'payment_reference' => 'PAY-ZM-8891',
                'payment_date' => now()->subDays(9)->toDateString(),
                'finance_comment' => 'Paid from approved operations budget.',
                'purchase_status' => PurchaseStatus::RECEIVED,
                'delivery_status' => DeliveryStatus::DELIVERED,
                'variance_reason' => 'Supplier discount applied on invoice.',
                'fulfilled_at' => now()->subDays(7),
                'fulfilled_by_id' => $admin->id,
                'fulfilment_notes' => 'Upgrade completed and validated by IT operations.',
                'requester_confirmed_at' => now()->subDays(6),
                'closed_at' => now()->subDays(5),
                'closed_by_id' => $admin->id,
                'closure_comment' => 'All deliverables received and confirmed.',
                'approval_turnaround_hours' => 24,
            ],
        ];

        foreach ($requisitions as $data) {
            CashRequisition::updateOrCreate(
                ['purpose' => $data['purpose']],
                $data
            );
        }

        // Sample leave requests
        $leaves = [
            [
                'employee_id' => $employee->id,
                'reason' => LeaveReason::ANNUAL,
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(14),
                'days' => 5,
                'notes' => 'Family vacation',
                'status' => LeaveStatus::SUBMITTED,
            ],
            [
                'employee_id' => $employee->id,
                'reason' => LeaveReason::SICK,
                'start_date' => now()->subDays(3),
                'end_date' => now()->subDays(2),
                'days' => 2,
                'notes' => 'Doctor appointment and recovery',
                'status' => LeaveStatus::APPROVED,
                'decided_at' => now()->subDays(2),
                'decided_by_id' => $admin->id,
                'decision_comment' => 'Get well soon',
            ],
            [
                'employee_id' => $admin->id,
                'reason' => LeaveReason::STUDY,
                'start_date' => now()->addDays(20),
                'end_date' => now()->addDays(22),
                'days' => 3,
                'notes' => 'Professional certification exam prep',
                'status' => LeaveStatus::SUBMITTED,
            ],
        ];

        foreach ($leaves as $data) {
            LeaveRequest::firstOrCreate(
                [
                    'employee_id' => $data['employee_id'],
                    'start_date' => $data['start_date'],
                    'reason' => $data['reason'] instanceof \BackedEnum ? $data['reason']->value : $data['reason'],
                ],
                $data
            );
        }

        $this->command->info('Seeded users, requisitions, and leave requests.');
        $this->command->info('Admin: admin@acetech.com / admin123');
        $this->command->info('Employee: employee@acetech.com / employee123');
    }
}
