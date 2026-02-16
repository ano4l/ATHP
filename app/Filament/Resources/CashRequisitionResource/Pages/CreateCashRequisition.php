<?php

namespace App\Filament\Resources\CashRequisitionResource\Pages;

use App\Enums\RequisitionStatus;
use App\Filament\Resources\CashRequisitionResource;
use App\Models\AuditEvent;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRequisition extends CreateRecord
{
    protected static string $resource = CashRequisitionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requester_id'] = auth()->id();
        $data['status'] = RequisitionStatus::DRAFT->value;
        return $data;
    }

    protected function afterCreate(): void
    {
        AuditEvent::log('CashRequisition', $this->record->id, 'CREATED', auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
