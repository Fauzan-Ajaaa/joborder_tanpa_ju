<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\CoaAutoGenerateService;
use App\Traits\HasCompany;

class Employee extends Model
{
    use HasCompany;

    protected $fillable = [
        'employee_number',
        'name',
        'email',
        'phone',
        'address',
        'position',
        'department',
        'hire_date',
        'base_salary',
        'status',
        'employee_type',
        'jam_kerja_per_bulan',
        'tarif_per_jam',
        'chart_of_account_id',
        'bdp_btkl_coa_id',
        'company_id',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'base_salary' => 'decimal:2',
        'jam_kerja_per_bulan' => 'decimal:2',
        'tarif_per_jam' => 'decimal:2',
    ];

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    /**
     * Relasi ke BTKL (hanya untuk pegawai dengan employee_type = BTKL)
     */
    public function btkls(): HasMany
    {
        return $this->hasMany(JobOrderLabor::class, 'employee_id');
    }

    /**
     * Relasi ke Chart of Account untuk BTKL spesifik
     */
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * Relasi ke Chart of Account untuk BDP - BTKL spesifik
     */
    public function bdpBtklCoa()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bdp_btkl_coa_id');
    }

    public function getHourlyRateAttribute(): float
    {
        $hours = (float) ($this->jam_kerja_per_bulan ?: 0);
        if ($hours <= 0) {
            return 0.0;
        }

        return (float) $this->base_salary / $hours;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_number)) {
                $employee->employee_number = 'EMP-' . str_pad(static::withoutGlobalScopes()->count() + 1, 4, '0', STR_PAD_LEFT);
            }
            
            // Auto-generate COA berdasarkan employee_type
            if ($employee->employee_type === 'BTKL') {
                // BTKL: Biaya Tenaga Kerja Langsung
                // Format: BTKL-[Posisi] (misal: BTKL-Chef)
                $coaId = CoaAutoGenerateService::createCoaForBtklEmployee($employee);
                if ($coaId) {
                    $employee->chart_of_account_id = $coaId;
                }
                
                // BDP - BTKL: Barang Dalam Proses - BTKL spesifik
                // Format: BDP - BTKL [Posisi] (misal: BDP - BTKL Chef)
                $bdpCoaId = CoaAutoGenerateService::createCoaForBdpBtklEmployee($employee);
                if ($bdpCoaId) {
                    $employee->bdp_btkl_coa_id = $bdpCoaId;
                }
            } elseif ($employee->employee_type === 'BTKTL') {
                // BTKTL: Biaya Tenaga Kerja Tidak Langsung
                // Format: BOP BTKTL - Biaya [Posisi] (misal: BOP BTKTL - Biaya Supervisor)
                $coaId = CoaAutoGenerateService::createCoaForBtktlEmployee($employee);
                if ($coaId) {
                    $employee->chart_of_account_id = $coaId;
                }
            }
        });
    }
}
