# Requirements Document

## Introduction

Fitur ini merevisi logika kalkulasi HPP (Harga Pokok Produksi) pada Job Order agar konsisten, akurat, dan dapat diaudit. Revamp mencakup empat area utama: sumber durasi produksi dari BOM Process, live pricing untuk BBB dan Bahan Penolong dengan snapshot saat selesai, tarif BTKL dinamis dari data karyawan aktif, dan filter BOP hanya dari bulan berjalan.

## Glossary

- **Job_Order**: Perintah produksi yang mencatat proses pembuatan produk dari awal hingga selesai
- **HPP**: Harga Pokok Produksi — total biaya produksi yang terdiri dari BBB + BTKL + BOP
- **BBB**: Bahan Baku Langsung — biaya material utama yang digunakan dalam produksi
- **BTKL**: Biaya Tenaga Kerja Langsung — biaya tenaga kerja yang terlibat langsung dalam produksi
- **BOP**: Biaya Overhead Pabrik — biaya tidak langsung termasuk bahan penolong dan overhead lainnya
- **BOM**: Bill of Material — daftar komponen, material, dan proses yang dibutuhkan untuk membuat satu produk
- **BOM_Process**: Satu baris proses dalam BOM yang memiliki atribut `duration_minutes`
- **Calculation_Service**: Komponen `JobOrderCalculationService` yang bertanggung jawab atas semua logika kalkulasi HPP
- **Snapshot**: Nilai HPP yang dibekukan saat Job Order berstatus `finished` dan tidak berubah setelahnya
- **Live_Pricing**: Harga yang selalu diambil langsung dari tabel master (`raw_materials` atau `auxiliary_materials`) pada saat kalkulasi
- **Karyawan_Aktif**: Karyawan dengan kolom `status = 'active'` di tabel `employees`

## Requirements

### Requirement 1: Sumber Durasi dari BOM Process

**User Story:** As a manajer produksi, I want durasi Job Order dihitung dari `bill_of_material_processes`, so that durasi produksi konsisten dan tidak bergantung pada pencatatan waktu manual.

#### Acceptance Criteria

1. WHEN `calculateDuration()` dipanggil untuk sebuah Job Order, THE Calculation_Service SHALL menghitung total durasi sebagai `SUM(duration_minutes) × quantity` dari semua `BOM_Process` yang terkait dengan produk dalam Job Order tersebut
2. WHEN nilai `mulai_job_at` atau `selesai_job_at` pada Job Order diubah, THE Calculation_Service SHALL tetap mengembalikan nilai durasi yang sama berdasarkan BOM Process tanpa terpengaruh perubahan tersebut
3. IF sebuah BOM tidak memiliki satu pun `BOM_Process`, THEN THE Calculation_Service SHALL mengembalikan durasi sebesar `0`
4. THE Calculation_Service SHALL menyimpan hasil kalkulasi durasi dalam satuan menit ke kolom `durasi_menit` dan dalam satuan jam ke kolom `durasi_jam` pada tabel `job_orders`

---

### Requirement 2: Live Pricing BBB dan Bahan Penolong dengan Snapshot

**User Story:** As a akuntan, I want harga BBB dan Bahan Penolong selalu mencerminkan harga terkini sebelum Job Order selesai dan dibekukan saat selesai, so that laporan HPP akurat dan dapat diaudit.

#### Acceptance Criteria

1. WHILE status Job Order bukan `finished`, THE Calculation_Service SHALL menghitung total BBB dengan melakukan JOIN ke tabel `raw_materials` untuk mengambil `price_per_unit` terkini
2. WHILE status Job Order bukan `finished`, THE Calculation_Service SHALL menghitung biaya Bahan Penolong dengan melakukan JOIN ke tabel `auxiliary_materials` untuk mengambil `price_per_unit` terkini
3. WHEN status Job Order berubah menjadi `finished`, THE Calculation_Service SHALL menulis nilai BBB, BTKL, BOP, dan HPP yang dihitung saat itu ke kolom `snapshot_bbb`, `snapshot_btkl`, `snapshot_bop`, `snapshot_hpp`, dan `snapshot_at` pada tabel `job_orders`
4. WHEN `snapshotOnFinish()` dipanggil pada Job Order yang kolom `snapshot_at`-nya sudah terisi, THE Calculation_Service SHALL tidak menimpa nilai snapshot yang sudah ada (idempoten)
5. AFTER status Job Order berubah menjadi `finished`, THE Job_Order SHALL mempertahankan nilai `snapshot_bbb`, `snapshot_btkl`, `snapshot_bop`, dan `snapshot_hpp` yang tidak berubah meskipun harga di tabel `raw_materials` atau `auxiliary_materials` diubah setelahnya
6. IF `price_per_unit` sebuah material bernilai `NULL` atau `0`, THEN THE Calculation_Service SHALL menggunakan nilai `0` sebagai fallback untuk material tersebut dan mencatat peringatan di log

