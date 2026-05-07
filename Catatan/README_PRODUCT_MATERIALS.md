# Panduan Lengkap: Sistem Produk dengan Bahan Baku

## 📋 Ringkasan
Sistem ini mengimplementasikan fitur dimana setiap produk memiliki komposisi bahan baku, dan ketika produk dijual, stok bahan baku akan **otomatis berkurang** sesuai dengan komposisi yang telah ditentukan.

## 🎯 Alur Kerja Sistem

```
┌─────────────────┐
│  Buat Produk    │
│  + Bahan Baku   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Produk memiliki │
│   komposisi:    │
│ - Tepung: 0.5kg │
│ - Gula: 0.1kg   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Transaksi Sale  │
│ Jual 10 unit    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│  Sistem Otomatis:           │
│  ✓ Kurangi stok produk: -10 │
│  ✓ Kurangi tepung: -5kg     │
│  ✓ Kurangi gula: -1kg       │
└─────────────────────────────┘
```

## 🗄️ Struktur Data

### Contoh Data Produk
```php
Product: Roti Tawar
├── ID: 1
├── Price: Rp 25,000
├── Stock: 0
└── Materials:
    ├── Tepung Terigu: 0.5 kg/unit
    ├── Gula Pasir: 0.1 kg/unit
    ├── Mentega: 0.05 kg/unit
    └── Susu Cair: 0.2 liter/unit
```

### Contoh Data Bahan Baku
```php
Raw Material: Tepung Terigu
├── ID: 1
├── Stock: 500 kg
├── Unit: kg
├── Price: Rp 12,000/kg
└── Used in Products:
    ├── Roti Tawar (0.5 kg)
    ├── Roti Manis (0.3 kg)
    ├── Kue Kering (0.4 kg)
    └── Cake Coklat (0.6 kg)
```

## 💻 Implementasi Kode

### 1. Relasi Model Product
```php
// app/Models/Product.php
public function materials(): HasMany
{
    return $this->hasMany(ProductMaterial::class);
}

public function rawMaterials(): BelongsToMany
{
    return $this->belongsToMany(RawMaterial::class, 'product_materials')
        ->withPivot('quantity_needed')
        ->withTimestamps();
}
```

### 2. Logika Pengurangan Stok
```php
// app/Filament/Admin/Resources/Transactions/Pages/CreateTransaction.php
if ($transaction->type === 'sale') {
    foreach ($transaction->items as $item) {
        $product = Product::find($item->product_id);
        if ($product) {
            // Kurangi stok produk
            $product->decrement('stock', $item->quantity);
            
            // Kurangi stok bahan baku otomatis
            foreach ($product->materials as $material) {
                $rawMaterial = RawMaterial::find($material->raw_material_id);
                if ($rawMaterial) {
                    $quantityToDeduct = $material->quantity_needed * $item->quantity;
                    $rawMaterial->decrement('stock', $quantityToDeduct);
                }
            }
        }
    }
}
```

## 📊 Contoh Skenario Penggunaan

### Skenario 1: Penjualan Produk
**Input:**
- Transaksi: Sale
- Produk: Roti Tawar
- Quantity: 5 unit
- Status: Completed

**Proses:**
1. Sistem cek komposisi Roti Tawar
2. Hitung kebutuhan bahan baku:
   - Tepung: 0.5 kg × 5 = 2.5 kg
   - Gula: 0.1 kg × 5 = 0.5 kg
   - Mentega: 0.05 kg × 5 = 0.25 kg
   - Susu: 0.2 L × 5 = 1 L

**Output:**
```
Stok Sebelum:
- Roti Tawar: 50 unit
- Tepung: 500 kg
- Gula: 300 kg
- Mentega: 200 kg
- Susu: 100 L

Stok Sesudah:
- Roti Tawar: 45 unit (-5)
- Tepung: 497.5 kg (-2.5)
- Gula: 299.5 kg (-0.5)
- Mentega: 199.75 kg (-0.25)
- Susu: 99 L (-1)
```

### Skenario 2: Produksi Produk
**Input:**
- Transaksi: Production
- Produk: Cake Coklat
- Quantity: 10 unit
- Bahan Baku Manual:
  - Tepung: 6 kg
  - Gula: 3 kg
  - dll.

**Proses:**
1. Kurangi bahan baku sesuai input manual
2. Tambah stok produk

**Catatan:** Pada produksi, bahan baku dikurangi manual melalui form, bukan otomatis dari komposisi.

## 🔧 Cara Menggunakan di Admin Panel

### Menambah Produk dengan Komposisi
1. Login ke admin panel
2. Menu **Products** → **Create**
3. Isi data produk:
   ```
   Nama: Donat Coklat
   Kode: PRD005
   Harga: Rp 5,000
   Stok: 0
   ```
4. Scroll ke **Komposisi Bahan Baku**
5. Klik **Tambah Bahan Baku**
6. Pilih bahan dan jumlah:
   ```
   Bahan Baku: Tepung Terigu
   Jumlah: 0.1
   
   Bahan Baku: Gula Pasir
   Jumlah: 0.05
   
   Bahan Baku: Telur
   Jumlah: 0.02
   ```
7. Klik **Save**

### Melakukan Penjualan
1. Menu **Transactions** → **Create**
2. Pilih:
   ```
   Tipe: Penjualan
   Tanggal: [hari ini]
   Status: Selesai
   ```
