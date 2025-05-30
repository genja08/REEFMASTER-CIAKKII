<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitesItem extends Model
{
    protected $fillable = [
        'cites_document_id',
        'product_id', // Added product_id
        'product_name',
        'qty_cites',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($citesItem) {
            if (empty($citesItem->product_name) && !empty($citesItem->product_id)) {
                $product = Product::find($citesItem->product_id);
                if ($product) {
                    $citesItem->product_name = $product->nama_latin;
                }
            }
        });
    }

    public function citesDocument()
    {
        return $this->belongsTo(CitesDocument::class, 'cites_document_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

