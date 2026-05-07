<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class JobOrderLabor extends Model
{
    use HasCompany;

    protected $fillable = [
        'job_order_id',
        'employee_id',
        'mulai_job_at',
        'selesai_job_at',
        'durasi_menit',
        'durasi_jam',
        'tarif_per_jam',
        'biaya_btkl',
        'keterangan',
        'company_id',
    ];

    protected $casts = [
        'mulai_job_at' => 'datetime',
        'selesai_job_at' => 'datetime',
        'durasi_jam' => 'decimal:2',
        'tarif_per_jam' => 'decimal:4',
        'biaya_btkl' => 'decimal:2',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Hitung biaya BTKL dengan pembulatan standar (2 angka desimal)
     */
    public function calculateBiayaBtkl(): float
    {
        if (!$this->durasi_jam || !$this->tarif_per_jam) {
            return 0;
        }
        
        // Pembulatan standar dari hasil perkalian jam × tarif per jam (2 angka desimal)
        return round($this->durasi_jam * $this->tarif_per_jam, 2);
    }

    /**
     * Update biaya BTKL otomatis
     */
    public function updateBiayaBtkl(): void
    {
        $this->biaya_btkl = $this->calculateBiayaBtkl();
        $this->saveQuietly();
    }
}
