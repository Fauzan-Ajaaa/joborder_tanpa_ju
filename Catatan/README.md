# Sistem Manajemen Manufaktur

Aplikasi web untuk manajemen perusahaan manufaktur dengan fitur otomatis pengurangan stok bahan baku setiap transaksi.

## Teknologi yang Digunakan

- **Laravel 12** - Framework PHP
- **Filament v4** - Admin Panel Framework
- **MYSQL** - Database
- **MVC Architecture** - Arsitektur aplikasi

## Fitur Utama

### 1. Manajemen Bahan Baku (Raw Materials)
- CRUD bahan baku
- Tracking stok bahan baku
- Minimum stock alert
- Harga per unit
- Satuan (kg, liter, pcs, dll)

### 2. Manajemen Produk (Products)
- CRUD produk
- Tracking stok produk
- Harga jual produk

### 3. Manajemen Transaksi (Transactions)
Tiga tipe transaksi:

#### a. Transaksi Produksi
- Menambah stok produk
- **Otomatis mengurangi stok bahan baku** yang digunakan
- Mencatat penggunaan bahan baku per transaksi

#### b. Transaksi Penjualan
- Mengurangi stok produk
- Mencatat penjualan

#### c. Transaksi Pembelian
- Menambah stok bahan baku
- Mencatat pembelian bahan baku

### 4. Automatic Stock Management
- Stok bahan baku otomatis berkurang saat transaksi produksi diselesaikan
- Stok produk otomatis bertambah saat produksi
- Stok produk otomatis berkurang saat penjualan
- Stok bahan baku otomatis bertambah saat pembelian

### 5. Manajemen Karyawan (Employees)
- CRUD data karyawan
- Informasi lengkap karyawan (NIK, nama, email, telepon, alamat)
- Jabatan dan departemen
- Tanggal masuk kerja
- Gaji pokok
- Status karyawan (aktif, tidak aktif, berhenti)
- Auto-generate nomor karyawan

### 6. Penggajian (Payroll)
- Proses penggajian karyawan
- Komponen gaji:
  - Gaji pokok
  - Tunjangan
  - Lembur
  - Bonus
  - Potongan
  - Pajak
  - **Gaji bersih (otomatis dihitung)**
- Periode gaji (awal - akhir)
- Status: Draft, Approved, Paid
- Tanggal pembayaran
- Auto-generate nomor payroll
- Catatan tambahan

### 7. Pengeluaran/Beban (Expenses)
- Pencatatan semua pengeluaran perusahaan
- Kategori beban (operasional, marketing, dll)
- Link ke Chart of Accounts
- Informasi vendor/supplier
- Metode pembayaran
- Nomor referensi/invoice
- Status approval (pending, approved, paid, rejected)
- Upload bukti pembayaran
- Auto-generate nomor expense

### 8. Chart of Accounts (Bagan Akun)
- Struktur akun akuntansi lengkap
- 5 tipe akun:
  - **Asset** (Aset)
  - **Liability** (Kewajiban)
  - **Equity** (Ekuitas)
  - **Revenue** (Pendapatan)
  - **Expense** (Beban)
- Hierarki parent-child accounts
- Kode akun unik
- Saldo akun
- Status aktif/non-aktif

### 9. Jurnal Umum (Journal Entries)
- Double-entry bookkeeping
- Debit dan kredit harus balance
- Link ke referensi transaksi
- Status: Draft, Posted, Void
- Auto-generate nomor jurnal
- Journal entry lines untuk detail debit/kredit per akun

## Instalasi

### Prerequisites
- PHP 8.2 atau lebih tinggi
- Composer
- Node.js & NPM (optional)

### Langkah Instalasi

1. Clone repository atau download project

2. Install dependencies:
```bash
composer install
```

3. Copy file environment:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Jalankan migrasi database:
```bash
php artisan migrate
```

6. Seed database dengan data sample:
```bash
php artisan db:seed
```

