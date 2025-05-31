<?php

namespace App\Filament\Resources\DataCustomerResource\Pages;

use App\Filament\Resources\DataCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataCustomer extends EditRecord
{
    protected static string $resource = DataCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
