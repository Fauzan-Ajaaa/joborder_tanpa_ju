# 📊 Dashboard Features - Perusahaan Manufaktur

## Overview
Dashboard yang telah dibuat mengikuti best practices modern UI/UX dengan inspirasi dari dashboard akuntansi profesional, disesuaikan untuk sistem manufaktur makanan.

## Widgets yang Tersedia

### 1. 📈 StatsOverviewWidget (Stats Cards)
**File:** `app/Filament/Admin/Widgets/StatsOverviewWidget.php`

**Fitur:**
- **Total Pendapatan** - Menampilkan pendapatan bulan ini dengan trend percentage dari bulan lalu
- **Total Beban** - Menampilkan total beban (pembelian + gaji + beban operasional) dengan trend
- **Laba Bersih** - Menampilkan laba bersih bulan ini dengan trend
- **Total Produk** - Jumlah jenis produk makanan
- **Stok Produk** - Total stok produk yang siap diproduksi
- **Bahan Baku** - Jumlah jenis bahan baku
- **Karyawan Aktif** - Jumlah karyawan aktif
- **Supplier** - Jumlah supplier mitra

**Highlights:**
- ✅ Trend percentage otomatis (perbandingan bulan ini vs bulan lalu)
- ✅ Icon dinamis (trending up/down berdasarkan trend)
- ✅ Color coding (hijau untuk positif, merah untuk negatif)
- ✅ Mini chart untuk visualisasi trend

---

### 2. 📊 RevenueChartWidget (Line Chart)
**File:** `app/Filament/Admin/Widgets/RevenueChartWidget.php`

**Fitur:**
- Grafik garis (line chart) untuk 6 bulan terakhir
- Menampilkan 2 dataset:
  - **Pendapatan** (hijau) - dari transaksi penjualan
  - **Beban** (merah) - dari pembelian + gaji + beban operasional
- Format currency IDR otomatis
- Responsive dan interactive tooltip

**Data Source:**
- `Transaction` (type: sale) untuk pendapatan
- `Transaction` (type: purchase) + `Payroll` + `Expense` untuk beban

---

### 3. 📈 BalanceSheetChartWidget (Bar Chart)
**File:** `app/Filament/Admin/Widgets/BalanceSheetChartWidget.php`

**Fitur:**
- Grafik batang (bar chart) untuk komposisi neraca
- Menampilkan 3 kategori:
  - **Aset** (biru)
  - **Kewajiban** (merah)
  - **Modal** (hijau)
- Format currency IDR otomatis
- Color coding sesuai tipe akun

**Data Source:**
- `ChartOfAccount` dengan filter berdasarkan `account_type`

---

### 4. 📋 RecentActivityWidget (Activity Table)
**File:** `app/Filament/Admin/Widgets/RecentActivityWidget.php`
**View:** `resources/views/filament/admin/widgets/recent-activity-widget.blade.php`

**Fitur:**
- Tabel aktivitas terbaru (10 terakhir)
- Menggabungkan data dari multiple sources:
  - Transaksi penjualan (3 terakhir)
  - Transaksi pembelian (2 terakhir)
  - Pembayaran gaji/payroll (2 terakhir)
  - Beban operasional (2 terakhir)
- Kolom yang ditampilkan:
  - Tanggal
  - Deskripsi
  - Tipe (badge dengan color coding)
  - Jumlah (format currency dengan color)
  - Status (badge)

**Color Coding:**
- 🟢 Penerimaan/Penjualan - hijau (success)
- 🔴 Pengeluaran - merah (danger)
- 🟡 Menunggu - kuning (warning)
- 🔵 Proses - biru (info)

---

### 5. ⚡ QuickActionsWidget (Action Cards)
**File:** `app/Filament/Admin/Widgets/QuickActionsWidget.php`
**View:** `resources/views/filament/admin/widgets/quick-actions-widget.blade.php`

**Fitur:**
- 6 kartu aksi cepat dengan hover effect
- Setiap kartu memiliki:
  - Icon yang relevan
  - Label aksi
  - Deskripsi singkat
  - Link langsung ke form create

**Actions:**
1. 🛒 **Buat Transaksi Penjualan** - Catat penjualan produk
2. 🛍️ **Buat Transaksi Pembelian** - Beli bahan baku dari supplier
3. 🎂 **Tambah Produk Baru** - Daftarkan produk makanan
4. 📦 **Tambah Bahan Baku** - Daftarkan bahan baku baru
5. 💰 **Catat Beban** - Tambah pengeluaran operasional
6. 👥 **Proses Payroll** - Hitung gaji karyawan

**Hover Effects:**
- Transform translateY (-4px)
- Shadow enhancement
- Border color change
- Icon scale (110%)

