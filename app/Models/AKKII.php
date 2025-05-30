<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\DataCustomer;
use App\Models\CitesDocument;

class AKKII extends Model
{
    protected $table = 'akkiis';
    
    protected $fillable = [
        'cites_document_id',
        'customer_id',
        'nomor_cites',
        'company_address',
        'country',
        'office_phone',
        'email',
        'contact_person',
        'tujuan',
        'mobile_phone',
        'airport_of_arrival',
        'tanggal_terbit',
        'tanggal_expired',
        'tanggal_ekspor',
        'no_awb',
        'no_aju',
        'no_pendaftaran',
        'tanggal_pendaftaran',
    ];
    
    /**
     * Get the validation rules that apply to the model.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'cites_document_id' => 'required|exists:cites_documents,id',
            'customer_id' => 'required|exists:data_customers,id',
            'nomor_cites' => [
                'required',
                Rule::unique('akkiis', 'nomor_cites')->ignore($id),
            ],
            'tanggal_terbit' => 'nullable|date',
            'tanggal_expired' => 'nullable|date|after_or_equal:tanggal_terbit',
            'tanggal_ekspor' => 'nullable|date',
            'tanggal_pendaftaran' => 'nullable|date',
        ];
    }
    
    /**
     * Get the items for this AKKII.
     */
    public function items()
    {
        return $this->hasMany(AKKII_Item::class, 'akkii_id');
    }
    
    /**
     * Get the customer that owns this AKKII.
     */
    public function customer()
    {
        return $this->belongsTo(DataCustomer::class, 'customer_id');
    }
    
    /**
     * Get the CITES document that owns this AKKII.
     */
    public function citesDocument()
    {
        return $this->belongsTo(CitesDocument::class, 'cites_document_id');
    }
    
    /**
     * Auto-fill customer data from the selected customer
     */
    public static function boot()
    {
        parent::boot();
        
        // Function to fill customer data
        $fillCustomerData = function ($akkii) {
            if ($akkii->customer_id) {
                $customer = DataCustomer::find($akkii->customer_id);
                if ($customer) {
                    // Tambahkan debug untuk melihat data customer
                    \Illuminate\Support\Facades\Log::info('Model boot - Customer data:', $customer->toArray());
                    
                    $akkii->company_address = $customer->company_address ?? '';
                    $akkii->country = $customer->country ?? '';
                    $akkii->office_phone = $customer->office_phone ?? '';
                    $akkii->email = $customer->email ?? '';
                    $akkii->contact_person = $customer->contact_person ?? '';
                    $akkii->tujuan = $customer->tujuan ?? '';
                    $akkii->mobile_phone = $customer->mobile_phone ?? '';
                    $akkii->airport_of_arrival = $customer->airport_of_arrival ?? '';
                } else {
                    \Illuminate\Support\Facades\Log::warning('Model boot - Customer not found with ID: ' . $akkii->customer_id);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Model boot - No customer ID provided');
            }
        };
        
        // Function to fill CITES document data
        $fillCitesData = function ($akkii) {
            if ($akkii->cites_document_id) {
                $cites = CitesDocument::find($akkii->cites_document_id);
                if ($cites) {
                    // Tambahkan debug untuk melihat data CITES
                    \Illuminate\Support\Facades\Log::info('Model boot - CITES data:', $cites->toArray());
                    
                    $akkii->nomor_cites = $cites->nomor ?? '';
                    $akkii->tanggal_terbit = $cites->issued_date ?? null;
                    $akkii->tanggal_expired = $cites->expired_date ?? null;
                    $akkii->airport_of_arrival = $cites->airport_of_arrival ?? '';
                } else {
                    \Illuminate\Support\Facades\Log::warning('Model boot - CITES document not found with ID: ' . $akkii->cites_document_id);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Model boot - No CITES document ID provided');
            }
        };
        
        // Fill data when creating a new record
        static::creating(function ($akkii) use ($fillCustomerData, $fillCitesData) {
            $fillCustomerData($akkii);
            $fillCitesData($akkii);
            
            // Bersihkan email dari spasi
            if (isset($akkii->email)) {
                $akkii->email = trim($akkii->email);
            }
        });
        
        // Fill data when updating an existing record
        static::updating(function ($akkii) use ($fillCustomerData, $fillCitesData) {
            // Check if customer_id has changed
            if ($akkii->isDirty('customer_id')) {
                $fillCustomerData($akkii);
            }
            
            // Check if cites_document_id has changed
            if ($akkii->isDirty('cites_document_id')) {
                $fillCitesData($akkii);
            }
            
            // Bersihkan email dari spasi
            if (isset($akkii->email)) {
                $akkii->email = trim($akkii->email);
            }
        });
    }
}
