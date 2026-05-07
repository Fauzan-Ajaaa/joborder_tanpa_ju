# Master Data Bahan Penolong (Auxiliary Materials)

## 📋 Deskripsi
Master Data Bahan Penolong adalah modul untuk mengelola bahan-bahan penolong yang digunakan dalam proses produksi. Struktur dan fiturnya sama persis seperti Bahan Baku (Raw Materials) namun dengan fokus pada bahan penolong.

## 🎯 Fitur Utama

### 1. **Data Management**
- **CRUD Operations**: Create, Read, Update, Delete
- **Auto-generated Code**: Format BP-0000001, BP-0000002, dst
- **Multi-unit Support**: Konversi satuan yang fleksibel
- **Supplier Management**: Relasi dengan data supplier
- **Stock Management**: Tracking stok real-time
- **Price Management**: Harga per satuan

### 2. **COA Integration**
- **Automatic COA Creation**: Saat tambah bahan penolong, otomatis buat COA
- **Parent COA**: "Persediaan Bahan Penolong" (Code: 115)
- **Sequential Codes**: 1151, 1152, 1153, dst
- **Database Relations**: Relasi dua arah antara bahan penolong dan COA

### 3. **Unit Conversion**
- **Base Unit**: Satuan dasar (Kg, Gram, Liter, Pack, Box, dll)
- **Recipe Unit**: Satuan untuk resep (opsional)
- **Conversion Factor**: Faktor konversi antara satuan
- **Multi-conversion**: Banyak konversi satuan yang didukung

## 🔧 Struktur Database

### Tabel `auxiliary_materials`
```sql
CREATE TABLE auxiliary_materials (
    id CHAR(36) PRIMARY KEY,                    -- UUID
    code VARCHAR(255) UNIQUE NOT NULL,            -- Kode unik BP-0000001
    name VARCHAR(255) NOT NULL,                  -- Nama bahan penolong
    description TEXT NULL,                          -- Deskripsi
    unit VARCHAR(255) NOT NULL,                 -- Satuan dasar
    recipe_unit VARCHAR(255) NULL,               -- Satuan resep
    recipe_conversion_factor DECIMAL(8,6) NULL, -- Faktor konversi resep
    stock DECIMAL(10,2) DEFAULT 0,            -- Stok saat ini
    price_per_unit DECIMAL(12,2) DEFAULT 0,     -- Harga per satuan
    supplier_id BIGINT UNSIGNED NULL,               -- Foreign key ke suppliers
    chart_of_account_id BIGINT UNSIGNED NULL,       -- Foreign key ke COA
    created_at TIMESTAMP NULL,                     -- Created timestamp
    updated_at TIMESTAMP NULL                      -- Updated timestamp
);
```

### Tabel `auxiliary_material_unit_conversions`
```sql
CREATE TABLE auxiliary_material_unit_conversions (
    id CHAR(36) PRIMARY KEY,                    -- UUID
    auxiliary_material_id CHAR(36) NOT NULL,       -- Foreign key
    from_unit VARCHAR(255) NOT NULL,              -- Satuan asal
    to_unit VARCHAR(255) NOT NULL,                -- Satuan tujuan
    factor DECIMAL(8,6) NOT NULL,                -- Faktor konversi
    created_at TIMESTAMP NULL,                     -- Created timestamp
    updated_at TIMESTAMP NULL,                      -- Updated timestamp
);
```

## 📁 File Structure

### 1. **Models**
```
app/
├── Models/
│   ├── AuxiliaryMaterial.php              # Model utama bahan penolong
│   └── AuxiliaryMaterialUnitConversion.php  # Model konversi satuan
```

### 2. **Controllers**
```
app/
├── Http/
│   └── Controllers/
│       └── AuxiliaryMaterialController.php  # Controller CRUD
```

