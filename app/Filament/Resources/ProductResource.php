<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('jenis_coral')
                    ->label('Jenis Coral')
                    ->options([
                        'Hard Coral' => 'Hard Coral',
                        'Substrat' => 'Substrat',
                        'Live Rock' => 'Live Rock',
                    ])
                    ->required()
                    ->placeholder('Pilih Jenis Coral'),

                TextInput::make('nama_latin')
                    ->label('Nama Latin')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Nama Latin ini sudah digunakan. Silakan gunakan nama yang berbeda.',
                    ])
                    ->placeholder('Masukkan Nama Latin'),

                TextInput::make('nama_lokal')
                    ->label('Nama Lokal')
                    ->required()
                    ->placeholder('Masukkan Nama Lokal'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenis_coral')
                    ->label('Jenis Coral')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_latin')
                    ->label('Nama Latin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_lokal')
                    ->label('Nama Lokal')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
