# Fitur Akuntansi - Sistem Manajemen Manufaktur

## Overview

Aplikasi ini telah dilengkapi dengan modul akuntansi lengkap yang terintegrasi dengan sistem manufaktur. Semua fitur akuntansi menggunakan prinsip double-entry bookkeeping dan mengikuti standar akuntansi Indonesia.

## Modul yang Tersedia

### 1. Manajemen Karyawan (Employees)
**Lokasi Menu:** Employees

**Fitur:**
- CRUD data karyawan lengkap
- Auto-generate nomor karyawan (EMP-XXXX)
- Informasi personal (nama, email, telepon, alamat)
- Informasi pekerjaan (jabatan, departemen, tanggal masuk)
- Gaji pokok
- Status karyawan (Active, Inactive, Terminated)

**Use Case:**
- Mengelola database karyawan perusahaan
- Basis data untuk proses penggajian
- Tracking status karyawan

---

### 2. Penggajian (Payroll)
**Lokasi Menu:** Payrolls

**Fitur:**
- Proses penggajian per karyawan
- Auto-generate nomor payroll (PAY-YYYYMM-XXXX)
- Periode gaji (tanggal awal - akhir)
- Komponen gaji:
  - **Gaji Pokok** (auto-fill dari data karyawan)
  - **Tunjangan** (allowances)
  - **Lembur** (overtime)
  - **Bonus**
  - **Potongan** (deductions)
  - **Pajak** (tax)
  - **Gaji Bersih** (net salary - auto-calculated)
- Status workflow: Draft → Approved → Paid
- Tanggal pembayaran
- Catatan tambahan

**Formula Gaji Bersih:**
```
Net Salary = Base Salary + Allowances + Overtime + Bonuses - Deductions - Tax
```

**Use Case:**
- Proses penggajian bulanan
- Perhitungan gaji dengan berbagai komponen
- Tracking pembayaran gaji
- Laporan penggajian

---

### 3. Pengeluaran/Beban (Expenses)
**Lokasi Menu:** Expenses

**Fitur:**
- Pencatatan semua pengeluaran perusahaan
- Auto-generate nomor expense (EXP-YYYYMMDD-XXXX)
- Link ke Chart of Accounts
- Kategori beban (operasional, marketing, produksi, dll)
- Informasi vendor/supplier
- Metode pembayaran
- Nomor referensi/invoice
- Upload bukti pembayaran (attachment)
- Status approval: Pending → Approved → Paid / Rejected
- Catatan tambahan

**Use Case:**
- Mencatat beban listrik, air, telepon
- Mencatat biaya marketing
- Mencatat biaya operasional
- Tracking approval pengeluaran
- Audit trail pengeluaran

---

### 4. Chart of Accounts (Bagan Akun)
**Lokasi Menu:** Chart Of Accounts

**Fitur:**
- Struktur akun akuntansi lengkap
- 5 tipe akun utama:
  - **Asset** (Aset)
  - **Liability** (Kewajiban)
  - **Equity** (Ekuitas)
  - **Revenue** (Pendapatan)
  - **Expense** (Beban)
- Hierarki parent-child accounts
- Kode akun unik
- Saldo akun
- Status aktif/non-aktif

**Struktur Default:**
```
1-0000 ASET
  ├── 1-1000 Kas
  ├── 1-1100 Bank
  ├── 1-2000 Piutang Usaha
  ├── 1-3000 Persediaan Bahan Baku
  ├── 1-3100 Persediaan Produk Jadi
  ├── 1-4000 Peralatan
  └── 1-4100 Akumulasi Penyusutan Peralatan

2-0000 KEWAJIBAN
  ├── 2-1000 Hutang Usaha
  ├── 2-2000 Hutang Gaji
  └── 2-3000 Hutang Pajak

3-0000 EKUITAS
  ├── 3-1000 Modal
  └── 3-2000 Laba Ditahan

4-0000 PENDAPATAN
  ├── 4-1000 Pendapatan Penjualan
  └── 4-2000 Pendapatan Lain-lain

5-0000 BEBAN
  ├── 5-1000 Beban Gaji
  ├── 5-2000 Beban Bahan Baku
  ├── 5-3000 Beban Listrik
  ├── 5-4000 Beban Air
  ├── 5-5000 Beban Telepon & Internet
  ├── 5-6000 Beban Transportasi
  ├── 5-7000 Beban Pemeliharaan
  ├── 5-8000 Beban Penyusutan
  └── 5-9000 Beban Lain-lain
```

**Use Case:**
- Basis struktur akuntansi perusahaan
- Klasifikasi transaksi keuangan
- Pembuatan laporan keuangan
- Analisis keuangan per kategori

---

