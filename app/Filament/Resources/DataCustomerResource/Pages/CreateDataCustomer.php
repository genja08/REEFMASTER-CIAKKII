<?php

namespace App\Filament\Resources\DataCustomerResource\Pages;

use App\Filament\Resources\DataCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDataCustomer extends CreateRecord
{
    protected static string $resource = DataCustomerResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
