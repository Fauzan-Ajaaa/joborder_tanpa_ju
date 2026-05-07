<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * DepreciationService
 * 
 * Menghitung penyusutan aset tetap berdasarkan 3 metode:
 * - Garis Lurus (Straight Line)
 * - Jumlah Angka Tahun (Sum of Years Digits)
 * - Saldo Menurun Ganda (Double Declining Balance)
 * 
 * Aturan cut-off tanggal:
 * - Tanggal <= 15 → mulai bulan tersebut
 * - Tanggal > 15  → mulai bulan berikutnya
 */
class DepreciationService
{
    const METHOD_STRAIGHT_LINE     = 'straight_line';
    const METHOD_SYD               = 'sum_of_years_digits';
    const METHOD_DOUBLE_DECLINING  = 'double_declining';

    /**
     * Tipe aset yang hanya boleh menggunakan metode garis lurus
     */
    const FIXED_METHOD_TYPES = ['bangunan'];

    /**
     * Kembalikan metode yang diizinkan berdasarkan tipe aset
     */
    public static function allowedMethods(string $tipeAsset): array
    {
        if (in_array($tipeAsset, self::FIXED_METHOD_TYPES)) {
            return [self::METHOD_STRAIGHT_LINE];
        }

        return [
            self::METHOD_STRAIGHT_LINE,
            self::METHOD_SYD,
            self::METHOD_DOUBLE_DECLINING,
        ];
    }

    /**
     * Hitung jadwal penyusutan bulanan
     *
     * @param float  $hargaPerolehan
     * @param float  $nilaiResidu
     * @param int    $umurEkonomis   (tahun)
     * @param string $tanggalPerolehan  (Y-m-d)
     * @param string $method
     * @param string $mode  'bulanan' | 'tahunan'
     * @return array
     */
    public function calculate(
        float $hargaPerolehan,
        float $nilaiResidu,
        int $umurEkonomis,
        string $tanggalPerolehan,
        string $method = self::METHOD_STRAIGHT_LINE,
        string $mode = 'bulanan'
    ): array {
        $startDate = $this->resolveStartDate($tanggalPerolehan);
        $depreciableAmount = $hargaPerolehan - $nilaiResidu;
        $totalMonths = $umurEkonomis * 12;

        $schedule = match ($method) {
            self::METHOD_SYD             => $this->calcSYD($hargaPerolehan, $nilaiResidu, $umurEkonomis, $depreciableAmount, $startDate, $totalMonths),
            self::METHOD_DOUBLE_DECLINING => $this->calcDoubleDeclining($hargaPerolehan, $nilaiResidu, $umurEkonomis, $startDate, $totalMonths),
            default                       => $this->calcStraightLine($hargaPerolehan, $nilaiResidu, $depreciableAmount, $startDate, $totalMonths),
        };

        if ($mode === 'tahunan') {
            return $this->groupByYear($schedule);
        }

        return $schedule;
    }

    /**
     * Hitung perbandingan 3 metode sekaligus (untuk simulasi)
     */
    public function simulate(
        float $hargaPerolehan,
        float $nilaiResidu,
        int $umurEkonomis,
        string $tanggalPerolehan,
        string $mode = 'tahunan'
    ): array {
        return [
            'straight_line'    => $this->calculate($hargaPerolehan, $nilaiResidu, $umurEkonomis, $tanggalPerolehan, self::METHOD_STRAIGHT_LINE, $mode),
            'sum_of_years_digits' => $this->calculate($hargaPerolehan, $nilaiResidu, $umurEkonomis, $tanggalPerolehan, self::METHOD_SYD, $mode),
            'double_declining' => $this->calculate($hargaPerolehan, $nilaiResidu, $umurEkonomis, $tanggalPerolehan, self::METHOD_DOUBLE_DECLINING, $mode),
        ];
    }

    // -------------------------------------------------------------------------
    // Private: Metode Perhitungan
    // -------------------------------------------------------------------------

    /**
     * Garis Lurus: penyusutan = (harga - residu) / umur
     */
    private function calcStraightLine(
        float $hargaPerolehan,
        float $nilaiResidu,
        float $depreciableAmount,
        Carbon $startDate,
        int $totalMonths
    ): array {
        $monthlyDep = round($depreciableAmount / $totalMonths, 2);
        $accumulated = 0.0;
        $schedule = [];

        for ($m = 0; $m < $totalMonths; $m++) {
            $date = $startDate->copy()->addMonths($m);
            $bookValue = $hargaPerolehan - $accumulated;

            // Penyesuaian bulan terakhir agar akumulasi tepat
            $dep = ($m === $totalMonths - 1)
                ? round($bookValue - $nilaiResidu, 2)
                : $monthlyDep;

            // Jangan turun di bawah nilai residu
            $dep = min($dep, round($bookValue - $nilaiResidu, 2));
            $dep = max($dep, 0);

            $accumulated = round($accumulated + $dep, 2);
            $schedule[] = $this->row($date, $dep, $accumulated, round($hargaPerolehan - $accumulated, 2));
        }

        return $schedule;
    }

