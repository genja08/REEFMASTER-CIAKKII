<?php

namespace App\Filament\Resources\DataCustomerResource\Pages;

use App\Filament\Resources\DataCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ListDataCustomers extends ListRecords
{
    protected static string $resource = DataCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('company_name')->searchable()->sortable(),
            TextColumn::make('company_address')->limit(30),
            TextColumn::make('office_phone'),
            TextColumn::make('contact_person'),
            TextColumn::make('mobile_phone'),
            TextColumn::make('country'),
            TextColumn::make('email')->limit(40),
            TextColumn::make('tujuan')->limit(30),
            TextColumn::make('airport_of_arrival')->limit(30),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}