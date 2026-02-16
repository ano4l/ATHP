<?php

namespace Database\Seeders;

use App\Enums\Branch;
use App\Enums\LeaveReason;
use App\Enums\LeaveStatus;
use App\Enums\RequisitionFor;
use App\Enums\RequisitionStatus;
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
                'requisition_for' => RequisitionFor::CLIENT,
                'client_ref' => 'CLT-001',
                'amount' => 5000.00,
                'currency' => 'ZMW',
                'purpose' => 'Client site visit transport and accommodation for project kickoff meeting',
                'needed_by' => now()->addDays(7),
                'status' => RequisitionStatus::SUBMITTED,
                'submitted_at' => now(),
            ],
            [
                'requester_id' => $employee->id,
                'branch' => Branch::ZAMBIA,
                'requisition_for' => RequisitionFor::ORDER,
                'order_ref' => 'ORD-2024-042',
                'amount' => 12500.00,
                'currency' => 'ZMW',
                'purpose' => 'Purchase of safety equipment for warehouse team — helmets, gloves, boots',
                'needed_by' => now()->addDays(14),
                'status' => RequisitionStatus::DRAFT,
            ],
            [
                'requester_id' => $admin->id,
                'branch' => Branch::SOUTH_AFRICA,
                'requisition_for' => RequisitionFor::SELF,
                'amount' => 3200.00,
                'currency' => 'ZAR',
                'purpose' => 'Conference registration and travel for annual industry summit',
                'needed_by' => now()->addDays(30),
                'status' => RequisitionStatus::APPROVED,
                'submitted_at' => now()->subDays(5),
                'decided_at' => now()->subDays(3),
                'decided_by_id' => $admin->id,
                'decision_comment' => 'Approved — important for networking',
            ],
        ];

        foreach ($requisitions as $data) {
            CashRequisition::firstOrCreate(
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
                ['employee_id' => $data['employee_id'], 'start_date' => $data['start_date'], 'reason' => $data['reason']],
                $data
            );
        }

        $this->command->info('Seeded users, requisitions, and leave requests.');
        $this->command->info('Admin: admin@acetech.com / admin123');
        $this->command->info('Employee: employee@acetech.com / employee123');
    }
}
