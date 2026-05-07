# Statistik Keuangan - Pemasukan & Pengeluaran

## 📊 Perubahan Dashboard

Dashboard sekarang menampilkan **2 statistik keuangan terpisah**:

### 1. **💰 Pemasukan** (Warna Hijau)
**Sumber:**
- ✅ Penjualan produk (`transactions` dengan `type = 'sale'`)

**Perhitungan:**
```php
$pemasukan = Transaction::where('type', 'sale')->sum('total_amount');
```

**Tampilan:**
- Icon: Arrow Trending Up ↗️
- Warna: Success (Hijau)
- Description: "Dari penjualan"

---

### 2. **💸 Pengeluaran** (Warna Merah)
**Sumber:**
- ✅ Pembelian bahan baku (`transactions` dengan `type = 'purchase'`)
- ✅ Penggajian karyawan (`payrolls.net_salary`)
- ✅ Beban operasional (`expenses.amount`)

**Perhitungan:**
```php
$pembelian = Transaction::where('type', 'purchase')->sum('total_amount');
$penggajian = Payroll::sum('net_salary');
$beban = Expense::sum('amount');
$pengeluaran = $pembelian + $penggajian + $beban;
```

**Tampilan:**
- Icon: Arrow Trending Down ↘️
- Warna: Danger (Merah)
- Description: "Pembelian, gaji & beban"

---

## 📈 Statistik Dashboard Lengkap

Dashboard sekarang menampilkan **6 cards**:

1. **📦 Total Produk** (Hijau)
   - Jumlah produk terdaftar
   
2. **🔧 Bahan Baku** (Biru)
   - Jumlah raw material tersedia
   
3. **💰 Pemasukan** (Hijau)
   - Total dari penjualan
   
4. **💸 Pengeluaran** (Merah)
   - Total pembelian + gaji + beban
   
5. **👥 Karyawan Aktif** (Biru)
   - Jumlah karyawan dengan status active
   
6. **🏪 Supplier** (Orange)
   - Jumlah mitra supplier

---

## 🎯 Keuntungan Pemisahan

### **Sebelum:**
- ❌ Hanya 1 statistik "Nilai Transaksi"
- ❌ Tidak jelas mana pemasukan dan pengeluaran
- ❌ Sulit analisis keuangan

### **Sesudah:**
- ✅ 2 statistik terpisah yang jelas
- ✅ Mudah membandingkan pemasukan vs pengeluaran
- ✅ Visual yang informatif (hijau vs merah)
- ✅ Description yang detail

---

## 💡 Cara Menghitung Laba/Rugi

Dengan statistik ini, Anda bisa dengan mudah menghitung:

```
Laba/Rugi = Pemasukan - Pengeluaran
```

**Contoh:**
- Pemasukan: Rp 10.000.000
- Pengeluaran: Rp 7.500.000
- **Laba: Rp 2.500.000** ✅

---

## 🔍 Detail Komponen Pengeluaran

### 1. **Pembelian Bahan Baku**
- Dari tabel: `transactions`
- Filter: `type = 'purchase'`
- Kolom: `total_amount`

### 2. **Penggajian**
- Dari tabel: `payrolls`
- Kolom: `net_salary` (gaji bersih setelah potongan)

### 3. **Beban Operasional**
- Dari tabel: `expenses`
- Kolom: `amount`
- Contoh: listrik, air, sewa, dll

---

## 📊 Chart Mini

Setiap statistik dilengkapi dengan **mini chart** untuk visualisasi trend:

- **Pemasukan:** Chart naik (trend positif)
- **Pengeluaran:** Chart fluktuatif (sesuai operasional)

---

## 🎨 Warna & Icon

### Pemasukan
```php
->color('success')              // Hijau
->descriptionIcon('heroicon-m-arrow-trending-up')  // ↗️
```

### Pengeluaran
```php
->color('danger')               // Merah
->descriptionIcon('heroicon-m-arrow-trending-down') // ↘️
```

---

## 🚀 Pengembangan Selanjutnya

Saran enhancement:
- [ ] Tambah filter periode (bulan ini, tahun ini)
- [ ] Tambah statistik Laba/Rugi otomatis
- [ ] Breakdown detail pengeluaran (pie chart)
- [ ] Perbandingan dengan periode sebelumnya
- [ ] Export laporan keuangan ke PDF/Excel
- [ ] Grafik trend bulanan
- [ ] Alert jika pengeluaran > pemasukan

---

## 📝 File yang Dimodifikasi

```
✅ app/Filament/Admin/Widgets/StatsOverviewWidget.php
```

**Perubahan:**
- Import model `Payroll` dan `Expense`
- Pisahkan perhitungan pemasukan dan pengeluaran
- Update warna dan icon sesuai konteks
- Tambah description yang informatif

---

## 🔧 Troubleshooting

### Data tidak muncul
```bash
# Pastikan ada data di tabel
php artisan tinker
>>> App\Models\Transaction::where('type', 'sale')->count();
>>> App\Models\Payroll::count();
>>> App\Models\Expense::count();
```

### Nilai tidak akurat
```bash
# Clear cache
php artisan optimize:clear
```

### Widget tidak update
```bash
# Refresh browser
Ctrl + F5
```

---

**Dibuat:** 24 Oktober 2025  
**Versi:** 1.0  
**Status:** ✅ Production Ready
