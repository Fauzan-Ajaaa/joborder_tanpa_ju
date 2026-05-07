<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

use App\Models\JobOrderItem;

use App\Traits\HasCompany;

class JobOrder extends Model
{
    use HasCompany;

    protected $fillable = [
        'kode_job',
        'order_date',
        'product_id',
        'quantity',
        'start_date',
        'end_date',
        'status',
        'customer_id',
        'product_name',
        'customer_name',
        'notes',
        'company_id',
        'mulai_job_at',
        'selesai_job_at',
        'durasi_menit',
        'durasi_jam',
        'por_id',
        'total_bbb',
        'total_btkl',
        'total_bop',
        'total_hpp',
        // Catalog fields
        'customer_phone',
        'customer_address',
        'payment_method',
        'payment_proof',
        'paid_at',
        'total_cost',
        'material_cost',
        'cash_amount',
        // Snapshot columns
        'snapshot_bbb',
        'snapshot_btkl',
        'snapshot_bop',
        'snapshot_hpp',
        'snapshot_btkl_rate',
        'snapshot_at',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'mulai_job_at' => 'datetime',
        'selesai_job_at' => 'datetime',
        'paid_at' => 'datetime',
        'quantity' => 'decimal:4',
        'durasi_jam' => 'decimal:2',
        'total_bbb' => 'decimal:2',
        'total_btkl' => 'decimal:2',
        'total_bop' => 'decimal:2',
        'total_hpp' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'snapshot_bbb' => 'decimal:2',
        'snapshot_btkl' => 'decimal:2',
        'snapshot_bop' => 'decimal:2',
        'snapshot_hpp' => 'decimal:2',
        'snapshot_btkl_rate' => 'decimal:4',
        'snapshot_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JobOrderItem::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(JobOrderMaterial::class);
    }

    public function labors(): HasMany
    {
        return $this->hasMany(JobOrderLabor::class);
    }

