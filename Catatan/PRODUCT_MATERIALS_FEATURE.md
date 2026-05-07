# Fitur Komposisi Bahan Baku Produk

## Deskripsi
Sistem ini memungkinkan setiap produk memiliki komposisi bahan baku yang terdefinisi. Ketika produk dijual melalui transaksi penjualan, sistem akan **otomatis mengurangi stok bahan baku** berdasarkan komposisi yang telah ditentukan.

## Fitur Utama

### 1. Komposisi Bahan Baku pada Produk
- Setiap produk dapat memiliki beberapa bahan baku dengan jumlah yang dibutuhkan
- Jumlah bahan baku ditentukan per unit produk
- Dapat dikelola melalui form produk di admin panel

### 2. Pengurangan Stok Otomatis
Ketika transaksi penjualan dengan status "completed" dibuat:
1. Stok produk berkurang sesuai jumlah yang dijual
2. **Stok bahan baku otomatis berkurang** berdasarkan komposisi produk
3. Perhitungan: `jumlah_bahan_baku_dikurangi = quantity_needed × quantity_sold`

## Struktur Database

### Tabel: `product_materials`
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| product_id | bigint | Foreign key ke tabel products |
| raw_material_id | bigint | Foreign key ke tabel raw_materials |
| quantity_needed | decimal(10,2) | Jumlah bahan baku per unit produk |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

## Model dan Relasi

> ⚠️ *DEPRECATED:* fitur `ProductMaterial` telah dihapus. dokumen
> ini hanya untuk arsip. gunakan tabel BOM baru untuk komposisi produk.

### Model `ProductMaterial`
- Relasi `belongsTo` ke `Product`
- Relasi `belongsTo` ke `RawMaterial`

### Model `Product`
- Relasi `hasMany` ke `ProductMaterial` (materials)
- Relasi `belongsToMany` ke `RawMaterial` (rawMaterials)

### Model `RawMaterial`
- Relasi `hasMany` ke `ProductMaterial` (productMaterials)
- Relasi `belongsToMany` ke `Product` (products)

## Cara Penggunaan

### 1. Menambah/Edit Produk dengan Bahan Baku
1. Buka menu **Products** di admin panel
2. Klik **Create** atau **Edit** produk
3. Isi informasi produk (nama, kode, harga, dll)
4. Di section **Komposisi Bahan Baku**, klik **Tambah Bahan Baku**
5. Pilih bahan baku dan masukkan jumlah yang dibutuhkan per unit
6. Simpan produk

### 2. Transaksi Penjualan
1. Buka menu **Transactions**
2. Klik **Create**
3. Pilih tipe transaksi: **Penjualan**
4. Tambah item produk yang dijual
5. Set status: **Selesai** (completed)
6. Simpan transaksi

**Hasil:**
- Stok produk berkurang
- Stok bahan baku otomatis berkurang sesuai komposisi

## Contoh Data Seeder

### Produk: Roti Tawar (1 unit)
- Tepung Terigu: 0.5 kg
- Gula Pasir: 0.1 kg
- Mentega: 0.05 kg
- Susu Cair: 0.2 liter

**Jika menjual 10 unit Roti Tawar:**
- Stok Roti Tawar: -10 unit
- Stok Tepung Terigu: -5 kg
- Stok Gula Pasir: -1 kg
- Stok Mentega: -0.5 kg
- Stok Susu Cair: -2 liter

## File yang Dimodifikasi/Dibuat

### Migration
- `2025_10_17_073204_create_product_materials_table.php`

### Models
- `app/Models/ProductMaterial.php` (baru)
- `app/Models/Product.php` (updated)
- `app/Models/RawMaterial.php` (updated)

### Filament Resources
- `app/Filament/Admin/Resources/Products/Schemas/ProductForm.php` (updated)
- `app/Filament/Admin/Resources/Transactions/Pages/CreateTransaction.php` (updated)
- `app/Filament/Admin/Resources/Transactions/Pages/EditTransaction.php` (updated)

### Seeders
- `database/seeders/ProductMaterialSeeder.php` (baru)
- `database/seeders/DatabaseSeeder.php` (updated)

## Catatan Penting

1. **Stok Bahan Baku**: Pastikan stok bahan baku mencukupi sebelum melakukan penjualan
2. **Status Transaksi**: Pengurangan stok hanya terjadi saat status transaksi "completed"
3. **Validasi**: Sistem tidak memiliki validasi stok minimum saat ini, bisa ditambahkan di masa depan
4. **Transaksi Produksi**: Untuk produksi, bahan baku dikurangi manual melalui form "Penggunaan Bahan Baku"

## Pengembangan Selanjutnya

Fitur yang bisa ditambahkan:
- [ ] Validasi stok bahan baku sebelum penjualan
- [ ] Notifikasi jika stok bahan baku tidak mencukupi
- [ ] Laporan penggunaan bahan baku per produk
- [ ] Kalkulasi biaya produksi otomatis berdasarkan harga bahan baku
- [ ] History perubahan stok bahan baku
