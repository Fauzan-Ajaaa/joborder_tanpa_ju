# Implementasi Fitur Supplier (Pemasok) untuk Bahan Baku

## Ringkasan Perubahan
Fitur Supplier telah ditambahkan ke sistem untuk mengelola pemasok bahan baku. Setiap bahan baku dan transaksi pembelian sekarang dapat dikaitkan dengan pemasok tertentu.

---

## 1. Database Changes

### A. Tabel Baru: `suppliers`
**File:** `database/migrations/2025_10_21_030900_create_suppliers_table.php`

**Struktur Tabel:**
- `id` - Primary key
- `code` - Kode pemasok (unique, auto-generated: SUP-YYYYMMDD-XXXX)
- `name` - Nama pemasok
- `contact_person` - Nama kontak person
- `email` - Email pemasok
- `phone` - Nomor telepon
- `address` - Alamat lengkap
- `city` - Kota
- `province` - Provinsi
- `postal_code` - Kode pos
- `status` - Status (active/inactive)
- `notes` - Catatan tambahan
- `timestamps` - Created at & Updated at

### B. Update Tabel: `raw_materials`
**File:** `database/migrations/2025_10_21_030901_add_supplier_id_to_raw_materials_table.php`

**Kolom Ditambahkan:**
- `supplier_id` (nullable, foreign key ke tabel suppliers)
- Relasi: ON DELETE SET NULL

### C. Update Tabel: `transactions`
**File:** `database/migrations/2025_10_21_030902_add_supplier_id_to_transactions_table.php`

**Kolom Ditambahkan:**
- `supplier_id` (nullable, foreign key ke tabel suppliers)
- Relasi: ON DELETE SET NULL

---

## 2. Model Changes

### A. Model Baru: `Supplier`
**File:** `app/Models/Supplier.php`

**Fitur:**
- Auto-generate kode supplier (SUP-YYYYMMDD-XXXX)
- Relasi ke RawMaterials (hasMany)
- Relasi ke Transactions (hasMany)
- Scope untuk filter supplier aktif: `active()`

**Fillable Fields:**
```php
'code', 'name', 'contact_person', 'email', 'phone', 
'address', 'city', 'province', 'postal_code', 'status', 'notes'
```

### B. Update Model: `RawMaterial`
**File:** `app/Models/RawMaterial.php`

**Perubahan:**
- Ditambahkan `supplier_id` ke fillable
- Ditambahkan relasi `belongsTo(Supplier::class)`

### C. Update Model: `Transaction`
**File:** `app/Models/Transaction.php`

**Perubahan:**
- Ditambahkan `supplier_id` ke fillable
- Ditambahkan relasi `belongsTo(Supplier::class)`

---

## 3. Filament Resource - Supplier CRUD

### A. Resource File
**File:** `app/Filament/Admin/Resources/Suppliers/SupplierResource.php`

**Konfigurasi:**
- Navigation Icon: BuildingStorefront
- Navigation Label: "Pemasok"
- Navigation Group: "Master Data"
- Navigation Sort: 4

### B. Form Schema
**File:** `app/Filament/Admin/Resources/Suppliers/Schemas/SupplierForm.php`

**Fields:**
1. **Kode Pemasok** - Auto-generated, disabled
2. **Nama Pemasok** - Required
3. **Nama Kontak** - Optional
4. **Email** - Optional, validated
5. **Telepon** - Optional
6. **Status** - Required (Aktif/Tidak Aktif), default: Aktif
7. **Alamat** - Optional, textarea
8. **Kota** - Optional
9. **Provinsi** - Optional
10. **Kode Pos** - Optional
11. **Catatan** - Optional, textarea

**Layout:** 3 columns

### C. Table Schema
**File:** `app/Filament/Admin/Resources/Suppliers/Tables/SuppliersTable.php`

**Kolom:**
- Kode (searchable, sortable)
- Nama Pemasok (searchable, sortable)
- Kontak (searchable, toggleable)
- Telepon (searchable, toggleable)
- Email (searchable, toggleable)
- Kota (searchable, toggleable)
- Status (badge dengan color: success/danger, sortable)
- Created at (toggleable, hidden by default)
- Updated at (toggleable, hidden by default)

