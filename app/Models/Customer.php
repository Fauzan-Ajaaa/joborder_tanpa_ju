<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\HasCompany;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'total_orders',
        'total_spent',
        'credit_limit',
        'total_outstanding',
        'credit_status',
        'company_id',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
    ];

    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }

    public static function generateCode(): string
    {
        $maxNumber = self::withTrashed()
            ->where('code', 'like', 'CUST-%')
            ->selectRaw("CAST(SUBSTRING(code, 6) AS UNSIGNED) as number")
            ->pluck('number')
            ->max() ?? 0;
        
        return 'CUST-' . str_pad($maxNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function createNew(array $data): self
    {
        return self::create(array_merge([
            'code' => self::generateCode(),
        ], $data));
    }

    public function incrementOrders(): void
    {
        $this->increment('total_orders');
    }

    public function addSpent(float $amount): void
    {
        $this->increment('total_spent', $amount);
    }

    public function addOutstanding(float $amount): void
    {
        $this->increment('total_outstanding', $amount);
        $this->updateCreditStatus();
    }

    public function reduceOutstanding(float $amount): void
    {
        $this->decrement('total_outstanding', $amount);
        $this->updateCreditStatus();
    }

    public function updateCreditStatus(): void
    {
        if ($this->credit_limit > 0) {
            $usage = ($this->total_outstanding / $this->credit_limit) * 100;
            
            if ($usage >= 100) {
                $this->credit_status = 'blocked';
            } elseif ($usage >= 80) {
                $this->credit_status = 'warning';
            } else {
                $this->credit_status = 'good';
            }
        } else {
            $this->credit_status = 'good';
        }
        
        $this->save();
    }

    public function isCreditLimitExceeded(float $newAmount): bool
    {
        return $this->credit_limit > 0 && 
               ($this->total_outstanding + $newAmount) > $this->credit_limit;
    }

    public function getCreditUsagePercentage(): float
    {
        if ($this->credit_limit <= 0) return 0;
        return ($this->total_outstanding / $this->credit_limit) * 100;
    }
}