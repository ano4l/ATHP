<?php

namespace App\Filament\Resources\CashRequisitionResource\Pages;

use App\Enums\RequisitionStatus;
use App\Filament\Resources\CashRequisitionResource;
use App\Models\AuditEvent;
use App\Models\CashRequisitionAttachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

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
        $attachments = $this->data['new_attachments'] ?? [];

        foreach ($attachments as $path) {
            if (!is_string($path) || blank($path) || !Storage::disk('local')->exists($path)) {
                continue;
            }

            CashRequisitionAttachment::create([
                'requisition_id' => $this->record->id,
                'file_name' => basename($path),
                'file_type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream',
                'file_size' => (int) (Storage::disk('local')->size($path) ?: 0),
                'storage_path' => $path,
                'uploaded_by_id' => auth()->id(),
            ]);
        }

        AuditEvent::log('CashRequisition', $this->record->id, 'CREATED', auth()->id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
