# Sistem Stok Produk Otomatis

## Deskripsi
Sistem ini menghitung stok produk secara otomatis berdasarkan ketersediaan bahan baku. Tidak perlu input stok produk secara manual.

## Cara Kerja

### 1. Perhitungan Stok Otomatis
Stok produk dihitung dengan formula:
```
Stok Tersedia = MIN(Stok Bahan Baku / Jumlah Dibutuhkan)
```

Sistem akan mencari **bottleneck** (bahan baku yang paling terbatas) untuk menentukan berapa banyak produk yang bisa dibuat.

### 2. Contoh Perhitungan

**Produk: Kue Kering Premium**
- Tepung: 0.3 kg per unit → Stok: 100 kg → Bisa buat: 333 unit
- Gula: 0.15 kg per unit → Stok: 50 kg → Bisa buat: 333 unit  
- Telur: 2 butir per unit → Stok: 200 butir → Bisa buat: **100 unit** ← BOTTLENECK
- Mentega: 0.1 kg per unit → Stok: 30 kg → Bisa buat: 300 unit

**Hasil: Stok tersedia = 100 unit** (dibatasi oleh telur)

### 3. Fitur Tambahan

#### Method `canProduce()`
Cek apakah produk bisa diproduksi:
```php
if ($product->canProduce(10)) {
    // Bisa produksi 10 unit
}
```

#### Method `getMissingMaterials()`
Dapatkan daftar bahan yang kurang:
```php
$missing = $product->getMissingMaterials(50);
// Returns array dengan info bahan yang kurang
```

## Penggunaan di Filament

### Form Produk
- **Tidak ada input stok manual**
- Hanya input: Nama, Kode, Deskripsi, Harga
- Tambahkan komposisi bahan baku dengan jumlah yang dibutuhkan
- Stok akan ditampilkan otomatis saat edit produk

### Tabel Produk
- Kolom "Stok Tersedia" menampilkan stok yang dihitung otomatis
- Badge berwarna:
  - 🟢 Hijau: Stok > 10
  - 🟡 Kuning: Stok 1-10
  - 🔴 Merah: Stok = 0

## Keuntungan

1. ✅ **Akurat**: Stok selalu sesuai dengan bahan baku yang tersedia
2. ✅ **Real-time**: Update otomatis saat stok bahan baku berubah
3. ✅ **Mencegah Overselling**: Tidak bisa jual produk jika bahan baku tidak cukup
4. ✅ **Mudah Dikelola**: Cukup kelola stok bahan baku saja

## Testing

Jalankan seeder untuk testing:
```bash
php artisan db:seed --class=ProductWithMaterialsSeeder
```

Seeder akan membuat:
- 4 Bahan Baku (Tepung, Gula, Telur, Mentega)
- 2 Produk (Kue Kering Premium, Roti Tawar)
- Komposisi bahan untuk setiap produk

## Catatan Penting

⚠️ **Kolom `stock` di tabel `products` sudah dihapus**. Semua perhitungan stok menggunakan accessor `available_stock` yang dihitung real-time dari bahan baku.
