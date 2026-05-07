<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait untuk otomatisasi multi-tenancy berdasarkan company_id.
 * Menggunakan Middleware untuk set context agar menghindari recursion pada Auth::user().
 */
trait HasCompany
{
    /**
     * Boot the trait to add multi-tenancy logic.
     */
    protected static function bootHasCompany()
    {
        // 1. Otomatis isi company_id saat membuat data baru
        static::creating(function ($model) {
            // Ambil dari config yang sudah di-set oleh Middleware atau dari Session
            $companyId = config('app.company_id') ?: session('active_company_id');
            
            if ($companyId && !$model->company_id) {
                $model->company_id = $companyId;
            }
        });

        // 2. Filter Global untuk memisahkan data antar perusahaan
        static::addGlobalScope('company_scope', function (Builder $builder) {
            // Jangan jalankan filter di Console/Migration
            if (app()->runningInConsole()) {
                return;
            }

            // Ambil ID perusahaan dari context yang sudah di-set
            // Kita tidak memanggil auth()->user() di sini untuk menghindari Infinite Recursion
            $companyId = config('app.company_id') ?: session('active_company_id');

            if ($companyId) {
                $builder->where($builder->getQuery()->from . '.company_id', $companyId);
            }
        });
    }

    /**
     * Relationship to the Company model.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
}