    /**
     * Jumlah Angka Tahun: total = n(n+1)/2
     * penyusutan_tahun_ke-i = (sisa_umur / total) * depreciableAmount
     */
    private function calcSYD(
        float $hargaPerolehan,
        float $nilaiResidu,
        int $umurEkonomis,
        float $depreciableAmount,
        Carbon $startDate,
        int $totalMonths
    ): array {
        $sumOfYears = ($umurEkonomis * ($umurEkonomis + 1)) / 2;
        $accumulated = 0.0;
        $schedule = [];

        for ($m = 0; $m < $totalMonths; $m++) {
            $date = $startDate->copy()->addMonths($m);
            $yearIndex = (int) floor($m / 12); // 0-based
            $remainingYears = $umurEkonomis - $yearIndex;

            $annualDep = round(($remainingYears / $sumOfYears) * $depreciableAmount, 2);
            $monthlyDep = round($annualDep / 12, 2);

            $bookValue = $hargaPerolehan - $accumulated;
            $dep = min($monthlyDep, round($bookValue - $nilaiResidu, 2));
            $dep = max($dep, 0);

            $accumulated = round($accumulated + $dep, 2);
            $schedule[] = $this->row($date, $dep, $accumulated, round($hargaPerolehan - $accumulated, 2));
        }

        return $schedule;
    }

    /**
     * Saldo Menurun Ganda: tarif = 2/umur
     * penyusutan = tarif * nilai_buku_awal_tahun
     */
    private function calcDoubleDeclining(
        float $hargaPerolehan,
        float $nilaiResidu,
        int $umurEkonomis,
        Carbon $startDate,
        int $totalMonths
    ): array {
        $annualRate = 2 / $umurEkonomis;
        $monthlyRate = $annualRate / 12;
        $accumulated = 0.0;
        $schedule = [];

        for ($m = 0; $m < $totalMonths; $m++) {
            $date = $startDate->copy()->addMonths($m);
            $bookValue = $hargaPerolehan - $accumulated;

            $dep = round($bookValue * $monthlyRate, 2);

            // Jangan turun di bawah nilai residu
            $dep = min($dep, round($bookValue - $nilaiResidu, 2));
            $dep = max($dep, 0);

            $accumulated = round($accumulated + $dep, 2);
            $schedule[] = $this->row($date, $dep, $accumulated, round($hargaPerolehan - $accumulated, 2));

            if (round($hargaPerolehan - $accumulated, 2) <= $nilaiResidu) {
                break;
            }
        }

        return $schedule;
    }

    // -------------------------------------------------------------------------
    // Private: Helpers
    // -------------------------------------------------------------------------

    /**
     * Tentukan tanggal mulai penyusutan berdasarkan aturan cut-off:
     * <= 15 → bulan tersebut, > 15 → bulan berikutnya
     */
    private function resolveStartDate(string $tanggalPerolehan): Carbon
    {
        $date = Carbon::parse($tanggalPerolehan);
        if ($date->day > 15) {
            $date->addMonthNoOverflow()->startOfMonth();
        } else {
            $date->startOfMonth();
        }
        return $date;
    }

    private function row(Carbon $date, float $dep, float $accumulated, float $bookValue): array
    {
        return [
            'bulan'               => $date->month,
            'tahun'               => $date->year,
            'bulan_tahun'         => $date->translatedFormat('F Y'),
            'beban_penyusutan'    => $dep,
            'akumulasi_penyusutan' => $accumulated,
            'nilai_buku'          => $bookValue,
        ];
    }

    /**
     * Kelompokkan jadwal bulanan menjadi tahunan
     */
    private function groupByYear(array $schedule): array
    {
        $grouped = [];
        foreach ($schedule as $row) {
            $year = $row['tahun'];
            if (!isset($grouped[$year])) {
                $grouped[$year] = [
                    'tahun'               => $year,
                    'beban_penyusutan'    => 0.0,
                    'akumulasi_penyusutan' => 0.0,
                    'nilai_buku'          => 0.0,
                ];
            }
            $grouped[$year]['beban_penyusutan']    = round($grouped[$year]['beban_penyusutan'] + $row['beban_penyusutan'], 2);
            $grouped[$year]['akumulasi_penyusutan'] = $row['akumulasi_penyusutan'];
            $grouped[$year]['nilai_buku']           = $row['nilai_buku'];
        }
        return array_values($grouped);
    }
}