### D. Pages
**Files:**
- `app/Filament/Admin/Resources/Suppliers/Pages/ListSuppliers.php`
- `app/Filament/Admin/Resources/Suppliers/Pages/CreateSupplier.php`
- `app/Filament/Admin/Resources/Suppliers/Pages/EditSupplier.php`

---

## 4. Update Raw Materials

### A. Form Update
**File:** `app/Filament/Admin/Resources/RawMaterials/Schemas/RawMaterialForm.php`

**Perubahan:**
- Ditambahkan field **Pemasok** (Select)
  - Searchable
  - Preload
  - Dengan opsi "Create New" (quick create)
  - Quick create form mencakup: nama, telepon, email
- Layout diubah menjadi 3 columns
- Semua field diberi label Indonesia

### B. Table Update
**File:** `app/Filament/Admin/Resources/RawMaterials/Tables/RawMaterialsTable.php`

**Perubahan:**
- Ditambahkan kolom **Pemasok** (`supplier.name`)
  - Searchable
  - Sortable
  - Toggleable
- Semua kolom diberi label Indonesia

---

## 5. Update Transactions

### A. Form Update
**File:** `app/Filament/Admin/Resources/Transactions/Schemas/TransactionForm.php`

**Perubahan:**
- Ditambahkan field **Pemasok** (Select)
  - Hanya muncul untuk transaksi tipe "purchase" (pembelian)
  - Required untuk transaksi pembelian
  - Searchable
  - Preload
  - Dengan opsi "Create New" (quick create)
  - Quick create form mencakup: nama, telepon, email, nama kontak

### B. Table Update
**File:** `app/Filament/Admin/Resources/Transactions/Tables/TransactionsTable.php`

**Perubahan:**
- Ditambahkan kolom **Pemasok** (`supplier.name`)
  - Searchable
  - Sortable
  - Toggleable
  - Placeholder: "-" (untuk transaksi penjualan)

---

## 6. Cara Menggunakan

### A. Mengelola Supplier

1. **Membuat Supplier Baru:**
   - Buka menu "Pemasok" di sidebar (grup Master Data)
   - Klik tombol "Tambah Pemasok"
   - Isi form (minimal nama pemasok)
   - Kode akan di-generate otomatis
   - Klik "Simpan"

2. **Edit Supplier:**
   - Klik tombol edit di tabel
   - Update informasi
   - Klik "Simpan"

3. **Menonaktifkan Supplier:**
   - Edit supplier
   - Ubah status menjadi "Tidak Aktif"
   - Supplier tidak akan terhapus dari database

### B. Menambahkan Bahan Baku dengan Supplier

1. **Cara 1: Pilih Supplier yang Ada**
   - Buka form bahan baku
   - Pilih supplier dari dropdown
   - Isi field lainnya
   - Simpan

2. **Cara 2: Buat Supplier Baru (Quick Create)**
   - Buka form bahan baku
   - Klik tombol "+" di field Pemasok
   - Isi form quick create (nama, telepon, email)
   - Supplier baru akan otomatis terpilih
   - Lanjutkan mengisi field lainnya
   - Simpan

### C. Transaksi Pembelian dengan Supplier

1. **Membuat Transaksi Pembelian:**
   - Buka menu "Transaksi"
   - Klik "Tambah Transaksi"
   - Pilih tipe: "Pembelian"
   - Field **Pemasok** akan muncul (required)
   - Pilih supplier atau buat baru dengan quick create
   - Tambahkan bahan baku yang dibeli
   - Simpan

2. **Melihat Transaksi:**
   - Di tabel transaksi, kolom Pemasok akan menampilkan nama supplier
   - Untuk transaksi penjualan, kolom akan menampilkan "-"

---

## 7. Fitur Tambahan

### A. Auto-Generate Kode
- Kode supplier di-generate otomatis dengan format: `SUP-YYYYMMDD-XXXX`
- Contoh: `SUP-20251021-0001`

### B. Quick Create
- Fitur quick create memungkinkan pembuatan supplier baru langsung dari form bahan baku atau transaksi
- Tidak perlu navigasi ke menu Supplier terlebih dahulu

