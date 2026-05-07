<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class BillOfMaterial extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'product_id',
        'code',
        'name',
        'description',
        'btkl_rate_per_hour',
        'bop_rate_per_hour',
        'selling_price',
        'is_active',
        'notes',
        'total_cost',
        'company_id',
    ];

    protected $casts = [
        'btkl_rate_per_hour' => 'decimal:2',
        'bop_rate_per_hour' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(BillOfMaterialItem::class);
    }

    public function auxiliaries()
    {
        return $this->hasMany(BillOfMaterialAuxiliary::class);
    }

    public function processes()
    {
        return $this->hasMany(BillOfMaterialProcess::class)->orderBy('sequence_order');
    }

    public function getEffectiveSellingPrice(): float
    {
        // Selalu pakai harga produk sebagai sumber kebenaran (selalu tersinkron)
        $productPrice = (float) ($this->product?->price ?? 0);
        if ($productPrice > 0) {
            return $productPrice;
        }
        // Fallback ke selling_price di BOM jika produk belum di-load atau harganya 0
        return (float) ($this->selling_price ?? 0);
    }

    public function calculateTotalMaterialCost()
    {
        return $this->items->sum('total_cost') + $this->auxiliaries->sum('total_cost');
    }

    public function calculateTotalProcessCost()
    {
        return $this->processes->sum('total_process_cost');
    }

    public function calculateTotalCost()
    {
        return $this->calculateTotalMaterialCost() + $this->calculateTotalProcessCost();
    }

    public function calculateTotalDuration()
    {
        return $this->processes->sum('duration_minutes');
    }

    public static function generateCode()
    {
        $latest = self::orderBy('id', 'desc')->first();
        if (!$latest) {
            return 'BOM-001';
        }
        
        $number = intval(substr($latest->code, -3)) + 1;
        return 'BOM-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
