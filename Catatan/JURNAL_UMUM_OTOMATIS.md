# 📒 Sistem Jurnal Umum Otomatis

## Deskripsi
Sistem jurnal umum yang secara otomatis mencatat transaksi dari:
- **Penjualan** (Sales)
- **Pembelian** (Purchase)
- **Penggajian** (Payroll/BTKL)
- **Biaya Overhead** (BOP)

## Fitur Utama

### ✅ 1. Jurnal Otomatis
- Jurnal dibuat otomatis saat ada transaksi baru
- Nomor jurnal auto-generate (format: JU-SALES-YYYYMMDD-0001)
- Debit dan Kredit otomatis balance
- Status: Posted (langsung tercatat)

### ✅ 2. Filter Periode
- Filter berdasarkan tanggal (dari - sampai)
- Default: Bulan berjalan
- Indicator filter aktif

### ✅ 3. Filter Sumber Transaksi
- Penjualan (badge hijau)
- Pembelian (badge kuning)
- Penggajian (badge biru)
- Biaya Overhead (badge merah)

### ✅ 4. Integrasi dengan COA
- Menggunakan master Chart of Accounts yang sudah ada
- Mapping otomatis ke akun yang sesuai

## Mapping Akun

### Penjualan (Sales)
```
Debit:  1-1000 (Kas) atau 1-2000 (Piutang Usaha)
Credit: 4-1000 (Pendapatan Penjualan)
```

### Pembelian (Purchase)
```
Debit:  1-3000 (Persediaan Bahan Baku)
Credit: 1-1000 (Kas) atau 2-1000 (Hutang Usaha)
```

### Penggajian (Payroll/BTKL)
```
Debit:  5-1000 (Beban Gaji)
Credit: 1-1000 (Kas)
```

### Biaya Overhead (BOP)
```
Debit:  5-9000 (Beban Lain-lain)
Credit: 1-1000 (Kas)
```

## Cara Menggunakan

### 1. Lihat Jurnal Umum
```
Menu: Laporan → Jurnal Umum
- Lihat semua jurnal yang sudah tercatat
- Filter berdasarkan periode (default: bulan berjalan)
- Filter berdasarkan sumber transaksi
- Filter berdasarkan status
- Lihat total debit dan kredit
- Copy nomor jurnal
```

### 2. Generate Jurnal Manual (via Tinker)
```php
// Dari Penjualan
$sales = SalesTransaction::find(1);
$journal = app(\App\Services\JournalService::class)->createJournalFromSales($sales);

// Dari Pembelian
$purchase = Transaction::find(1);
$journal = app(\App\Services\JournalService::class)->createJournalFromPurchase($purchase);

// Dari Penggajian
$payroll = Payroll::find(1);
$journal = app(\App\Services\JournalService::class)->createJournalFromPayroll($payroll);

// Dari Biaya Overhead
$overhead = BiayaOverhead::find(1);
$journal = app(\App\Services\JournalService::class)->createJournalFromOverhead($overhead);
```

### 3. Lihat Detail Jurnal
```
Klik "Lihat Detail" pada baris jurnal
- Lihat nomor jurnal
- Lihat tanggal transaksi
- Lihat sumber transaksi
- Lihat detail debit/kredit per akun
```

## Struktur Database

### Tabel: journal_entries
```sql
- id
- journal_number (unique)
- transaction_date
- source_type (sales/purchase/payroll/overhead)
- source_id
- description
- total_debit
- total_credit
- status (draft/posted/void)
- created_at
- updated_at
```

### Tabel: journal_entry_items
```sql
- id
- journal_entry_id (FK)
- chart_of_account_id (FK)
- description
- debit
- credit
- created_at
- updated_at
```

## Contoh Jurnal

### Contoh 1: Penjualan Tunai Rp 100.000
```
No. Jurnal: JU-SALES-20251103-0001
Tanggal: 03 Nov 2025
Sumber: Penjualan

Debit:  Kas (1-1000)                    Rp 100.000
Credit: Pendapatan Penjualan (4-1000)   Rp 100.000
```

### Contoh 2: Pembelian Bahan Baku Kredit Rp 500.000
```
No. Jurnal: JU-PURCHASE-20251103-0001
Tanggal: 03 Nov 2025
Sumber: Pembelian

Debit:  Persediaan Bahan Baku (1-3000) Rp 500.000
Credit: Hutang Usaha (2-1000)           Rp 500.000
```

### Contoh 3: Gaji BTKL Rp 50.000
```
No. Jurnal: JU-PAYROLL-20251103-0001
Tanggal: 03 Nov 2025
Sumber: Penggajian

Debit:  Beban Gaji (5-1000)             Rp 50.000
Credit: Kas (1-1000)                    Rp 50.000
```

### Contoh 4: Biaya Listrik Rp 200.000
```
No. Jurnal: JU-BOP-20251103-0001
Tanggal: 03 Nov 2025
Sumber: Biaya Overhead

Debit:  Beban Lain-lain (5-9000)        Rp 200.000
Credit: Kas (1-1000)                    Rp 200.000
```

## File-file Penting

### Models
- `app/Models/JournalEntry.php`
- `app/Models/JournalEntryItem.php`

### Services
- `app/Services/JournalService.php`

### Migrations
- `database/migrations/*_create_journal_entries_table.php`
- `database/migrations/*_create_journal_entry_items_table.php`

### Filament Resources
- `app/Filament/Admin/Resources/JournalEntries/JournalEntryResource.php`
- `app/Filament/Admin/Resources/JournalEntries/Tables/JournalEntriesTable.php`

## Tips & Best Practices

### ✅ DO:
1. Selalu cek balance (debit = credit) sebelum post jurnal
2. Gunakan filter periode untuk laporan bulanan
3. Review jurnal secara berkala
4. Backup data jurnal secara rutin

### ❌ DON'T:
1. Jangan edit jurnal yang sudah posted
2. Jangan hapus jurnal tanpa alasan jelas
3. Jangan ubah mapping akun tanpa konsultasi

## Troubleshooting

**Q: Jurnal tidak muncul?**
A: Pastikan transaksi sudah tersimpan dan status = 'posted'

**Q: Total debit tidak sama dengan credit?**
A: Cek mapping akun di JournalService, pastikan semua akun ada

**Q: Akun tidak ditemukan?**
A: Jalankan seeder COA: `php artisan db:seed --class=ChartOfAccountSeeder`

**Q: Error saat generate jurnal?**
A: Pastikan relasi model sudah benar (employee, product, dll)

## Roadmap

### Phase 2 (Completed)
- [x] Auto-generate jurnal via Observer ✅
- [ ] Export jurnal ke PDF
- [ ] Jurnal penyesuaian
- [ ] Jurnal penutup
- [ ] Buku besar otomatis
- [ ] Neraca saldo
- [ ] Laporan laba rugi dari jurnal

## Observer Auto-Generate

Jurnal akan otomatis dibuat saat:
- **Penjualan dibuat** → `SalesTransactionObserver`
- **Pembelian dibuat** → `TransactionObserver`
- **Penggajian dibuat** → `PayrollObserver`
- **Biaya Overhead dibuat** → `BiayaOverheadObserver`

Tidak perlu generate manual, sistem akan otomatis mencatat jurnal!

## Support
Untuk pertanyaan atau issue, hubungi tim development.

---
**Last Updated:** 03 November 2025
**Version:** 1.0.0