### 3. **Views**
```
resources/
├── views/
│   └── auxiliary-materials/
│       ├── index.blade.php     # List data
│       ├── create.blade.php    # Form tambah
│       ├── show.blade.php      # Detail data
│       └── edit.blade.php     # Form edit
```

### 4. **Migrations**
```
database/
├── migrations/
│   ├── 2026_02_05_130002_create_auxiliary_materials_table_fixed.php
│   └── 2026_02_05_130003_create_auxiliary_material_unit_conversions_table_fixed.php
```

### 5. **Seeders**
```
database/
└── seeders/
    └── AuxiliaryMaterialCOASeeder.php  # Parent COA "Persediaan Bahan Penolong"
```

## 🚀 Cara Penggunaan

### 1. **Setup Parent COA**
```bash
php artisan db:seed --class=AuxiliaryMaterialCOASeeder
```

### 2. **Akses Menu**
- Login ke aplikasi
- Klik menu **Master Data** → **Bahan Penolong**

### 3. **Tambah Bahan Penolong**
1. Klik tombol **"Tambah Bahan Penolong"**
2. Isi form:
   - **Nama**: Nama bahan penolong (wajib)
   - **Kode**: Auto-generated (BP-0000001)
   - **Deskripsi**: Keterangan opsional
   - **Satuan Dasar**: Pilih dari dropdown (Kg, Gram, Liter, Pack, Box, dll)
   - **Harga per Satuan**: Harga beli
   - **Stok Awal**: Stok awal
   - **Supplier**: Pilih supplier (opsional)
   - **Satuan Resep**: Satuan untuk resep (opsional)
   - **Faktor Konversi**: 1 satuan dasar = ? satuan resep
3. Tambah konversi multi-satuan (opsional)
4. Klik **"Simpan"**

### 4. **Hasil Otomatis**
- ✅ Bahan penolong tersimpan
- ✅ COA otomatis dibuat (1151, 1152, dst)
- ✅ Relasi database tersimpan
- ✅ Flash message: "Bahan penolong berhasil ditambahkan (COA: 1151 - Persediaan Bahan Penolong Nama)"

## 📋 Fitur Lainnya

### 1. **List View**
- **Pagination**: 15 data per halaman
- **Search**: Filter berdasarkan nama/kode
- **Sorting**: Sort berdasarkan kolom
- **Quick Actions**: Lihat, Edit, Hapus
- **COA Info**: Menampilkan kode dan nama COA

### 2. **Detail View**
- **Informasi Umum**: Kode, nama, deskripsi, supplier
- **Informasi Stok**: Stok saat ini, harga, satuan
- **Konversi Multi-Satuan**: Tabel konversi yang tersedia
- **COA Information**: Badge COA dengan kode dan nama

### 3. **Edit Form**
- **Pre-populated**: Form terisi data existing
- **Unit Conversion**: Edit/hapus/tambah konversi
- **Validation**: Validasi data input
- **Update Logic**: Konversi satuan otomatis

### 4. **Unit Options**
```php
const UNIT_OPTIONS = [
    'Kg' => 'Kilogram (Kg)',
    'Gram' => 'Gram (g)',
    'Milligram' => 'Milligram (mg)',
    'Liter' => 'Liter (L)',
    'Pack' => 'Pack',
    'Box' => 'Box',
    'Bottle' => 'Bottle',
    'Can' => 'Can',
];
```

## 🔍 COA Integration

### 1. **Parent COA Structure**
```
Code: 115
Name: Persediaan Bahan Penolong
Group: Current Assets
Normal Balance: Debit
```

### 2. **Child COA Generation**
```
Bahan: "Kemasan Plastik"
COA: "1151 - Persediaan Bahan Penolong Kemasan Plastik"

Bahan: "Label Produk"
COA: "1152 - Persediaan Bahan Penolong Label Produk"

Bahan: "Dus Kardus"
COA: "1153 - Persediaan Bahan Penolong Dus Kardus"
```

