<?php

namespace App\Filament\Resources\AKKIIResource\Pages;

use App\Filament\Resources\AKKIIResource;
use App\Models\AKKII;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAKKII extends EditRecord
{
    protected static string $resource = AKKIIResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        \Illuminate\Support\Facades\Log::info('EditAKKII - Form data before fill:', $data);
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Log data yang diterima
        \Illuminate\Support\Facades\Log::info('EditAKKII - Data received:', $data);
        
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, AKKII::rules($record->id));
        
        if ($validator->fails()) {
            $this->halt();
            
            \Illuminate\Support\Facades\Log::error('EditAKKII - Validation failed:', $validator->errors()->toArray());
            
            return $validator->errors()->first();
        }
        
        // Jika customer_id ada dan berubah, isi data customer
        if (isset($data['customer_id']) && $record->customer_id != $data['customer_id']) {
            $customer = \App\Models\DataCustomer::find($data['customer_id']);
            if ($customer) {
                \Illuminate\Support\Facades\Log::info('EditAKKII - Customer found:', $customer->toArray());
                
                $data['company_address'] = $customer->company_address ?? '';
                $data['country'] = $customer->country ?? '';
                $data['office_phone'] = $customer->office_phone ?? '';
                $data['email'] = $customer->email ?? '';
                $data['contact_person'] = $customer->contact_person ?? '';
                $data['tujuan'] = $customer->tujuan ?? '';
                $data['mobile_phone'] = $customer->mobile_phone ?? '';
                $data['airport_of_arrival'] = $customer->airport_of_arrival ?? '';
            } else {
                \Illuminate\Support\Facades\Log::warning('EditAKKII - Customer not found with ID: ' . $data['customer_id']);
            }
        }
        
        // Jika cites_document_id ada dan berubah, isi data CITES
        if (isset($data['cites_document_id']) && $record->cites_document_id != $data['cites_document_id']) {
            $cites = \App\Models\CitesDocument::find($data['cites_document_id']);
            if ($cites) {
                \Illuminate\Support\Facades\Log::info('EditAKKII - CITES document found:', $cites->toArray());
                
                $data['nomor_cites'] = $cites->nomor ?? '';
                $data['tanggal_terbit'] = $cites->issued_date ?? null;
                $data['tanggal_expired'] = $cites->expired_date ?? null;
                $data['airport_of_arrival'] = $cites->airport_of_arrival ?? '';
            } else {
                \Illuminate\Support\Facades\Log::warning('EditAKKII - CITES document not found with ID: ' . $data['cites_document_id']);
            }
        }
        
        // Log data setelah dimodifikasi
        \Illuminate\Support\Facades\Log::info('EditAKKII - Modified data:', $data);
        
        $record->update($data);
        
        return $record;
    }
}