### C. Relasi Data
- Supplier dapat dihapus (soft delete dengan set null)
- Jika supplier dihapus, bahan baku dan transaksi terkait tidak akan terhapus
- Field supplier_id akan di-set menjadi NULL

### D. Filter & Search
- Semua tabel mendukung pencarian supplier
- Kolom supplier dapat di-sort
- Kolom supplier dapat di-toggle (show/hide)

---

## 8. Migration Commands

Untuk menerapkan perubahan database, jalankan perintah berikut:

```bash
php artisan migrate
```

Jika ingin rollback:

```bash
php artisan migrate:rollback --step=3
```

---

## 9. Testing Checklist

### ✅ Supplier CRUD
- [ ] Buat supplier baru
- [ ] Edit supplier
- [ ] Ubah status supplier (aktif/tidak aktif)
- [ ] Hapus supplier
- [ ] Cek auto-generate kode supplier

### ✅ Raw Materials
- [ ] Buat bahan baku dengan memilih supplier yang ada
- [ ] Buat bahan baku dengan quick create supplier baru
- [ ] Edit bahan baku dan ganti supplier
- [ ] Lihat kolom supplier di tabel bahan baku

### ✅ Transactions
- [ ] Buat transaksi pembelian dengan memilih supplier
- [ ] Buat transaksi pembelian dengan quick create supplier
- [ ] Cek field supplier hanya muncul untuk tipe "pembelian"
- [ ] Cek field supplier required untuk pembelian
- [ ] Lihat kolom supplier di tabel transaksi
- [ ] Buat transaksi penjualan (supplier tidak muncul)

### ✅ Relasi & Integritas Data
- [ ] Hapus supplier yang memiliki bahan baku terkait
- [ ] Cek bahan baku masih ada (supplier_id = NULL)
- [ ] Hapus supplier yang memiliki transaksi terkait
- [ ] Cek transaksi masih ada (supplier_id = NULL)

---

## 10. File Summary

### File Baru (11 files):
1. `database/migrations/2025_10_21_030900_create_suppliers_table.php`
2. `database/migrations/2025_10_21_030901_add_supplier_id_to_raw_materials_table.php`
3. `database/migrations/2025_10_21_030902_add_supplier_id_to_transactions_table.php`
4. `app/Models/Supplier.php`
5. `app/Filament/Admin/Resources/Suppliers/SupplierResource.php`
6. `app/Filament/Admin/Resources/Suppliers/Schemas/SupplierForm.php`
7. `app/Filament/Admin/Resources/Suppliers/Tables/SuppliersTable.php`
8. `app/Filament/Admin/Resources/Suppliers/Pages/ListSuppliers.php`
9. `app/Filament/Admin/Resources/Suppliers/Pages/CreateSupplier.php`
10. `app/Filament/Admin/Resources/Suppliers/Pages/EditSupplier.php`
11. `SUPPLIER_FEATURE_IMPLEMENTATION.md` (dokumentasi ini)

### File Dimodifikasi (6 files):
1. `app/Models/RawMaterial.php`
2. `app/Models/Transaction.php`
3. `app/Filament/Admin/Resources/RawMaterials/Schemas/RawMaterialForm.php`
4. `app/Filament/Admin/Resources/RawMaterials/Tables/RawMaterialsTable.php`
5. `app/Filament/Admin/Resources/Transactions/Schemas/TransactionForm.php`
6. `app/Filament/Admin/Resources/Transactions/Tables/TransactionsTable.php`

---

## 11. Catatan Penting

1. **Backward Compatibility:** Field `supplier_id` bersifat nullable, sehingga data lama tetap valid
2. **Data Integrity:** Menggunakan foreign key dengan ON DELETE SET NULL
3. **User Experience:** Quick create memudahkan penambahan supplier tanpa navigasi
4. **Konsistensi:** Semua label menggunakan Bahasa Indonesia
5. **Format Currency:** Tetap menggunakan format "Rp." seperti yang sudah diterapkan sebelumnya

---

## Tanggal Implementasi
21 Oktober 2025

## Developer
Cascade AI Assistant
