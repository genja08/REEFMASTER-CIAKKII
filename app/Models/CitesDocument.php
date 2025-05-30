<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CitesDocument extends Model
{
    protected $fillable = [
        'nomor',
        'issued_date',
        'expired_date',
        'airport_of_arrival',
        'customer_id',
    ];
    
    /**
     * Get the validation rules that apply to the model.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'nomor' => [
                'required',
                Rule::unique('cites_documents', 'nomor')->ignore($id),
            ],
            'issued_date' => 'required|date',
            'expired_date' => 'required|date|after_or_equal:issued_date',
            'airport_of_arrival' => 'required|string',
            'customer_id' => 'required|exists:data_customers,id',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\DataCustomer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(CitesItem::class, 'cites_document_id');
    }
}
