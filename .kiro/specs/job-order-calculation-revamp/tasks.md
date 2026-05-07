# Implementation Plan: Job Order Calculation Revamp

## Overview

Implementasi `JobOrderCalculationService` sebagai sumber tunggal logika kalkulasi HPP, migrasi kolom snapshot, refactor controller, dan test suite menggunakan eris/eris.

## Tasks

- [x] 1. Buat migration untuk kolom snapshot di tabel `job_orders`
  - Buat file migration baru dengan `php artisan make:migration add_snapshot_columns_to_job_orders_table`
  - Tambahkan kolom: `snapshot_bbb`, `snapshot_btkl`, `snapshot_bop`, `snapshot_hpp` (DECIMAL 15,2 nullable), `snapshot_btkl_rate` (DECIMAL 10,4 nullable), `snapshot_at` (TIMESTAMP nullable)
  - Tambahkan kolom ke `$fillable` di `app/Models/JobOrder.php` dan cast yang sesuai
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 2. Buat `JobOrderCalculationService`
  - [x] 2.1 Implementasi `calculateDuration(JobOrder $jobOrder): float`
    - Query `SUM(bmp.duration_minutes) * jod.quantity` dari `bill_of_material_processes` JOIN `job_order_details`
    - Kembalikan `0` jika BOM tidak punya proses
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ]* 2.2 Tulis property test untuk `calculateDuration()`
    - **Property 1: Durasi selalu dari BOM Processes**
    - Generate BOM acak dengan N proses (duration_minutes acak), buat Job Order, bandingkan hasil `calculateDuration()` dengan `SUM(duration_minutes × quantity)`
    - Ubah `mulai_job_at`/`selesai_job_at`, pastikan durasi tidak berubah
    - Tag: `// Feature: job-order-calculation-revamp, Property 1: durasi dari BOM`
    - **Validates: Requirements 1.1, 1.2, 1.3**

  - [x] 2.3 Implementasi `calculateBBB(JobOrder $jobOrder): float`
    - JOIN `job_order_materials` ke `raw_materials` untuk `price_per_unit` live
    - Fallback ke `0` jika `price_per_unit` NULL atau 0, log warning
    - _Requirements: 2.1, 2.6_

  - [ ]* 2.4 Tulis property test untuk `calculateBBB()`
    - **Property 2: Live pricing BBB sebelum finished**
    - Generate Job Order non-finished, ubah `price_per_unit` di master, panggil `calculateBBB()`, pastikan hasilnya mencerminkan harga baru
    - Tag: `// Feature: job-order-calculation-revamp, Property 2: live pricing BBB`
    - **Validates: Requirements 2.1, 2.6**

  - [x] 2.5 Implementasi `getBtklRatePerHour(): float`
    - Query `SUM(base_salary) / NULLIF(SUM(jam_kerja_per_bulan), 0)` dari `employees` WHERE `status = 'active'`
    - Kembalikan `0.0` dan log warning jika tidak ada karyawan aktif atau total jam = 0
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ]* 2.6 Tulis property test untuk `getBtklRatePerHour()`
    - **Property 4: Tarif BTKL hanya dari karyawan aktif**
    - Generate set karyawan campuran (aktif + non-aktif), pastikan tarif hanya dari yang aktif
    - Tambah/ubah karyawan non-aktif, pastikan tarif tidak berubah
    - Tag: `// Feature: job-order-calculation-revamp, Property 4: BTKL hanya karyawan aktif`
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

  - [x] 2.7 Implementasi `calculateBTKL(JobOrder $jobOrder): float`
    - Hitung `durasi_jam × getBtklRatePerHour()`, bulatkan 2 desimal
    - _Requirements: 3.5_

  - [x] 2.8 Implementasi `calculateBOP(JobOrder $jobOrder): float`
    - Query `SUM(total)` dari `biaya_overhead` dengan filter `MONTH(periode) = MONTH(CURRENT_DATE) AND YEAR(periode) = YEAR(CURRENT_DATE)`
    - Tambahkan biaya bahan penolong live (JOIN `auxiliary_materials` untuk `price_per_unit` terkini)
    - Kembalikan `0.0` jika tidak ada data overhead bulan ini
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [ ]* 2.9 Tulis property test untuk `calculateBOP()`
    - **Property 5: BOP hanya dari bulan dan tahun berjalan**
    - Generate data `biaya_overhead` dari berbagai bulan, pastikan `calculateBOP()` hanya menjumlahkan bulan/tahun saat ini
    - Tag: `// Feature: job-order-calculation-revamp, Property 5: BOP bulan berjalan`
    - **Validates: Requirements 4.1, 4.2, 4.3**

  - [x] 2.10 Implementasi `calculateHPP(JobOrder $jobOrder): float`
    - Hitung `calculateBBB() + calculateBTKL() + calculateBOP()`, bulatkan 2 desimal
    - _Requirements: 5.1_

  - [ ]* 2.11 Tulis property test untuk `calculateHPP()`
    - **Property 6: Invariant HPP**
    - Generate nilai BBB, BTKL, BOP acak, pastikan `calculateHPP()` selalu mengembalikan jumlah ketiganya tanpa selisih
    - Tag: `// Feature: job-order-calculation-revamp, Property 6: invariant HPP`
    - **Validates: Requirements 5.1, 5.2**

  - [x] 2.12 Implementasi `snapshotOnFinish(JobOrder $jobOrder): void`
    - Cek `snapshot_at IS NOT NULL` — jika sudah ada, return tanpa menulis (idempoten)
    - Tulis `snapshot_bbb`, `snapshot_btkl`, `snapshot_bop`, `snapshot_hpp`, `snapshot_btkl_rate`, `snapshot_at`
    - Bekukan juga `total_bbb`, `total_btkl`, `total_bop`, `total_hpp`
    - _Requirements: 2.3, 2.4, 2.5, 5.2_

  - [ ]* 2.13 Tulis unit test untuk `snapshotOnFinish()`
    - Test idempoten: panggil dua kali, pastikan nilai snapshot tidak berubah
    - Test snapshot immutable: ubah harga master setelah snapshot, pastikan nilai snapshot tetap
    - Tag: `// Feature: job-order-calculation-revamp, Property 3: snapshot immutable`
    - **Validates: Requirements 2.3, 2.4, 2.5**

