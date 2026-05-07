<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SalesTransaction;
use App\Models\Transaction;
use App\Models\Payroll;
use App\Models\BiayaOverhead;
use App\Models\Penggajian;
use App\Observers\SalesTransactionObserver;
use App\Observers\TransactionObserver;
use App\Observers\PayrollObserver;
use App\Observers\BiayaOverheadObserver;
use App\Observers\PenggajianObserver;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers untuk auto-generate jurnal
        SalesTransaction::observe(SalesTransactionObserver::class);
        Transaction::observe(TransactionObserver::class);
        Payroll::observe(PayrollObserver::class);
        BiayaOverhead::observe(BiayaOverheadObserver::class);
        Penggajian::observe(PenggajianObserver::class);

        // Share company ke semua view catalog
        \Illuminate\Support\Facades\View::composer(['catalog.*', 'layouts.catalog'], function ($view) {
            $user = \App\Models\User::first();
            $company = \App\Models\Company::first();
            $view->with('company', $company);
            $view->with('companyContact', $user);
        });

        // Morph map: hubungkan source_type -> model class
        Relation::enforceMorphMap([
            'sales' => \App\Models\SalesTransaction::class,
            'purchase' => \App\Models\Transaction::class,
            'payroll' => \App\Models\Payroll::class,
            'overhead' => \App\Models\BiayaOverhead::class,
            'penggajian' => \App\Models\Penggajian::class,
            'penggajian_payment' => \App\Models\Penggajian::class,
        ]);
    }
}