### 3. **Database Relations**
```php
// Model AuxiliaryMaterial
public function chartOfAccount(): BelongsTo
{
    return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
}

// Model ChartOfAccount
public function auxiliaryMaterials(): HasMany
{
    return $this->hasMany(AuxiliaryMaterial::class, 'chart_of_account_id');
}
```

## ✅ Validasi

### 1. **Input Validation**
- **Required Fields**: Nama, satuan, harga, stok
- **Unique Code**: Kode tidak boleh duplikat
- **Numeric Fields**: Harga dan stok harus angka positif
- **Foreign Keys**: Supplier harus ada di database

### 2. **Business Logic**
- **Stock Management**: Tidak bisa kurang dari 0 saat pengurangan
- **Price Validation**: Harga harus >= 0
- **Unit Conversion**: Faktor konversi harus > 0
- **Transaction Safety**: Semua operasi dalam DB transaction

### 3. **Error Handling**
- **User Messages**: Pesan error yang jelas
- **Logging**: Log untuk debugging
- **Rollback**: Transaction rollback pada error
- **Graceful Degradation**: Fallback jika COA gagal dibuat

## 🎯 Keunggulan dari Bahan Baku

### 1. **Kode Prefix**
- **Bahan Baku**: BB- (Bahan Baku)
- **Bahan Penolong**: BP- (Bahan Penolong)

### 2. **COA Parent**
- **Bahan Baku**: Code 114 - "Persediaan Bahan Baku"
- **Bahan Penolong**: Code 115 - "Persediaan Bahan Penolong"

### 3. **Unit Options**
- **Bahan Baku**: Kg, Gram, Milligram, Liter
- **Bahan Penolong**: Kg, Gram, Milligram, Liter, Pack, Box, Bottle, Can

### 4. **Use Case**
- **Bahan Baku**: Bahan langsung untuk produksi (makanan, minuman)
- **Bahan Penolong**: Bahan pendukung produksi (kemasan, label, dll)

## 🚀 Routes

```php
// Resource Routes (CRUD)
Route::resource('auxiliary-materials', AuxiliaryMaterialController::class);

// Additional Routes
Route::patch('/auxiliary-materials/{auxiliaryMaterial}/unit', 
    [AuxiliaryMaterialController::class, 'updateUnit'])
    ->name('auxiliary-materials.update-unit');
```

## 📊 Contoh Implementasi

### Input Form:
```
Nama: Kemasan Plastik
Kode: BP-000001 (auto)
Satuan: Pack
Harga: Rp 500
Stok: 100
Supplier: PT. Kemas Indah
```

### Output:
```
✅ Bahan penolong berhasil ditambahkan
COA: 1151 - Persediaan Bahan Penolong Kemasan Plastik
```

### Database Result:
```sql
INSERT INTO auxiliary_materials (
    id, code, name, unit, price_per_unit, stock, 
    supplier_id, chart_of_account_id, created_at, updated_at
) VALUES (
    'uuid-here', 'BP-000001', 'Kemasan Plastik', 'Pack', 
    500.00, 100, 5, 123, NOW(), NOW()
);

INSERT INTO chart_of_accounts (
    id, code, account_name, parent_code, 
    account_group_name, normal_balance_position, 
    opening_balance, is_active, created_at, updated_at
) VALUES (
    456, '1151', 'Persediaan Bahan Penolong Kemasan Plastik', '115',
    'Current Assets', 'debit', 0.00, 1, NOW(), NOW()
);
```

## 🎉 **Selesai!**

Master Data Bahan Penolong sudah 100% siap digunakan dengan:

✅ **Complete CRUD Operations**
✅ **Automatic COA Integration** 
✅ **Multi-unit Conversion Support**
✅ **Database Relations**
✅ **User-friendly Interface**
✅ **Navigation Integration**
✅ **Comprehensive Documentation**

**Sistem siap untuk mengelola bahan penolong dengan fitur yang sama lengkapnya seperti Bahan Baku!** 🚀
