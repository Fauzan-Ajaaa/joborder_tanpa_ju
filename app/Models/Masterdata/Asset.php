<?php

namespace App\Models\Masterdata;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Traits\HasCompany;

class Asset extends Model
{
    use HasFactory, HasCompany;

    protected $table = 'assets';
    protected $primaryKey = 'id_assets';

    protected $keyType = 'string';
    public $incrementing = false;


    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id_assets)) {
                $model->id_assets = Str::uuid();
            }
        });
    }

    // Depreciation methods
    const DEPRECIATION_STRAIGHT_LINE = 'straight_line';
    const DEPRECIATION_DOUBLE_DECLINING = 'double_declining';
    const DEPRECIATION_SINGLE_DECLINING = 'single_declining';
    const DEPRECIATION_SYD = 'sum_of_years_digits';
    const DEPRECIATION_UOP = 'units_of_production';

    /**
     * Daftar metode penyusutan yang tersedia
     * 
     * @return array
     */
    public static function getDepreciationMethods()
    {
        return [
            self::DEPRECIATION_STRAIGHT_LINE => [
                'name' => 'Garis Lurus',
                'description' => 'Penyusutan merata sepanjang masa manfaat aset',
                'suitable_for' => 'Semua jenis aset, terutama yang memiliki manfaat yang stabil setiap tahun',
                'tax_purpose' => 'Dapat digunakan untuk tujuan pajak dan komersial',
            ],
            self::DEPRECIATION_DOUBLE_DECLINING => [
                'name' => 'Saldo Menurun Ganda',
                'description' => 'Penyusutan dipercepat dengan tarif 2x garis lurus',
                'suitable_for' => 'Aset yang kehilangan nilai lebih cepat di awal, seperti peralatan teknologi',
                'tax_purpose' => 'Cocok untuk tujuan pajak dengan manfaat pengurangan pajak lebih besar di awal',
            ],
            self::DEPRECIATION_SINGLE_DECLINING => [
                'name' => 'Saldo Menurun Tunggal',
                'description' => 'Penyusutan dipercepat dengan tarif 1x garis lurus',
                'suitable_for' => 'Aset dengan penurunan nilai yang lebih lambat dibanding metode ganda',
                'tax_purpose' => 'Dapat digunakan untuk tujuan pajak dengan percepatan sedang',
            ],
            self::DEPRECIATION_SYD => [
                'name' => 'Jumlah Angka Tahun',
                'description' => 'Penyusutan berdasarkan jumlah digit tahun masa manfaat',
                'suitable_for' => 'Aset dengan produktivitas menurun seiring waktu',
                'tax_purpose' => 'Cocok untuk tujuan komersial yang membutuhkan percepatan penyusutan',
            ],
            self::DEPRECIATION_UOP => [
                'name' => 'Satuan Hasil Produksi',
                'description' => 'Penyusutan berdasarkan pemakaian aktual (unit yang diproduksi)',
                'suitable_for' => 'Mesin dan peralatan yang penggunaannya bervariasi setiap periode',
                'tax_purpose' => 'Cocok untuk tujuan akuntansi manajemen yang membutuhkan pencocokan biaya dengan pendapatan',
                'requires' => ['total_production_units', 'production_rate']
            ],
        ];
    }

    // Tipe aset
    const TIPE_BANGUNAN  = 'bangunan';
    const TIPE_KENDARAAN = 'kendaraan';
    const TIPE_MESIN     = 'mesin';
    const TIPE_PERALATAN = 'peralatan';

    public static function getTipeAsset(): array
    {
        return [
            self::TIPE_BANGUNAN  => 'Bangunan',
            self::TIPE_KENDARAAN => 'Kendaraan',
            self::TIPE_MESIN     => 'Mesin',
            self::TIPE_PERALATAN => 'Peralatan',
        ];
    }

    protected $fillable = [
        'id_assets',
        'nama_asset',
        'tipe_asset',
        'harga_perolehan',
        'nilai_sisa',
        'masa_manfaat',
        'tanggal_perolehan',
        'depreciation_method',
        'total_production_units',
        'current_production_units',
        'production_rate',
        'start_depreciation_date',
        'is_fully_depreciated',
        'company_id',
    ];

    protected $casts = [
        'harga_perolehan' => 'float',
        'nilai_sisa' => 'float',
        'masa_manfaat' => 'integer',
        'tanggal_perolehan' => 'datetime',
        'total_production_units' => 'float',
        'current_production_units' => 'float',
        'production_rate' => 'float',
    ];

    protected $attributes = [
        'depreciation_method' => self::DEPRECIATION_STRAIGHT_LINE,
        'is_fully_depreciated' => false,
        'current_production_units' => 0,
    ];
    
    /**
     * Get the human-readable name of the current depreciation method
     * 
     * @return string|null
     */
    public function getDepreciationMethodNameAttribute()
    {
        $methods = self::getDepreciationMethods();
        return $methods[$this->depreciation_method]['name'] ?? null;
    }

    public function getRouteKeyName(): string
    {
        return 'id_assets';
    }

    public function getDepreciationPerMonthAttribute()
    {
        if ($this->masa_manfaat > 0) {
            return ($this->harga_perolehan - $this->nilai_sisa) / ($this->masa_manfaat * 12);
        }

        return 0;
    }

    public function getDepreciationPerYearAttribute()
    {
        if ($this->masa_manfaat > 0) {
            return ($this->harga_perolehan - $this->nilai_sisa) / $this->masa_manfaat;
        }

        return 0;
    }

    /**
     * Calculate depreciation based on the selected method
     */
    public function calculateDepreciationSchedule()
    {
        switch ($this->depreciation_method) {
            case self::DEPRECIATION_DOUBLE_DECLINING:
                return $this->calculateDoubleDecliningBalance();
            case self::DEPRECIATION_SINGLE_DECLINING:
                return $this->calculateDecliningBalance(1.0);
            case self::DEPRECIATION_SYD:
                return $this->calculateSumOfYearsDigits();
            case self::DEPRECIATION_UOP:
                return $this->calculateUnitsOfProduction();
            case self::DEPRECIATION_STRAIGHT_LINE:
            default:
                return $this->calculateStraightLine();
        }
    }

    /**
     * Straight Line Depreciation Method
     */
    protected function calculateStraightLine()
    {
        $depreciable_amount = $this->harga_perolehan - $this->nilai_sisa;
        $annual_depreciation = $depreciable_amount / $this->masa_manfaat;
        $monthly_depreciation = $annual_depreciation / 12;

        $accumulated_depreciation = 0;
        $schedule = [];
        $start_date = new \DateTime($this->tanggal_perolehan);

        $total_months = $this->masa_manfaat * 12;

        for ($month = 0; $month < $total_months; $month++) {
            $current_date = (clone $start_date)->modify("+{$month} months");
            
            // Calculate depreciation for this month
            $depreciation = $monthly_depreciation;
            
            // Don't depreciate below salvage value
            $current_book_value = $this->harga_perolehan - $accumulated_depreciation;
            if ($current_book_value - $depreciation < $this->nilai_sisa) {
                $depreciation = $current_book_value - $this->nilai_sisa;
            }
            
            // Update accumulated depreciation
            $accumulated_depreciation += $depreciation;
            $book_value = $this->harga_perolehan - $accumulated_depreciation;

            $schedule[] = [
                'bulan_tahun' => $current_date->format('F Y'),
                'bulan' => (int) $current_date->format('n'),
                'tahun' => (int) $current_date->format('Y'),
                'biaya_penyusutan' => $depreciation,
                'akumulasi_penyusutan' => $accumulated_depreciation, // Total including current month
                'nilai_buku' => $book_value,
            ];

            if ($book_value <= $this->nilai_sisa) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Double Declining Balance Depreciation Method
     */
    protected function calculateDoubleDecliningBalance()
    {
        $annual_rate = 2 / $this->masa_manfaat;
        $monthly_rate = $annual_rate / 12;

        $accumulated_depreciation = 0;
        $schedule = [];
        $start_date = new \DateTime($this->tanggal_perolehan);

        $total_months = $this->masa_manfaat * 12;

        for ($month = 0; $month < $total_months; $month++) {
            $current_date = (clone $start_date)->modify("+{$month} months");
            
            // Calculate current book value
            $current_book_value = $this->harga_perolehan - $accumulated_depreciation;
            
            // Calculate depreciation for this month
            $depreciation = $current_book_value * $monthly_rate;
            
            // Don't depreciate below salvage value
            if ($current_book_value - $depreciation < $this->nilai_sisa) {
                $depreciation = $current_book_value - $this->nilai_sisa;
            }
            
            // Update accumulated depreciation
            $accumulated_depreciation += $depreciation;
            $book_value = $this->harga_perolehan - $accumulated_depreciation;

            $schedule[] = [
                'bulan_tahun' => $current_date->format('F Y'),
                'bulan' => (int) $current_date->format('n'),
                'tahun' => (int) $current_date->format('Y'),
                'biaya_penyusutan' => $depreciation,
                'akumulasi_penyusutan' => $accumulated_depreciation, // Total including current month
                'nilai_buku' => $book_value,
            ];

            if ($book_value <= $this->nilai_sisa) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Generic Declining Balance Depreciation Method
     */
    protected function calculateDecliningBalance($factor = 1.0)
    {
        $depreciation_rate = ($factor / $this->masa_manfaat) / 12; // Monthly rate
        $book_value = $this->harga_perolehan;
        $accumulated_depreciation = 0;
        $schedule = [];
        $start_date = new \DateTime($this->tanggal_perolehan);

        $total_months = $this->masa_manfaat * 12;

        for ($month = 0; $month < $total_months; $month++) {
            $current_date = (clone $start_date)->modify("+{$month} months");

            $depreciation = $book_value * $depreciation_rate;
            
            // Ensure depreciation does not go below salvage value
            $depreciation = min($depreciation, $book_value - $this->nilai_sisa);

            $accumulated_depreciation += $depreciation;
            $book_value -= $depreciation;

            // Ensure book value does not go below salvage value
            if ($book_value < $this->nilai_sisa) {
                $book_value = $this->nilai_sisa;
            }
            
            $schedule[] = [
                'bulan_tahun' => $current_date->format('F Y'),
                'bulan' => (int) $current_date->format('n'),
                'tahun' => (int) $current_date->format('Y'),
                'biaya_penyusutan' => $depreciation,
                'akumulasi_penyusutan' => $accumulated_depreciation,
                'nilai_buku' => $book_value,
            ];

            if ($book_value <= $this->nilai_sisa) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Sum of the Years' Digits Depreciation Method
     */
    protected function calculateSumOfYearsDigits()
    {
        $sum_of_years = ($this->masa_manfaat * ($this->masa_manfaat + 1)) / 2;
        $depreciable_amount = $this->harga_perolehan - $this->nilai_sisa;
        
        $accumulated_depreciation = 0;
        $schedule = [];
        $start_date = new \DateTime($this->tanggal_perolehan);

        $total_months = $this->masa_manfaat * 12;

        for ($month = 0; $month < $total_months; $month++) {
            $current_date = (clone $start_date)->modify("+{$month} months");
            
            // Calculate current year and month in year
            $year_offset = floor($month / 12);
            $remaining_years = $this->masa_manfaat - $year_offset;
            
            // Calculate annual depreciation using SYD formula
            $annual_fraction = $remaining_years / $sum_of_years;
            $annual_depreciation = $annual_fraction * $depreciable_amount;
            
            // Monthly depreciation (annual divided by 12)
            $depreciation = $annual_depreciation / 12;
            
            // Don't depreciate below salvage value
            $current_book_value = $this->harga_perolehan - $accumulated_depreciation;
            if ($current_book_value - $depreciation < $this->nilai_sisa) {
                $depreciation = $current_book_value - $this->nilai_sisa;
            }
            
            // Update accumulated depreciation
            $accumulated_depreciation += $depreciation;
            $book_value = $this->harga_perolehan - $accumulated_depreciation;

            $schedule[] = [
                'bulan_tahun' => $current_date->format('F Y'),
                'bulan' => (int) $current_date->format('n'),
                'tahun' => (int) $current_date->format('Y'),
                'biaya_penyusutan' => $depreciation,
                'akumulasi_penyusutan' => $accumulated_depreciation, // Total including current month
                'nilai_buku' => $book_value,
            ];

            if ($book_value <= $this->nilai_sisa) {
                break;
            }
        }

        return $schedule;
    }

    /**
     * Units of Production Depreciation Method
     */
    protected function calculateUnitsOfProduction()
    {
        if ($this->total_production_units <= 0) {
            throw new \RuntimeException('Total production units must be greater than zero for UOP depreciation');
        }

        $depreciable_amount = $this->harga_perolehan - $this->nilai_sisa;
        $depreciation_per_unit = $depreciable_amount / $this->total_production_units;
        
        $accumulated_depreciation = 0;
        $book_value = $this->harga_perolehan;
        $schedule = [];
        $start_date = new \DateTime($this->tanggal_perolehan);
        $remaining_units = $this->total_production_units;
        $month = 0;

        while ($remaining_units > 0 && $book_value > $this->nilai_sisa) {
            $current_date = (clone $start_date)->modify("+{$month} months");
            $month++;
            
            // Get units produced this month (or remaining units if it's the last period)
            $units_this_month = min($this->production_rate ?? $this->total_production_units, $remaining_units);
            $depreciation = $units_this_month * $depreciation_per_unit;
            
            // Ensure we don't depreciate below salvage value
            $depreciation = min($depreciation, $book_value - $this->nilai_sisa);
            
            $accumulated_depreciation += $depreciation;
            $book_value = max($this->harga_perolehan - $accumulated_depreciation, $this->nilai_sisa);
            $remaining_units -= $units_this_month;

            $schedule[] = [
                'bulan_tahun' => $current_date->format('F Y'),
                'bulan' => (int) $current_date->format('n'),
                'tahun' => (int) $current_date->format('Y'),
                'biaya_penyusutan' => $depreciation,
                'akumulasi_penyusutan' => $accumulated_depreciation,
                'nilai_buku' => $book_value,
                'units_produced' => $units_this_month,
                'total_units_produced' => $this->total_production_units - $remaining_units,
            ];
        }

        return $schedule;
    }
}
