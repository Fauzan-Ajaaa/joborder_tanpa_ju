# Perubahan: Bahan Penolong Tidak Menambah BOP

## Tanggal: 6 April 2026

## Keputusan Bisnis
Bahan penolong (auxiliary materials) **TIDAK menambah BOP** dalam perhitungan job order.
- Bahan penolong hanya sebagai **rincian resep** di BOM
- BOM hanya mencatat **qty dan satuan** (TIDAK ADA HARGA/NOMINAL)
- Pembelian bahan penolong tetap dijurnal ke Persediaan
- Tidak ada jurnal pemakaian bahan penolong

## Perubahan yang Dilakukan

### 1. JobOrderCalculationService.php
**Method: `calculateBOP()`**
- ❌ DIHAPUS: Perhitungan biaya bahan penolong dari BOM
- ✅ SEKARANG: BOP hanya dari `POR × durasi jam`

```php
// SEBELUM: BOP = POR × durasi + biaya bahan penolong
// SEKARANG: BOP = POR × durasi (saja)
return $bopFromPor; // tanpa + $auxiliaryCost
```

### 2. JournalService.php
**Method: `createAuxiliaryMaterialJournalForJobStart()`**
- ❌ DISABLED: Tidak ada jurnal pemakaian bahan penolong saat mulai job
- Hanya return kosong

```php
public function createAuxiliaryMaterialJournalForJobStart(JobOrder $jobOrder): void
{
    // Bahan penolong tidak ada jurnal pemakaian
    return;
}
```

### 3. PurchaseController.php
**Method: `approve()`**
- ❌ DIHAPUS: Entry ke `biaya_overhead` saat approve PO bahan penolong
- ✅ SEKARANG: Hanya update status ke 'approved'
- Jurnal pembelian tetap ada (ke Persediaan)

```php
// SEBELUM: Approve → Entry biaya_overhead → Jurnal
// SEKARANG: Approve → (tidak ada entry biaya_overhead)
```

## Jurnal yang Masih Ada

### Pembelian Bahan Penolong (saat buat PO)
```
Debit: Pers. Bahan Penolong - [nama]  Rp xxx
Debit: PPN Masukan                     Rp xxx
Debit: Beban Transport (jika FOB)      Rp xxx
Credit: Kas/Hutang                     Rp xxx
```

## Jurnal yang TIDAK ADA Lagi

### ❌ Pemakaian Bahan Penolong (saat mulai job)
```
TIDAK ADA LAGI
```

### ❌ Alokasi BOP BP (saat selesai job)
```
TIDAK ADA LAGI
```

### ❌ Entry biaya_overhead (saat approve PO)
```
TIDAK ADA LAGI
```

## Dampak ke Sistem

1. **BOM**: Bahan penolong hanya rincian resep (qty + satuan)
2. **Job Order**: BOP tidak termasuk biaya bahan penolong
3. **HPP**: Lebih rendah karena BOP lebih kecil
4. **Persediaan**: Tetap tercatat saat pembelian
5. **Biaya Overhead**: Tidak ada entry untuk bahan penolong

## Testing Checklist

- [ ] Buat PO bahan penolong → Cek jurnal pembelian (harus ada)
- [ ] Approve PO → Cek biaya_overhead (harus TIDAK ada entry)
- [ ] Mulai job order → Cek jurnal pemakaian BP (harus TIDAK ada)
- [ ] Selesai job order → Cek jurnal BOP (harus TIDAK ada BP)
- [ ] Cek HPP job order → Harus lebih rendah (tanpa biaya BP)
- [ ] Cek BOM → Bahan penolong hanya qty dan satuan (tanpa harga)

## Files Modified

1. `app/Services/JobOrderCalculationService.php`
2. `app/Services/JournalService.php`
3. `app/Http/Controllers/PurchaseController.php`
