<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, Product::rules($record->id));
        
        if ($validator->fails()) {
            $this->halt();
            
            return $validator->errors()->first();
        }
        
        $record->update($data);
        
        return $record;
    }
}
