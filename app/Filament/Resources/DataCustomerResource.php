<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataCustomerResource\Pages;
use App\Models\DataCustomer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DataCustomerResource extends Resource
{
    protected static ?string $model = DataCustomer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('company_name')->required(),
                TextInput::make('country')->required(),
                TextInput::make('company_address')->required(),
                TextInput::make('email'),
                TextInput::make('office_phone'),
                TextInput::make('tujuan'),
                TextInput::make('contact_person'),
                TextInput::make('airport_of_arrival'),
                TextInput::make('mobile_phone'),
            ]),

            Repeater::make('discounts')
                ->relationship('discounts')
                ->schema([
                    TextInput::make('jenis_discount')->label('Jenis Discount')->required(),
                    TextInput::make('discount')->label('Discount')->required(),
                ])
                ->defaultItems(1)
                ->addActionLabel('Add Discount')
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->searchable()->sortable(),
                TextColumn::make('company_address')->limit(30),
                TextColumn::make('office_phone'),
                TextColumn::make('contact_person'),
                TextColumn::make('mobile_phone'),
                TextColumn::make('country'),
                TextColumn::make('email')->limit(40),
                TextColumn::make('tujuan')->limit(30),
                TextColumn::make('airport_of_arrival')->limit(30),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataCustomers::route('/'),
            'create' => Pages\CreateDataCustomer::route('/create'),
            'edit' => Pages\EditDataCustomer::route('/{record}/edit'),
        ];
    }
}
