<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class Unit extends Model
{
    use HasCompany;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Generate next auto code for unit, e.g. U-0001
     */
    public static function generateCode(): string
    {
        $prefix = 'U-';

        $lastCode = static::where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $nextNumber = 1;
        if ($lastCode) {
            $numericPart = substr($lastCode, strlen($prefix));
            if (ctype_digit($numericPart)) {
                $nextNumber = (int) $numericPart + 1;
            }
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
