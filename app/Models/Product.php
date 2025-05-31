<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\AKKII_Item;
use App\Models\CitesItem;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'jenis_coral',
        'nama_latin',
        'nama_lokal',
        'jenis_produk',
    ];
    
    /**
     * Get the validation rules that apply to the model.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'jenis_coral' => 'required|string',
            'nama_latin' => [
                'required',
                'string',
                Rule::unique('products', 'nama_latin')->ignore($id),
            ],
            'nama_lokal' => 'required|string',
        ];
    }

    public function citesItems()
    {
        // Jika sudah pakai product_id di cites_items
        return $this->hasMany(CitesItem::class, 'product_id', 'id');
    }
    
    public function akkiiItems()
    {
        return $this->hasMany(AKKII_Item::class, 'product_id', 'id');
    }

    protected $table = 'products';
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
