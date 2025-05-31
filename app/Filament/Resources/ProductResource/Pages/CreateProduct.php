<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    protected function handleRecordCreation(array $data): Model
    {
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, Product::rules());
        
        if ($validator->fails()) {
            $this->halt();
            
            return $validator->errors()->first();
        }
        
        return static::getModel()::create($data);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
