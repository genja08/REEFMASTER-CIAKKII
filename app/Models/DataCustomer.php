<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataCustomer extends Model
{
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Bersihkan email dari spasi sebelum disimpan
        static::saving(function ($customer) {
            if (isset($customer->email)) {
                $customer->email = trim($customer->email);
            }
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_name',
        'company_address',
        'office_phone',
        'contact_person',
        'mobile_phone',
        'country',
        'email',
        'tujuan',
        'airport_of_arrival',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the discounts for the customer.
     */
    public function discounts()
    {
        return $this->hasMany(CustomerDiscount::class, 'customer_id');
    }
}