3. Tambah Item:
   ```
   Produk: Donat Coklat
   Jumlah: 20
   Harga: 5000
   ```
4. Klik **Save**
5. **Otomatis terjadi:**
   - Stok Donat Coklat: -20
   - Stok Tepung: -2 kg (0.1 × 20)
   - Stok Gula: -1 kg (0.05 × 20)
   - Stok Telur: -0.4 kg (0.02 × 20)

## 📈 Query Database untuk Cek Data

### Cek Komposisi Produk
```sql
SELECT 
    p.name as product_name,
    rm.name as material_name,
    pm.quantity_needed,
    rm.unit
FROM products p
JOIN product_materials pm ON p.id = pm.product_id
JOIN raw_materials rm ON pm.raw_material_id = rm.id
WHERE p.id = 1;
```

### Cek Stok Bahan Baku
```sql
SELECT 
    name,
    stock,
    unit,
    minimum_stock,
    CASE 
        WHEN stock <= minimum_stock THEN 'LOW STOCK'
        ELSE 'OK'
    END as status
FROM raw_materials
ORDER BY stock ASC;
```

### Cek Produk yang Menggunakan Bahan Tertentu
```sql
SELECT 
    p.name as product_name,
    pm.quantity_needed,
    rm.unit
FROM products p
JOIN product_materials pm ON p.id = pm.product_id
JOIN raw_materials rm ON pm.raw_material_id = rm.id
WHERE rm.id = 1  -- Tepung Terigu
ORDER BY pm.quantity_needed DESC;
```

## ⚠️ Hal Penting yang Perlu Diperhatikan

### 1. Validasi Stok
**Saat ini sistem TIDAK memvalidasi stok bahan baku sebelum penjualan.**

Contoh masalah:
```
Stok Tepung: 1 kg
Jual Roti Tawar: 10 unit (butuh 5 kg tepung)
Hasil: Stok Tepung menjadi -4 kg ❌
```

**Solusi yang bisa ditambahkan:**
```php
// Validasi sebelum save
foreach ($product->materials as $material) {
    $needed = $material->quantity_needed * $item->quantity;
    if ($material->rawMaterial->stock < $needed) {
        throw new \Exception("Stok {$material->rawMaterial->name} tidak mencukupi");
    }
}
```

### 2. Status Transaksi
Pengurangan stok **HANYA** terjadi jika status = "completed"
- Status "pending": Tidak ada perubahan stok
- Status "cancelled": Tidak ada perubahan stok

### 3. Edit Transaksi
Jika mengubah status dari "pending" ke "completed", stok akan berkurang.
**Perhatian:** Jika mengubah dari "completed" ke "pending", stok TIDAK dikembalikan otomatis.

## 🚀 Fitur Tambahan yang Bisa Dikembangkan

### 1. Validasi Stok Real-time
```php
// Di TransactionForm
->afterStateUpdated(function ($state, $get) {
    $product = Product::find($get('product_id'));
    $quantity = $get('quantity');
    
    foreach ($product->materials as $material) {
        $needed = $material->quantity_needed * $quantity;
        if ($material->rawMaterial->stock < $needed) {
            // Show warning
        }
    }
})
```

### 2. Kalkulasi Biaya Produksi
```php
public function getProductionCost(): float
{
    $cost = 0;
    foreach ($this->materials as $material) {
        $cost += $material->quantity_needed * $material->rawMaterial->price_per_unit;
    }
    return $cost;
}
```

### 3. Laporan Penggunaan Bahan
```php
// Report: Bahan baku yang paling banyak digunakan
SELECT 
    rm.name,
    SUM(pm.quantity_needed * ti.quantity) as total_used
FROM raw_materials rm
JOIN product_materials pm ON rm.id = pm.raw_material_id
JOIN products p ON pm.product_id = p.id
JOIN transaction_items ti ON p.id = ti.product_id
JOIN transactions t ON ti.transaction_id = t.id
WHERE t.type = 'sale' AND t.status = 'completed'
GROUP BY rm.id
ORDER BY total_used DESC;
```

## 📞 Troubleshooting

### Error: "Undefined relationship materials"
**Penyebab:** Model Product tidak memiliki relasi materials
**Solusi:** Pastikan sudah menambahkan method `materials()` di model Product

### Stok tidak berkurang
**Kemungkinan penyebab:**
1. Status transaksi bukan "completed"
2. Produk tidak memiliki komposisi bahan baku
3. Method `afterCreate()` tidak dipanggil

**Cara cek:**
```php
// Di tinker
$product = Product::find(1);
dd($product->materials); // Harus ada data
```

### Stok menjadi negatif
**Penyebab:** Tidak ada validasi stok minimum
**Solusi:** Tambahkan validasi sebelum decrement stok

## 📝 Changelog

### Version 1.0 (17 Oktober 2025)
- ✅ Tabel product_materials
- ✅ Model ProductMaterial dengan relasi
- ✅ Form input komposisi bahan baku
- ✅ Pengurangan stok otomatis saat penjualan
- ✅ Seeder data contoh
- ✅ Dokumentasi lengkap

### Planned Features
- [ ] Validasi stok bahan baku
- [ ] Notifikasi low stock
- [ ] Kalkulasi biaya produksi
- [ ] Laporan penggunaan bahan baku
- [ ] Export data ke Excel/PDF