### 5. Jurnal Umum (Journal Entries)
**Lokasi Menu:** (Akan ditambahkan)

**Fitur:**
- Double-entry bookkeeping
- Auto-generate nomor jurnal
- Link ke referensi transaksi (payroll, expense, transaction)
- Multiple journal entry lines
- Debit dan kredit harus balance
- Status: Draft → Posted / Void
- Tanggal transaksi
- Deskripsi

**Prinsip Double-Entry:**
```
Total Debit = Total Credit
```

**Use Case:**
- Mencatat transaksi akuntansi
- Posting ke general ledger
- Audit trail transaksi keuangan
- Basis pembuatan laporan keuangan

---

## Integrasi Antar Modul

### Manufaktur → Akuntansi

1. **Transaksi Produksi:**
   - Mengurangi stok bahan baku
   - Menambah stok produk
   - Dapat di-link ke Journal Entry (Debit: Persediaan Produk Jadi, Credit: Persediaan Bahan Baku)

2. **Transaksi Penjualan:**
   - Mengurangi stok produk
   - Dapat di-link ke Journal Entry (Debit: Kas/Piutang, Credit: Pendapatan Penjualan)

3. **Transaksi Pembelian:**
   - Menambah stok bahan baku
   - Dapat di-link ke Journal Entry (Debit: Persediaan Bahan Baku, Credit: Kas/Hutang)

### Payroll → Akuntansi

**Journal Entry untuk Payroll:**
```
Debit: Beban Gaji (5-1000)
Credit: Hutang Gaji (2-2000)
```

### Expenses → Akuntansi

**Journal Entry untuk Expense:**
```
Debit: Beban [sesuai kategori] (5-XXXX)
Credit: Kas/Bank (1-1000/1-1100)
```

---

## Workflow Rekomendasi

### 1. Setup Awal
1. Review dan sesuaikan Chart of Accounts
2. Input data karyawan
3. Setup kategori expense

### 2. Operasional Harian
1. Catat transaksi produksi/penjualan/pembelian
2. Catat pengeluaran harian
3. Review stok bahan baku dan produk

### 3. Akhir Bulan
1. Proses penggajian karyawan
2. Review dan approve semua expenses
3. Posting journal entries
4. Generate laporan keuangan

---

## Keamanan & Audit

### Auto-Generated Numbers
Semua nomor transaksi di-generate otomatis untuk memastikan:
- Uniqueness
- Traceability
- Audit trail yang jelas

### Status Workflow
Setiap modul memiliki status workflow untuk:
- Kontrol approval
- Tracking progress
- Audit trail

### Timestamps
Semua record memiliki created_at dan updated_at untuk:
- Audit trail
- Tracking perubahan
- Analisis historis

---

## Best Practices

### 1. Chart of Accounts
- Jangan hapus akun yang sudah digunakan
- Gunakan status "inactive" untuk akun yang tidak digunakan lagi
- Maintain hierarki yang konsisten

### 2. Payroll
- Selalu review sebelum approve
- Dokumentasikan komponen tambahan (tunjangan, bonus, potongan)
- Backup data payroll secara berkala

### 3. Expenses
- Selalu upload bukti pembayaran
- Isi nomor referensi/invoice dengan lengkap
- Gunakan kategori yang konsisten

### 4. Journal Entries
- Pastikan debit = credit sebelum posting
- Gunakan deskripsi yang jelas
- Link ke referensi transaksi jika ada

---

## Laporan yang Dapat Dihasilkan

### Dari Data yang Ada:
1. **Laporan Gaji** - Per karyawan, per periode
2. **Laporan Pengeluaran** - Per kategori, per periode
3. **Trial Balance** - Dari Chart of Accounts
4. **General Ledger** - Per akun
5. **Laporan Stok** - Bahan baku dan produk
6. **Laporan Produksi** - Per periode
7. **Laporan Penjualan** - Per periode

---

## Pengembangan Selanjutnya

### Fitur yang Dapat Ditambahkan:
1. ✅ Laporan Laba Rugi (Income Statement)
2. ✅ Laporan Neraca (Balance Sheet)
3. ✅ Laporan Arus Kas (Cash Flow Statement)
4. ✅ Dashboard Keuangan dengan grafik
5. ✅ Export laporan ke PDF/Excel
6. ✅ Budgeting & Forecasting
7. ✅ Cost Center Management
8. ✅ Fixed Asset Management
9. ✅ Bank Reconciliation
10. ✅ Multi-currency support

---

## Support & Dokumentasi

Untuk pertanyaan atau bantuan:
- Baca dokumentasi lengkap di README.md
- Review struktur database di migrations
- Lihat contoh data di seeders

---

**Versi:** 1.0  
**Tanggal:** Oktober 2025  
**Framework:** Laravel 12 + Filament v4
