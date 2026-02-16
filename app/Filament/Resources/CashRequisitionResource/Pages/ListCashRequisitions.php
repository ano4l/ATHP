<?php

namespace App\Filament\Resources\CashRequisitionResource\Pages;

use App\Filament\Resources\CashRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashRequisitions extends ListRecords
{
    protected static string $resource = CashRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
