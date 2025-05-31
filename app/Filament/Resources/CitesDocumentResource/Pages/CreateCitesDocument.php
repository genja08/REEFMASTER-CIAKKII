<?php

namespace App\Filament\Resources\CitesDocumentResource\Pages;

use App\Filament\Resources\CitesDocumentResource;
use App\Models\CitesDocument;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCitesDocument extends CreateRecord
{
    protected static string $resource = CitesDocumentResource::class;
    
    protected function handleRecordCreation(array $data): Model
    {
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, CitesDocument::rules());
        
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