- [x] 3. Checkpoint — Pastikan semua test service lulus
  - Pastikan semua test lulus, tanyakan ke user jika ada pertanyaan.

- [x] 4. Refactor `JobOrderController` untuk delegasi ke service
  - [x] 4.1 Inject `JobOrderCalculationService` via constructor
    - Tambahkan `__construct(private JobOrderCalculationService $calculationService)` di controller
    - _Requirements: 5.3_

  - [x] 4.2 Refactor method `store()` — delegasi kalkulasi durasi dan BBB
    - Ganti logika kalkulasi inline dengan `$this->calculationService->calculateDuration()` dan `calculateBBB()`
    - Simpan hasil ke `durasi_menit`, `durasi_jam`, `total_bbb`
    - _Requirements: 1.4, 5.3, 5.4_

  - [x] 4.3 Refactor method `finish()` — delegasi kalkulasi BTKL, BOP, HPP, dan snapshot
    - Ganti semua logika kalkulasi inline dengan panggilan ke service
    - Panggil `$this->calculationService->snapshotOnFinish($job_order)` saat status berubah ke `finished`
    - Hapus variabel kalkulasi lokal yang sudah dipindah ke service
    - _Requirements: 2.3, 3.5, 4.1, 5.3, 5.4_

  - [ ]* 4.4 Tulis unit test untuk `JobOrderController`
    - Test bahwa controller tidak mengandung logika kalkulasi langsung (mock service)
    - Test bahwa `snapshotOnFinish()` dipanggil saat `finish()` dieksekusi
    - _Requirements: 5.3_

- [x] 5. Final checkpoint — Pastikan semua test lulus
  - Jalankan seluruh test suite, pastikan semua lulus, tanyakan ke user jika ada pertanyaan.

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Property tests menggunakan **eris/eris** dengan minimum 100 iterasi per property
- Kolom `total_*` yang sudah ada tetap digunakan sebagai nilai live; kolom `snapshot_*` hanya diisi saat `finished`
- `snapshotOnFinish()` bersifat idempoten — aman dipanggil lebih dari sekali