7. Buat user admin untuk Filament:
```bash
php artisan make:filament-user
```

8. Jalankan development server:
```bash
php artisan serve
```

9. Akses aplikasi di browser:
```
http://localhost:8000/admin
```

## Struktur Database

### Tables

#### raw_materials
- id
- name
- code (unique)
- description
- unit (satuan)
- stock (stok saat ini)
- minimum_stock (stok minimum)
- price_per_unit (harga per unit)
- timestamps

#### products
- id
- name
- code (unique)
- description
- price (harga jual)
- stock (stok saat ini)
- timestamps

#### transactions
- id
- transaction_number (auto-generated, unique)
- type (production/sale/purchase)
- transaction_date
- notes
- total_amount
- status (pending/completed/cancelled)
- timestamps

#### transaction_items
- id
- transaction_id (foreign key)
- product_id (foreign key)
- quantity
- unit_price
- subtotal
- timestamps

#### raw_material_usages
- id
- transaction_id (foreign key)
- raw_material_id (foreign key)
- quantity_used
- cost
- timestamps

#### employees
- id
- employee_number (auto-generated, unique)
- name
- email (unique)
- phone
- address
- position (jabatan)
- department (departemen)
- hire_date (tanggal masuk)
- base_salary (gaji pokok)
- status (active/inactive/terminated)
- timestamps

#### payrolls
- id
- employee_id (foreign key)
- payroll_number (auto-generated, unique)
- period_start (awal periode)
- period_end (akhir periode)
- base_salary
- allowances (tunjangan)
- overtime (lembur)
- bonuses (bonus)
- deductions (potongan)
- tax (pajak)
- net_salary (gaji bersih)
- status (draft/approved/paid)
- payment_date
- notes
- timestamps

#### expenses
- id
- expense_number (auto-generated, unique)
- chart_of_account_id (foreign key)
- category (kategori beban)
- description
- expense_date
- amount
- vendor (supplier/vendor)
- payment_method
- reference_number (no referensi/invoice)
- status (pending/approved/paid/rejected)
- notes
- attachment (file bukti)
- timestamps

#### chart_of_accounts
- id
- account_code (unique)
- account_name
- account_type (asset/liability/equity/revenue/expense)
- parent_id (foreign key, nullable)
- description
- balance (saldo)
- is_active
- timestamps

#### journal_entries
- id
- journal_number (auto-generated, unique)
- transaction_date
- reference_type (tipe referensi)
- reference_id (id referensi)
- description
- total_debit
- total_credit
- status (draft/posted/void)
- timestamps

#### journal_entry_lines
- id
- journal_entry_id (foreign key)
- chart_of_account_id (foreign key)
- description
- debit
- credit
- timestamps

## Cara Penggunaan

### 1. Membuat Transaksi Produksi

1. Login ke admin panel
2. Klik menu "Transactions"
3. Klik "Create"
4. Pilih tipe transaksi: **Produksi**
5. Isi tanggal transaksi
6. Tambahkan item produk yang akan diproduksi
7. Tambahkan bahan baku yang digunakan
8. Pilih status **Completed** jika ingin langsung memproses
9. Klik "Create"

**Hasil:** 
- Stok bahan baku akan otomatis berkurang
- Stok produk akan otomatis bertambah

### 2. Membuat Transaksi Penjualan

1. Pilih tipe transaksi: **Penjualan**
2. Tambahkan produk yang dijual
3. Set status **Completed**
4. Klik "Create"

**Hasil:** Stok produk akan otomatis berkurang

### 3. Membuat Transaksi Pembelian

1. Pilih tipe transaksi: **Pembelian**
2. Tambahkan bahan baku yang dibeli
3. Set status **Completed**
4. Klik "Create"

**Hasil:** Stok bahan baku akan otomatis bertambah

### 4. Mengelola Karyawan

1. Klik menu "Employees"
2. Klik "Create" untuk menambah karyawan baru
3. Isi data karyawan (nama, email, jabatan, departemen, gaji pokok, dll)
4. Nomor karyawan akan di-generate otomatis
5. Klik "Create"

