<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDiscount extends Model
{
    protected $fillable = [
        'customer_id',
        'cites_document_id',
        'jenis_discount',
        'discount',
        'product_name',
    ];

    public function customer()
    {
        return $this->belongsTo(DataCustomer::class, 'customer_id');
    }
    
    public function citesDocument()
    {
        return $this->belongsTo(CitesDocument::class, 'cites_document_id');
    }

    protected $table = 'customer_discounts';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
