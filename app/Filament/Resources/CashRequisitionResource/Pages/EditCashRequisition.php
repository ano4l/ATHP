<?php

namespace App\Filament\Resources\CashRequisitionResource\Pages;

use App\Filament\Resources\CashRequisitionResource;
use App\Models\AuditEvent;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashRequisition extends EditRecord
{
    protected static string $resource = CashRequisitionResource::class;

    protected function afterSave(): void
    {
        AuditEvent::log('CashRequisition', $this->record->id, 'UPDATED', auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
