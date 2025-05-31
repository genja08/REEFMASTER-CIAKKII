<?php

namespace App\Filament\Resources\AKKIIResource\Pages;

use App\Filament\Resources\AKKIIResource;
use App\Models\AKKII;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAKKII extends CreateRecord
{
    protected static string $resource = AKKIIResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        \Illuminate\Support\Facades\Log::info('CreateAKKII - Form data before fill:', $data);
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        // Log data yang diterima
        \Illuminate\Support\Facades\Log::info('CreateAKKII - Data received:', $data);
        
        // Validasi data menggunakan aturan dari model
        $validator = validator($data, AKKII::rules());
        
        if ($validator->fails()) {
            $this->halt();
            
            \Illuminate\Support\Facades\Log::error('CreateAKKII - Validation failed:', $validator->errors()->toArray());
            
            return $validator->errors()->first();
        }
        
        // Validasi items jika ada
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                // Validasi qty_cites tidak boleh negatif
                if (isset($item['qty_cites']) && $item['qty_cites'] < 0) {
                    $this->halt();
                    $this->notify('error', 'Jumlah kirim tidak boleh kurang dari 0.');
                    return 'Jumlah kirim tidak boleh kurang dari 0.';
                }
                
                // Validasi qty_cites tidak boleh melebihi qty CITES yang tersedia
                if (isset($item['product_id']) && isset($data['cites_document_id']) && isset($item['qty_cites'])) {
                    $citesItem = \App\Models\CitesItem::where('product_id', $item['product_id'])
                        ->where('cites_document_id', $data['cites_document_id'])
                        ->first();
                    
                    if ($citesItem && $item['qty_cites'] > $citesItem->qty_cites) {
                        $this->halt();
                        $this->notify('error', "Jumlah kirim tidak boleh melebihi qty CITES ({$citesItem->qty_cites}).");
                        return "Jumlah kirim tidak boleh melebihi qty CITES ({$citesItem->qty_cites}).";
                    }
                }
            }
        }
        
        // Jika customer_id ada, isi data customer
        if (isset($data['customer_id'])) {
            $customer = \App\Models\DataCustomer::find($data['customer_id']);
            if ($customer) {
                \Illuminate\Support\Facades\Log::info('CreateAKKII - Customer found:', $customer->toArray());
                
                $data['company_address'] = $customer->company_address ?? '';
                $data['country'] = $customer->country ?? '';
                $data['office_phone'] = $customer->office_phone ?? '';
                $data['email'] = $customer->email ?? '';
                $data['contact_person'] = $customer->contact_person ?? '';
                $data['tujuan'] = $customer->tujuan ?? '';
                $data['mobile_phone'] = $customer->mobile_phone ?? '';
                $data['airport_of_arrival'] = $customer->airport_of_arrival ?? '';
            } else {
                \Illuminate\Support\Facades\Log::warning('CreateAKKII - Customer not found with ID: ' . $data['customer_id']);
            }
        }
        
        // Jika cites_document_id ada, isi data CITES
        if (isset($data['cites_document_id'])) {
            $cites = \App\Models\CitesDocument::find($data['cites_document_id']);
            if ($cites) {
                \Illuminate\Support\Facades\Log::info('CreateAKKII - CITES document found:', $cites->toArray());
                
                $data['nomor_cites'] = $cites->nomor ?? '';
                $data['tanggal_terbit'] = $cites->issued_date ?? null;
                $data['tanggal_expired'] = $cites->expired_date ?? null;
                $data['airport_of_arrival'] = $cites->airport_of_arrival ?? '';
            } else {
                \Illuminate\Support\Facades\Log::warning('CreateAKKII - CITES document not found with ID: ' . $data['cites_document_id']);
            }
        }
        
        // Log data setelah dimodifikasi
        \Illuminate\Support\Facades\Log::info('CreateAKKII - Modified data:', $data);
        
        return static::getModel()::create($data);
    }
}
