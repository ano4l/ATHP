<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Filament\Resources\LeaveRequestResource;
use App\Models\AuditEvent;
use App\Models\LeaveRequest;
use App\Models\Notification as AppNotification;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_id'] = auth()->id();
        $data['status'] = LeaveStatus::SUBMITTED->value;

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $data['days'] = LeaveRequest::countBusinessDays(
                \Carbon\Carbon::parse($data['start_date']),
                \Carbon\Carbon::parse($data['end_date'])
            );
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        AuditEvent::log('LeaveRequest', $this->record->id, 'CREATED', auth()->id());

        $admins = User::where('role', UserRole::ADMIN->value)->get();
        foreach ($admins as $admin) {
            AppNotification::notify($admin, 'leave_submitted', 'New Leave Request',
                $this->record->employee->name . ' submitted a ' . $this->record->reason->label() . ' request for ' . $this->record->days . ' day(s)',
                'LeaveRequest', $this->record->id);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
