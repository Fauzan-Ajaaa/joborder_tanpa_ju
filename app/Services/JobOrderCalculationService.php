<?php

namespace App\Services;

use App\Models\JobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobOrderCalculationService
{
    /**
     * Hitung total durasi produksi dari BOM Processes.
     *
     * Query SUM(bmp.duration_minutes) * jod.quantity dari bill_of_material_processes
     * JOIN bill_of_materials JOIN job_order_details.
     * Simpan hasil ke job_order.durasi_menit dan job_order.durasi_jam.
     *
     * Requirements: 1.1, 1.2, 1.3, 1.4
     */
    public function calculateDuration(JobOrder $jobOrder): float
    {
        $totalMinutes = (float) DB::table('bill_of_material_processes as bmp')
            ->join('bill_of_materials as bom', 'bom.id', '=', 'bmp.bill_of_material_id')
            ->join('job_order_details as jod', 'jod.product_id', '=', 'bom.product_id')
            ->where('jod.job_order_id', $jobOrder->id)
            ->sum(DB::raw('bmp.duration_minutes * jod.quantity'));

        $jobOrder->durasi_menit = $totalMinutes;
        $jobOrder->durasi_jam   = round($totalMinutes / 60, 4);
        $jobOrder->saveQuietly();

        return $totalMinutes;
    }

    /**
     * Hitung total Bahan Baku Langsung (BBB) dengan live pricing dari raw_materials.
     *
     * JOIN job_order_materials ke raw_materials untuk price_per_unit terkini.
     * Fallback ke 0 jika price_per_unit NULL atau 0, log warning per item.
     *
     * Requirements: 2.1, 2.6
     */
    public function calculateBBB(JobOrder $jobOrder): float
    {
        $materials = DB::table('job_order_materials as jom')
            ->leftJoin('raw_materials as rm', 'rm.id', '=', 'jom.bahan_baku_id')
            ->where('jom.job_order_id', $jobOrder->id)
            ->select('jom.id', 'jom.qty_total', 'jom.harga_per_unit', 'rm.price_per_unit', 'rm.name as material_name')
            ->get();

        $total = 0.0;

        foreach ($materials as $item) {
            // Prioritas: harga_per_unit dari job_order_materials (sudah dihitung saat job dibuat)
            // Fallback ke price_per_unit dari raw_materials
            $price = (float) ($item->harga_per_unit ?? 0);
            if ($price <= 0) {
                $price = (float) ($item->price_per_unit ?? 0);
            }

            if ($price <= 0) {
                Log::warning('JobOrderCalculationService: harga_per_unit NULL atau 0 untuk job_order_material', [
                    'job_order_id'          => $jobOrder->id,
                    'job_order_material_id' => $item->id,
                    'material_name'         => $item->material_name ?? 'unknown',
                ]);
                continue;
            }

            $total += (float) ($item->qty_total ?? 0) * $price;
        }

        return $total;
    }

    /**
     * Hitung tarif BTKL per jam dari karyawan dengan employee_type = 'BTKL'.
     *
     * SUM(base_salary) / NULLIF(SUM(jam_kerja_per_bulan), 0) dari employees WHERE employee_type = 'BTKL'.
     * Kembalikan 0.0 dan log warning jika tidak ada karyawan BTKL atau total jam = 0.
     *
     * Requirements: 3.1, 3.2, 3.3, 3.4
     */
    public function getBtklRatePerHour(): float
    {
        // Ambil semua karyawan BTKL yang aktif, hitung total tarif per jam
        $employees = DB::table('employees')
            ->where('employee_type', 'BTKL')
            ->whereRaw('UPPER(status) = ?', ['ACTIVE'])
            ->select('base_salary', 'jam_kerja_per_bulan')
            ->get();

        if ($employees->isEmpty()) {
            Log::warning('JobOrderCalculationService: tidak ada karyawan BTKL aktif, tarif BTKL dikembalikan 0.0');
            return 0.0;
        }

        $totalRate = 0.0;
        foreach ($employees as $emp) {
            $jam = (float)($emp->jam_kerja_per_bulan ?? 0);
            $salary = (float)($emp->base_salary ?? 0);
            $rate = ($jam > 0 && $salary > 0) ? $salary / $jam : 0;
            $totalRate += $rate;
        }

        return $totalRate;
    }

    /**
     * Hitung total BTKL = durasi_jam × tarif per jam.
     *
     * Requirements: 3.5
     */
    public function calculateBTKL(JobOrder $jobOrder): float
    {
        // Gunakan durasi_jam dari job order (sudah dikali qty oleh calculateDuration)
        $durasiJam = (float)($jobOrder->durasi_jam ?? 0);

        // Ambil tarif BTKL dari BOM
        $productId = $jobOrder->jobOrderDetails()->first()?->product_id ?? $jobOrder->product_id;
        $bom = DB::table('bill_of_materials')->where('product_id', $productId)->first();
        $tarifPerJam = $bom ? (float)$bom->btkl_rate_per_hour : $this->getBtklRatePerHour();

        // Fallback: jika durasi_jam belum dihitung, hitung dulu
        if ($durasiJam <= 0) {
            $this->calculateDuration($jobOrder);
            $jobOrder->refresh();
            $durasiJam = (float)($jobOrder->durasi_jam ?? 0);
        }

        return round($durasiJam * $tarifPerJam, 2);
    }

    /**
     * Hitung total BOP = (POR per jam × durasi_jam) + biaya bahan penolong live.
     *
     * POR per jam diambil dari overhead_por bulan berjalan.
     * Bahan penolong: JOIN job_order_details -> product -> auxiliary_materials (price_per_unit terkini).
     *
     * Requirements: 4.1, 4.2, 4.3, 4.4
     */
    public function calculateBOP(JobOrder $jobOrder): float
    {
        // BOP hanya dari POR per jam (bahan penolong TIDAK menambah BOP)
        $por = DB::table('overhead_por')
            ->where('periode', now()->format('Y-m'))
            ->orderByDesc('id')
            ->first();

        $porPerJam   = (float) ($por->por_per_jam ?? 0);
        $durasiJam   = (float) ($jobOrder->durasi_jam ?? 0);
        $bopFromPor  = round($porPerJam * $durasiJam, 2);

        if ($porPerJam <= 0) {
            Log::warning('JobOrderCalculationService: tidak ada data overhead_por untuk bulan berjalan, BOP dari POR = 0', [
                'job_order_id' => $jobOrder->id,
            ]);
        }

        return $bopFromPor;
    }

    /**
     * Hitung total HPP = BBB + BTKL + BOP, bulatkan 2 desimal.
     *
     * Requirements: 5.1
     */
    public function calculateHPP(JobOrder $jobOrder): float
    {
        return round(
            $this->calculateBBB($jobOrder)
            + $this->calculateBTKL($jobOrder)
            + $this->calculateBOP($jobOrder),
            2
        );
    }

    /**
     * Bekukan semua nilai HPP saat Job Order selesai (idempoten).
     *
     * Cek snapshot_at IS NOT NULL — jika sudah ada, return tanpa menulis.
     * Tulis snapshot_bbb, snapshot_btkl, snapshot_bop, snapshot_hpp, snapshot_btkl_rate, snapshot_at.
     * Bekukan juga total_bbb, total_btkl, total_bop, total_hpp.
     *
     * Requirements: 2.3, 2.4, 2.5, 5.2
     */
    public function snapshotOnFinish(JobOrder $jobOrder): void
    {
        // Idempoten: jika snapshot sudah ada, jangan timpa
        if (! empty($jobOrder->snapshot_at)) {
            return;
        }

        $bbb      = $this->calculateBBB($jobOrder);
        $btkl     = $this->calculateBTKL($jobOrder);
        $bop      = $this->calculateBOP($jobOrder);
        $hpp      = round($bbb + $btkl + $bop, 2);
        
        // Ambil tarif BTKL dari BOM (sumber kebenaran)
        $productId = $jobOrder->jobOrderDetails()->first()?->product_id ?? $jobOrder->product_id;
        $bom = DB::table('bill_of_materials')->where('product_id', $productId)->first();
        $btklRate = $bom ? (float)$bom->btkl_rate_per_hour : $this->getBtklRatePerHour();

        $jobOrder->update([
            'snapshot_bbb'       => $bbb,
            'snapshot_btkl'      => $btkl,
            'snapshot_bop'       => $bop,
            'snapshot_hpp'       => $hpp,
            'snapshot_btkl_rate' => $btklRate,
            'snapshot_at'        => now(),
            // Bekukan kolom total_* agar konsisten dengan snapshot
            'total_bbb'          => $bbb,
            'total_btkl'         => $btkl,
            'total_bop'          => $bop,
            'total_hpp'          => $hpp,
        ]);
    }
}
