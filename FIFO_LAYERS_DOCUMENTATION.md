# FIFO Layers - Dokumentasi Posting Stock

## Konsep

Untuk metode FIFO, saldo akhir bulan terdiri dari **multiple batches** yang perlu dipindahkan ke bulan berikutnya agar ditampilkan di kolom SALDO.

## Contoh

### Bulan Maret 2026 (FIFO):
```
Tanggal    | Tipe  | Masuk | Keluar | Saldo (Qty) | Saldo (Value)
-----------|-------|-------|--------|-------------|---------------
01/04/2026 | Awal  |   -   |   -    |      0      |       0
15/04/2026 | Masuk |   3   |   -    |      3      | Rp 94.595
15/04/2026 | Masuk |   2   |   -    |      5      | Rp 157.658
```

**Kolom SALDO Akhir Maret:**
- Batch 1: 3 ekor @ Rp 31.532 = Rp 94.595
- Batch 2: 2 ekor @ Rp 31.532 = Rp 63.063
- **Total: 5 ekor @ Rp 157.658**

### Bulan April 2026 (Setelah Posting Maret):
```
Tanggal    | Tipe  | Masuk | Keluar | Saldo (Qty) | Saldo (Value)
-----------|-------|-------|--------|-------------|---------------
01/04/2026 | Awal  |   -   |   -    |      3      | Rp 94.595     ← Batch 1 dari Maret
01/04/2026 | Awal  |   -   |   -    |      5      | Rp 157.658    ← Batch 1+2 dari Maret
15/04/2026 | Masuk |   3   |   -    |      8      | Rp 252.253
```

## Implementasi

### 1. Database Schema

Tambah kolom `beginning_fifo_layers` dan `ending_fifo_layers` di tabel `material_stock_balances`:

```php
$table->json('beginning_fifo_layers')->nullable();
$table->json('ending_fifo_layers')->nullable();
```

Format JSON:
```json
[
  {"qty": 3, "price": 31532},
  {"qty": 2, "price": 31532}
]
```

### 2. Posting Logic (StockPostingController)

```php
// Simpan ending_fifo_layers dari periode ini
MaterialStockBalance::updateOrCreate(
    ['material_type' => 'RawMaterial', 'material_id' => $material->id, 'period' => $period],
    array_merge($stats, ['is_posted' => true])
);

// Pindahkan ke beginning_fifo_layers bulan berikutnya
MaterialStockBalance::updateOrCreate(
    ['material_type' => 'RawMaterial', 'material_id' => $material->id, 'period' => $nextPeriod],
    [
        'beginning_qty' => $stats['ending_qty'],
        'beginning_value' => $stats['ending_value'],
        'beginning_fifo_layers' => $stats['ending_fifo_layers'], // ← FIFO batches
        'is_posted' => false,
    ]
);
```

### 3. getPeriodStats (RawMaterial/AuxiliaryMaterial)

```php
// Hitung saldo akhir dari batch yang tersisa
$endingValue = 0.0;
foreach ($batches as $batch) {
    $endingValue += $batch['qty'] * $batch['price'];
}

return [
    'ending_qty' => $endingQty,
    'ending_value' => $endingValue,
    'ending_fifo_layers' => json_encode($batches), // ← Simpan batches
];
```

### 4. Stock Card Display (RawMaterialController)

```php
if ($balance && $balance->beginning_fifo_layers) {
    // Load FIFO layers dari periode sebelumnya
    $batches = json_decode($balance->beginning_fifo_layers, true) ?: [];
} elseif ($openingQty > 0) {
    // Fallback: single batch
    $batches[] = ['qty' => $openingQty, 'price' => $openingTotal / $openingQty];
}
```

## Hasil

- **FIFO**: Multiple baris di kolom SALDO untuk saldo awal (sesuai batches)
- **Average**: Single baris di kolom SALDO untuk saldo awal (harga rata-rata)
- **Tidak ada transaksi yang dipindahkan**, hanya saldo akhir di kolom SALDO

## Migration

Jalankan migration:
```bash
php artisan migrate
```

File: `database/migrations/2026_04_15_100000_add_fifo_layers_to_material_stock_balances.php`