---

### Requirement 3: Tarif BTKL Dinamis dari Karyawan Aktif

**User Story:** As a akuntan, I want tarif BTKL per jam dihitung secara dinamis dari data karyawan aktif, so that biaya tenaga kerja selalu mencerminkan kondisi aktual penggajian.

#### Acceptance Criteria

1. WHEN `getBtklRatePerHour()` dipanggil, THE Calculation_Service SHALL menghitung tarif per jam sebagai `SUM(base_salary) / SUM(jam_kerja_per_bulan)` dari semua karyawan dengan `status = 'active'`
2. WHEN karyawan dengan status selain `active` ditambahkan atau diubah datanya, THE Calculation_Service SHALL tetap mengembalikan tarif yang sama tanpa memperhitungkan karyawan non-aktif tersebut
3. IF `SUM(jam_kerja_per_bulan)` dari seluruh karyawan aktif bernilai `0`, THEN THE Calculation_Service SHALL mengembalikan tarif `0.0` dan mencatat peringatan di log
4. IF tidak ada karyawan dengan `status = 'active'`, THEN THE Calculation_Service SHALL mengembalikan tarif `0.0` dan mencatat peringatan di log
5. WHEN `calculateBTKL()` dipanggil untuk sebuah Job Order, THE Calculation_Service SHALL menghitung total BTKL sebagai `durasi_jam × getBtklRatePerHour()`

---

### Requirement 4: Reset BOP Bulanan

**User Story:** As a akuntan, I want kalkulasi BOP hanya menggunakan data overhead dari bulan dan tahun berjalan, so that biaya overhead tidak terkontaminasi data dari periode lain.

#### Acceptance Criteria

1. WHEN `calculateBOP()` dipanggil, THE Calculation_Service SHALL mengambil total overhead dari tabel `biaya_overhead` dengan filter `MONTH(periode) = MONTH(CURRENT_DATE) AND YEAR(periode) = YEAR(CURRENT_DATE)`
2. WHEN terdapat data di tabel `biaya_overhead` dari bulan atau tahun yang berbeda dengan bulan dan tahun berjalan, THE Calculation_Service SHALL tidak menyertakan data tersebut dalam kalkulasi BOP
3. IF tidak ada data di tabel `biaya_overhead` untuk bulan dan tahun berjalan, THEN THE Calculation_Service SHALL menggunakan nilai overhead `0.0` dan tetap melanjutkan kalkulasi biaya Bahan Penolong
4. THE Calculation_Service SHALL menambahkan biaya Bahan Penolong yang dihitung secara live ke hasil BOP dari `biaya_overhead` bulan berjalan untuk menghasilkan total BOP

---

### Requirement 5: Konsistensi Formula HPP

**User Story:** As a akuntan, I want nilai `total_hpp` selalu sama dengan penjumlahan `total_bbb + total_btkl + total_bop`, so that laporan keuangan tidak memiliki selisih yang tidak dapat dijelaskan.

#### Acceptance Criteria

1. THE Calculation_Service SHALL menghitung `total_hpp` sebagai `total_bbb + total_btkl + total_bop` dengan pembulatan konsisten dua angka desimal
2. WHEN Job Order berstatus `finished`, THE Job_Order SHALL memastikan `snapshot_hpp` sama dengan `snapshot_bbb + snapshot_btkl + snapshot_bop`
3. THE Calculation_Service SHALL mendelegasikan semua logika kalkulasi HPP sehingga `JobOrderController` tidak mengandung logika kalkulasi BBB, BTKL, BOP, atau HPP secara langsung
4. THE Calculation_Service SHALL menyimpan nilai `total_bbb`, `total_btkl`, `total_bop`, dan `total_hpp` ke kolom yang sesuai di tabel `job_orders` setiap kali kalkulasi dijalankan sebelum status `finished`
