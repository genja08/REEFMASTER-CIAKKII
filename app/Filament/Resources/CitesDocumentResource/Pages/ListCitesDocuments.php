<?php

namespace App\Filament\Resources\CitesDocumentResource\Pages;

use App\Filament\Resources\CitesDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCitesDocuments extends ListRecords
{
    protected static string $resource = CitesDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
