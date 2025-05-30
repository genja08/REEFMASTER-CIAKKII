<?php

namespace App\Filament\Resources\CitesDocumentResource\Pages;

use App\Filament\Resources\CitesDocumentResource;
use App\Models\CitesDocument;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCitesDocument extends EditRecord
{
    protected static string $resource = CitesDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, CitesDocument::rules($record->id));
        
        if ($validator->fails()) {
            $this->halt();
            
            return $validator->errors()->first();
        }
        
        $record->update($data);
        
        return $record;
    }
}