    public function por(): BelongsTo
    {
        return $this->belongsTo(OverheadPor::class, 'por_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'job_order_id', 'kode_job');
    }

    public function jobOrderDetails(): HasMany
    {
        return $this->hasMany(JobOrderDetail::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'job_order_details')
                    ->withPivot(['quantity', 'unit_price', 'total_price', 'notes'])
                    ->withTimestamps();
    }

    // Method untuk menghitung total quantity dari semua produk
    public function getTotalQuantityAttribute()
    {
        return $this->jobOrderDetails()->sum('quantity');
    }

    // Method untuk menghitung total harga dari semua produk
    public function getTotalProductPriceAttribute()
    {
        return $this->jobOrderDetails()->sum('total_price');
    }

    /**
     * Calculate Total HPP with standard rounding (2 decimal places)
     */
    public function calculateTotalHPP(): float
    {
        $materialCost = $this->materials()->sum('total_biaya');
        $laborCost = $this->labors()->sum('biaya_btkl');
        $overheadCost = $this->total_bop ?? 0;
        
        // Total HPP = BBB + BTKL + BOP
        // BOP sudah termasuk bahan penolong, jadi tidak perlu ditambah lagi
        // Pembulatan standar final HPP untuk konsistensi (2 angka desimal)
        return round($materialCost + $laborCost + $overheadCost, 2);
    }

    /**
     * Update Total HPP with consistent rounding
     */
    public function updateTotalHPP(): void
    {
        $this->total_bbb = $this->materials()->sum('total_biaya');
        $this->total_btkl = $this->labors()->sum('biaya_btkl');
        $this->total_hpp = $this->calculateTotalHPP();
        
        $this->saveQuietly();
    }

    /**
     * Create journal entry when job order is completed
     */
    public function createCompletionJournalEntry(): void
    {
        // Gunakan JournalService untuk konsistensi
        $journalService = app(\App\Services\JournalService::class);
        
        // 1. Jurnal untuk Tenaga Kerja Langsung dan Overhead saat selesai job
        $journalService->createJournalFromJobOrderFinish($this);
        
        // 2. Jurnal untuk penyelesaian produksi (WIP ke Finished Goods)
        $journalService->createJournalFromProductionCompletion($this);
    }

    /**
     * Create journal entry when job order is started (WIP)
     */
    public function createStartJournalEntry(): void
    {
        // Gunakan JournalService untuk konsistensi
        $journalService = app(\App\Services\JournalService::class);
        
        // DEBUG: Log untuk investigasi
        \Log::info("createStartJournalEntry() called for JobOrder #{$this->id}");
        
        // Buat RawMaterialUsage untuk setiap material
        $materials = $this->materials()->with('rawMaterial')->get();
        \Log::info("Processing " . $materials->count() . " raw materials");
        
        foreach ($materials as $material) {
            $raw = $material->rawMaterial;
            if (!$raw) {
                \Log::info("Skipping material - no raw material");
                continue;
            }

            $qtyTotal = (float) ($material->qty_total ?? 0);
            if ($qtyTotal <= 0) {
                \Log::info("Skipping material - qty_total <= 0");
                continue;
            }

            $rawUnit = $raw->unit;
            $recipeUnit = $material->unit ?: $rawUnit;

            // GUNAKAN HARGA FIFO SESUAI REQUEST USER
            $unitPrice = $raw->getFifoPrice($qtyTotal, $recipeUnit);

            $cost = $qtyTotal * $unitPrice;

            // Buat RawMaterialUsage record
            $usage = \App\Models\RawMaterialUsage::create([
                'job_order_id' => $this->id,
                'raw_material_id' => $raw->id,
                'quantity_used' => $qtyTotal,
                'unit' => $recipeUnit,
                'unit_price' => $unitPrice,
                'cost' => $cost,
            ]);

            \Log::info("Created RawMaterialUsage #{$usage->id} for material {$raw->name}");

            // Langsung buat jurnal untuk usage ini
            $journalService->createJournalFromRawMaterialUsage($usage);
        }
        
        // DEBUG: Cek auxiliary materials
        $this->loadMissing('jobOrderDetails.product.auxiliaryMaterials');
        \Log::info("Loaded job order details for auxiliary materials");
        
        foreach ($this->jobOrderDetails as $detail) {
            $product = $detail->product;
            if (!$product) {
                \Log::info("Skipping detail - no product");
                continue;
            }
            
            \Log::info("Processing product: {$product->name} with " . $product->auxiliaryMaterials->count() . " auxiliary materials");
            
            // Setiap produk bisa punya banyak bahan penolong lewat pivot auxiliaryMaterials
            foreach ($product->auxiliaryMaterials as $aux) {
                // Qty resep per 1 unit produk (dalam unit resep penolong)
                $qtyPerUnit = (float) ($aux->pivot->quantity_needed ?? 0);
                if ($qtyPerUnit <= 0) {
                    \Log::info("Skipping auxiliary - qty_per_unit <= 0");
                    continue;
                }

                // Qty total = qty per unit × quantity produk di job order
                $qtyTotal = $qtyPerUnit * (float) $detail->quantity;

                if ($qtyTotal <= 0) {
                    \Log::info("Skipping auxiliary - qtyTotal <= 0");
                    continue;
                }

                \Log::info("Auxiliary {$aux->name} calculated for job: qty={$qtyTotal}");

                // Buat jurnal pemakaian bahan penolong menggunakan proporsi BOP
                $journalService->createAuxiliaryMaterialJournalForJobStart($this);
                
                // Hentikan loop karena method di atas sudah handle semua detail sekaligus
                break 2;
            }
        }
        
        \Log::info("createStartJournalEntry() completed for JobOrder #{$this->id}");
    }

    /**
     * Create journal entry for auxiliary material usage during job start
     */
    private function createAuxiliaryMaterialUsageJournal($auxiliaryMaterial, $quantity, $unitPrice, $cost, $journalService): void
    {
        // Buat jurnal pemakaian bahan penolong
        $journal = \App\Models\JournalEntry::create([
            'journal_number' => \App\Models\JournalEntry::generateJournalNumber('JU'),
            'transaction_date' => $this->order_date,
            'description' => "Pemakaian Bahan Penolong {$auxiliaryMaterial->name} - Job Order #{$this->id}",
            'source_type' => 'auxiliary_material_usage',
            'source_id' => $this->id,
            'status' => 'posted',
            'total_debit' => $cost,
            'total_credit' => $cost,
        ]);

        // Debit: BOP BP - (Bahan Penolong) menggunakan expense COA spesifik atau fallback ke '55'
        $expenseCoa = $auxiliaryMaterial->expenseCoa;
        if ($expenseCoa) {
            $journalService->addJournalItem($journal, $cost, 0, $expenseCoa->code, $expenseCoa->account_name);
        } else {
            // Fallback: gunakan COA generic BOP BP
            $coaName = "BOP BP - {$auxiliaryMaterial->name}";
            $journalService->addJournalItem($journal, $cost, 0, '55', $coaName);
        }

        // Credit: Persediaan Bahan Penolong spesifik
        $inventoryCoa = $auxiliaryMaterial->chartOfAccount;
        if ($inventoryCoa) {
            $journalService->addJournalItem($journal, 0, $cost, $inventoryCoa->code, $inventoryCoa->account_name);
        } else {
            // Fallback: gunakan COA generic persediaan bahan penolong
            $coaName = "Pers. Bahan Penolong {$auxiliaryMaterial->name}";
            $journalService->addJournalItem($journal, 0, $cost, '1107', $coaName);
        }

        \Log::info("Created auxiliary material usage journal for {$auxiliaryMaterial->name}, cost: {$cost}");
    }
}