### 5. Membuat Penggajian

1. Klik menu "Payrolls"
2. Klik "Create"
3. Pilih karyawan
4. Isi periode gaji (awal - akhir)
5. Gaji pokok akan otomatis terisi dari data karyawan
6. Tambahkan komponen lain:
   - Tunjangan
   - Lembur
   - Bonus
   - Potongan
   - Pajak
7. **Gaji bersih akan otomatis dihitung**
8. Pilih status (Draft/Approved/Paid)
9. Isi tanggal pembayaran jika sudah dibayar
10. Klik "Create"

### 6. Mencatat Pengeluaran/Beban

1. Klik menu "Expenses"
2. Klik "Create"
3. Pilih akun dari Chart of Accounts
4. Isi kategori beban (operasional, marketing, dll)
5. Isi deskripsi pengeluaran
6. Isi tanggal dan jumlah
7. Isi informasi vendor (opsional)
8. Pilih metode pembayaran
9. Isi nomor referensi/invoice
10. Upload bukti pembayaran (opsional)
11. Pilih status approval
12. Klik "Create"

### 7. Mengelola Chart of Accounts

1. Klik menu "Chart Of Accounts"
2. Lihat struktur akun yang sudah ada
3. Untuk menambah akun baru:
   - Klik "Create"
   - Isi kode akun (unique)
   - Isi nama akun
   - Pilih tipe akun (Asset/Liability/Equity/Revenue/Expense)
   - Pilih parent account (opsional, untuk sub-account)
   - Klik "Create"

## Data Sample

Aplikasi sudah dilengkapi dengan data sample:

### Bahan Baku:
- Tepung Terigu (500 kg)
- Gula Pasir (300 kg)
- Mentega (200 kg)
- Telur (150 kg)
- Susu Cair (100 liter)

### Produk:
- Roti Tawar
- Roti Manis
- Kue Kering
- Cake Coklat

### Karyawan:
- Budi Santoso (Manager Produksi) - Rp 8.000.000
- Siti Nurhaliza (Supervisor Produksi) - Rp 6.000.000
- Ahmad Hidayat (Staff Akuntansi) - Rp 5.000.000
- Dewi Lestari (Operator Produksi) - Rp 4.500.000
- Rudi Hartono (Operator Produksi) - Rp 4.500.000

### Chart of Accounts:
Struktur akun lengkap dengan 28 akun meliputi:
- **Aset**: Kas, Bank, Piutang, Persediaan, Peralatan
- **Kewajiban**: Hutang Usaha, Hutang Gaji, Hutang Pajak
- **Ekuitas**: Modal, Laba Ditahan
- **Pendapatan**: Pendapatan Penjualan, Pendapatan Lain-lain
- **Beban**: Gaji, Bahan Baku, Listrik, Air, Telepon, Transportasi, Pemeliharaan, Penyusutan, dll

## Fitur Lengkap Aplikasi

### Modul Manufaktur
✅ Manajemen Bahan Baku  
✅ Manajemen Produk  
✅ Transaksi Produksi/Penjualan/Pembelian  
✅ Automatic Stock Management  

### Modul Akuntansi
✅ Manajemen Karyawan  
✅ Penggajian (Payroll)  
✅ Pengeluaran/Beban (Expenses)  
✅ Chart of Accounts (Bagan Akun)  
✅ Jurnal Umum (Journal Entries)  
✅ Double-Entry Bookkeeping  

### Fitur Otomatis
✅ Auto-generate nomor transaksi  
✅ Auto-generate nomor karyawan  
✅ Auto-generate nomor payroll  
✅ Auto-generate nomor expense  
✅ Auto-calculate gaji bersih  
✅ Auto-update stok bahan baku & produk  

## Kredensial Default

**Email:** admin@gmail.com  
**Password:** (yang Anda set saat membuat user)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
