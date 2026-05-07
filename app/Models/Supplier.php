<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCompany;

class Supplier extends Model
{
    use HasCompany;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'status',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Relasi ke RawMaterials
     */
    public function rawMaterials(): HasMany
    {
        return $this->hasMany(RawMaterial::class);
    }

    /**
     * Relasi ke Transactions (pembelian)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Generate kode supplier otomatis
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->code)) {
                $supplier->code = 'SUP-' . date('Ymd') . '-' . str_pad(static::withoutGlobalScopes()->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Scope untuk supplier aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
