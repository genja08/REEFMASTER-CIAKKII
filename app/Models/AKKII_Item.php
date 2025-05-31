<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AKKII;
use App\Models\CitesItem;
use App\Models\Product;

class AKKII_Item extends Model
{
    protected $table = 'akkii_items';
    
    protected $fillable = [
        'akkii_id',
        'product_id',
        'qty_cites',
        'qty_sisa',
        'qty_realisasi',
        'keterangan',
    ];
    
    /**
     * Get the validation rules that apply to the model.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'akkii_id' => 'required|exists:akkiis,id',
            'product_id' => 'required|exists:products,id',
            'qty_cites' => 'required|integer|min:1',
            'qty_sisa' => 'nullable|integer|min:0',
            'qty_realisasi' => 'nullable|integer|min:0',
        ];
    }
    
    /**
     * Get the AKKII that owns this item.
     */
    public function akkii()
    {
        return $this->belongsTo(AKKII::class, 'akkii_id');
    }
    
    /**
     * Get the product that this item refers to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Auto-fill product data when creating a new item
     */
    protected static function boot()
    {
        parent::boot();
        
        // Function to calculate qty_sisa
        $calculateQtySisa = function ($item) {
            // Cari data qty_cites dari CitesItem
            if ($item->product_id && $item->akkii_id) {
                // Dapatkan AKKII untuk mendapatkan cites_document_id
                $akkii = AKKII::find($item->akkii_id);
                if ($akkii && $akkii->cites_document_id) {
                    // Cari CitesItem yang sesuai dengan product_id dan cites_document_id
                    $citesItem = CitesItem::where('product_id', $item->product_id)
                        ->where('cites_document_id', $akkii->cites_document_id)
                        ->first();
                    
                    if ($citesItem) {
                        // Log untuk debugging
                        \Illuminate\Support\Facades\Log::info('AKKII_Item - CitesItem found:', [
                            'cites_item_id' => $citesItem->id,
                            'product_id' => $citesItem->product_id,
                            'product_name' => $citesItem->product_name,
                            'qty_cites' => $citesItem->qty_cites
                        ]);
                        
                        // Hitung qty_sisa = qty_cites dari CitesItem - jumlah kirim
                        if (!empty($item->qty_cites)) {
                            // Jika qty_realisasi tidak diisi, gunakan qty_cites sebagai nilai default
                            if (empty($item->qty_realisasi)) {
                                $item->qty_realisasi = $item->qty_cites;
                            }
                            
                            $item->qty_sisa = $citesItem->qty_cites - $item->qty_realisasi;
                            
                            // Pastikan qty_sisa tidak negatif
                            if ($item->qty_sisa < 0) {
                                $item->qty_sisa = 0;
                            }
                            
                            \Illuminate\Support\Facades\Log::info('AKKII_Item - Calculated qty_sisa:', [
                                'cites_qty_cites' => $citesItem->qty_cites,
                                'item_qty_cites' => $item->qty_cites,
                                'item_qty_realisasi' => $item->qty_realisasi,
                                'calculated_qty_sisa' => $item->qty_sisa
                            ]);
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('AKKII_Item - CitesItem not found for product_id: ' . $item->product_id . ' and cites_document_id: ' . $akkii->cites_document_id);
                        // Default to 0 if no CitesItem found
                        $item->qty_sisa = 0;
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('AKKII_Item - AKKII not found or has no cites_document_id: ' . ($item->akkii_id ?? 'null'));
                    // Default to 0 if no AKKII or cites_document_id found
                    $item->qty_sisa = 0;
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('AKKII_Item - Missing product_id or akkii_id');
                // Default to 0 if missing product_id or akkii_id
                $item->qty_sisa = 0;
            }
        };
        
        // When creating a new item
        static::creating(function ($item) use ($calculateQtySisa) {
            // Validasi qty_cites tidak boleh negatif
            if ($item->qty_cites < 0) {
                $item->qty_cites = 0;
            }
            
            // Validasi qty_cites tidak boleh melebihi qty CITES yang tersedia
            if ($item->product_id && $item->akkii_id) {
                $akkii = AKKII::find($item->akkii_id);
                if ($akkii && $akkii->cites_document_id) {
                    $citesItem = CitesItem::where('product_id', $item->product_id)
                        ->where('cites_document_id', $akkii->cites_document_id)
                        ->first();
                    
                    if ($citesItem && $item->qty_cites > $citesItem->qty_cites) {
                        $item->qty_cites = $citesItem->qty_cites;
                        \Illuminate\Support\Facades\Log::warning('AKKII_Item - qty_cites adjusted to match available qty in CITES document');
                    }
                }
            }
            
            $calculateQtySisa($item);
        });
        
        // When updating an existing item
        static::updating(function ($item) use ($calculateQtySisa) {
            // Validasi qty_cites tidak boleh negatif
            if ($item->qty_cites < 0) {
                $item->qty_cites = 0;
            }
            
            // Validasi qty_cites tidak boleh melebihi qty CITES yang tersedia
            if ($item->isDirty('qty_cites') || $item->isDirty('product_id')) {
                if ($item->product_id && $item->akkii_id) {
                    $akkii = AKKII::find($item->akkii_id);
                    if ($akkii && $akkii->cites_document_id) {
                        $citesItem = CitesItem::where('product_id', $item->product_id)
                            ->where('cites_document_id', $akkii->cites_document_id)
                            ->first();
                        
                        if ($citesItem && $item->qty_cites > $citesItem->qty_cites) {
                            $item->qty_cites = $citesItem->qty_cites;
                            \Illuminate\Support\Facades\Log::warning('AKKII_Item - qty_cites adjusted to match available qty in CITES document');
                        }
                    }
                }
            }
            
            // Only recalculate if qty_cites, qty_realisasi, or product_id has changed
            if ($item->isDirty('qty_cites') || $item->isDirty('qty_realisasi') || $item->isDirty('product_id')) {
                $calculateQtySisa($item);
            }
        });
    }
}