---

## Layout Dashboard

```
┌─────────────────────────────────────────────────────────┐
│  Stats Cards (4 cards per row)                          │
│  💰 Pendapatan | 💸 Beban | 💵 Laba | 🎂 Produk        │
│  📦 Stok | 🛍️ Bahan | 👨‍🍳 Karyawan | 🚚 Supplier      │
└─────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────┐
│  📊 Revenue Chart        │  📈 Balance Sheet Chart      │
│  (Line Chart - 6 months) │  (Bar Chart - Neraca)        │
└──────────────────────────┴──────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  📋 Recent Activity (Table - 10 items)                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│  ⚡ Quick Actions (6 action cards)                      │
└─────────────────────────────────────────────────────────┘
```

---

## Teknologi yang Digunakan

### Backend (PHP/Laravel)
- **Filament v3** - Admin panel framework
- **Laravel Eloquent** - ORM untuk query database
- **Carbon** - Date manipulation

### Frontend
- **Blade Templates** - Templating engine
- **Tailwind CSS** - Utility-first CSS framework
- **Chart.js** - Charting library (via Filament)
- **Heroicons** - Icon set

---

## Perbandingan dengan Contoh React

| Fitur React | Implementasi Filament PHP |
|-------------|---------------------------|
| `useState`, `useEffect` | Livewire reactive properties |
| `LineChart`, `BarChart` (recharts) | `ChartWidget` dengan Chart.js |
| Inline styles | Tailwind CSS classes |
| `onClick` handlers | Blade `href` links |
| `formatCurrency()` | `number_format()` + Blade |
| Component props | Widget `getViewData()` |

---

## Cara Menggunakan

### 1. Widget sudah otomatis terdaftar
Filament akan auto-discover semua widget di folder `app/Filament/Admin/Widgets/`

### 2. Urutan widget diatur dengan `$sort`
```php
protected static ?int $sort = 1; // Semakin kecil, semakin atas
```

### 3. Lebar widget diatur dengan `$columnSpan`
```php
protected int | string | array $columnSpan = 'full'; // Full width
protected int | string | array $columnSpan = 2; // 2 columns
```

### 4. Refresh data otomatis
Widget akan refresh saat halaman di-reload. Untuk auto-refresh:
```php
protected static ?string $pollingInterval = '30s'; // Refresh tiap 30 detik
```

---

## Customization

### Mengubah Warna Chart
Edit file widget, contoh `RevenueChartWidget.php`:
```php
'borderColor' => 'rgb(16, 185, 129)', // Hijau
'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
```

### Mengubah Jumlah Data
Edit limit di method `getRecentActivities()`:
```php
->limit(5) // Ubah angka sesuai kebutuhan
```

### Menambah Action Baru
Edit `QuickActionsWidget.php`, tambahkan array baru:
```php
[
    'label' => 'Action Baru',
    'description' => 'Deskripsi action',
    'icon' => 'heroicon-o-icon-name',
    'color' => 'primary',
    'url' => ResourceClass::getUrl('create'),
],
```

---

## Troubleshooting

### Widget tidak muncul
1. Pastikan namespace benar: `App\Filament\Admin\Widgets`
2. Clear cache: `php artisan filament:cache-components`
3. Check AdminPanelProvider: `discoverWidgets()` harus ada

### Data tidak akurat
1. Periksa relasi model (with eager loading)
2. Pastikan kolom tanggal sudah di-cast ke 'date'
3. Check timezone di `config/app.php`

### Chart tidak tampil
1. Pastikan data tidak kosong (return minimal 0)
2. Check format data (harus array of numbers)
3. Inspect browser console untuk error JavaScript

---

## Best Practices

✅ **DO:**
- Gunakan eager loading untuk relasi (`with()`)
- Cache query yang berat
- Format currency konsisten
- Gunakan color coding yang jelas
- Tambahkan tooltip informatif

❌ **DON'T:**
- Query di loop (N+1 problem)
- Hardcode nilai
- Gunakan terlalu banyak widget (performance)
- Lupa handle data kosong

---

## Future Enhancements

Fitur yang bisa ditambahkan:
- 📅 Filter berdasarkan range tanggal
- 📊 Export data ke PDF/Excel
- 🔔 Notifikasi untuk aktivitas penting
- 📈 Prediksi trend dengan machine learning
- 🎯 Goal tracking dan KPI
- 📱 Responsive mobile optimization
- 🌙 Dark mode optimization

---

## Credits

Dashboard ini terinspirasi dari:
- Modern accounting dashboard UI/UX
- Filament v3 best practices
- React/TypeScript dashboard patterns

Dibuat dengan ❤️ untuk Perusahaan Manufaktur Makanan
