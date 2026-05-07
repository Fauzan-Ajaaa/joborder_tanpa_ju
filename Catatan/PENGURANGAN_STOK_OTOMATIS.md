# Sistem Pengurangan Stok Otomatis

## Deskripsi
Sistem ini otomatis mengurangi stok bahan baku saat terjadi transaksi penjualan produk. Tidak perlu pengurangan manual.

## Cara Kerja

### 1. Saat Transaksi Dibuat
Ketika `TransactionItem` dibuat (produk terjual):
1. Sistem mengambil komposisi bahan baku dari produk
2. Menghitung jumlah bahan baku yang dibutuhkan: `quantity_needed × quantity_sold`
3. Otomatis mengurangi stok setiap bahan baku

### 2. Saat Transaksi Dihapus
Ketika `TransactionItem` dihapus (pembatalan):
1. Sistem mengembalikan stok bahan baku
2. Stok produk otomatis bertambah kembali

## Contoh Kasus

### Sebelum Transaksi
**Bahan Baku:**
- Tepung: 100 kg
- Gula: 50 kg
- Telur: 200 butir
- Mentega: 30 kg

**Produk: Kue Kering Premium**
- Komposisi per unit:
  - Tepung: 0.3 kg
  - Gula: 0.15 kg
  - Telur: 2 butir
  - Mentega: 0.1 kg
- **Stok tersedia: 100 unit**

### Transaksi: Jual 10 Unit Kue Kering

**Pengurangan Otomatis:**
- Tepung: 100 - (0.3 × 10) = **97 kg**
- Gula: 50 - (0.15 × 10) = **48.5 kg**
- Telur: 200 - (2 × 10) = **180 butir**
- Mentega: 30 - (0.1 × 10) = **29 kg**

**Stok Produk Setelah Transaksi:**
- Kue Kering Premium: **90 unit** (dihitung otomatis dari bahan baku tersisa)

## Fitur Keamanan

### 1. Validasi Stok
Method `reduceStock()` di model `RawMaterial`:
```php
public function reduceStock(float $quantity): bool
{
    if ($this->stock < $quantity) {
        return false; // Stok tidak cukup
    }
    
    $this->stock -= $quantity;
    $this->save();
    
    return true;
}
```

### 2. Pengecekan Sebelum Transaksi
Gunakan method `canProduce()` di model `Product`:
```php
if (!$product->canProduce($quantity)) {
    // Tampilkan error: stok tidak cukup
    $missing = $product->getMissingMaterials($quantity);
    // Tampilkan bahan yang kurang
}
```

## Alur Lengkap

```
[Transaksi Dibuat]
    ↓
[TransactionItem::created event]
    ↓
[Ambil data produk & quantity]
    ↓
[Loop setiap material produk]
    ↓
[Hitung: quantity_needed × quantity_sold]
    ↓
[RawMaterial::reduceStock()]
    ↓
[Stok bahan baku berkurang]
    ↓
[Stok produk otomatis update (via accessor)]
```

## Keuntungan

1. ✅ **Otomatis**: Tidak perlu input manual
2. ✅ **Akurat**: Stok selalu sinkron dengan transaksi
3. ✅ **Real-time**: Update langsung saat transaksi
4. ✅ **Reversible**: Bisa dikembalikan jika transaksi dibatalkan
5. ✅ **Traceable**: Semua perubahan stok tercatat

## Catatan Penting

⚠️ **Minimum Stock dihapus**: Sistem tidak lagi menggunakan minimum stock. Fokus pada stok aktual yang tersedia.

⚠️ **Validasi di Filament**: Sebaiknya tambahkan validasi di form transaksi untuk cek ketersediaan stok sebelum menyimpan.

## Testing

### Test Manual
1. Cek stok bahan baku awal
2. Buat transaksi penjualan produk
3. Cek stok bahan baku berkurang otomatis
4. Cek stok produk tersedia berkurang
5. Hapus transaksi
6. Cek stok kembali seperti semula

### Test dengan Seeder
```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=ProductWithMaterialsSeeder
```

Kemudian buat transaksi di admin panel dan lihat perubahan stok.
