# 📊 Sistem BTKL - Dokumentasi Lengkap

## Fitur yang Sudah Dibuat

### ✅ 1. Tabel BTKL dengan Grouping by Product
**Lokasi:** Menu BTKL → List

**Fitur:**
- **Grouping by Product**: Data otomatis dikelompokkan per produk
- **Summarizer**: Menampilkan total BTKL per pcs (dijumlahkan dari semua pegawai)
- **Filter**: Filter berdasarkan pegawai atau produk
- **Total Biaya**: Summary total biaya di bagian bawah

**Cara Lihat:**
1. Buka menu **BTKL**
2. Klik tombol **Group** di toolbar
3. Pilih **Produk**
4. Data akan dikelompokkan per produk dengan total BTKL per pcs

---

### ✅ 2. Total BTKL Otomatis di Halaman Product
**Lokasi:** Menu Produk → Edit Product

**Fitur:**
- **Total BTKL per Pcs**: Otomatis menghitung total dari semua pegawai
- **Jumlah Pegawai**: Menampilkan berapa pegawai yang terlibat
- **Auto-update**: Otomatis update saat ada perubahan data BTKL

**Contoh Tampilan:**
```
Total BTKL per Pcs: Rp 4.000 (2 pegawai)
```

---

## 📝 Contoh Kasus Penggunaan

### Produk: Brownies Keju

**Input Data BTKL:**

1. **Aura (BTKL)**
   - Biaya per pcs: Rp 2.000
   - Jumlah: 10 pcs
   - Total: Rp 20.000

2. **Budi (BTKL)**
   - Biaya per pcs: Rp 2.000
   - Jumlah: 5 pcs
   - Total: Rp 10.000

**Hasil Otomatis:**

**Di Tabel BTKL (dengan grouping by product):**
```
📦 Brownies Keju
├─ Aura: Rp 2.000/pcs × 10 = Rp 20.000
├─ Budi: Rp 2.000/pcs × 5 = Rp 10.000
└─ Total BTKL/Pcs: Rp 4.000 (Aura + Budi)
   Total Biaya: Rp 30.000
```

**Di Halaman Product (Edit Brownies Keju):**
```
Total BTKL per Pcs: Rp 4.000 (2 pegawai)
```

**Penjelasan:**
- Total BTKL per pcs = Rp 2.000 (Aura) + Rp 2.000 (Budi) = **Rp 4.000**
- Ini adalah biaya tenaga kerja untuk membuat 1 pcs Brownies Keju
- Total biaya produksi = Rp 30.000 (untuk 15 pcs)

---

## 🔄 Alur Kerja Sistem

### 1. Setup Pegawai BTKL
```
Menu Employees → Create
├─ Nama: Aura
├─ Tipe Pegawai: BTKL ✅
└─ Status: Active
```

### 2. Input Data BTKL
```
Menu BTKL → Create
├─ Pegawai: Aura (hanya BTKL yang muncul)
├─ Produk: Brownies Keju
├─ Biaya per pcs: Rp 2.000
├─ Jumlah: 10 pcs
└─ Total: Rp 20.000 (auto-calculate)
```

### 3. Lihat Hasil
```
Menu BTKL → Group by Produk
└─ Lihat total BTKL per produk

Menu Produk → Edit Brownies Keju
└─ Lihat "Total BTKL per Pcs" otomatis
```

---

## 📊 Integrasi dengan BOM & HPP

### Formula HPP (Harga Pokok Produksi):
```
HPP = Biaya Bahan Baku + Biaya BTKL + Biaya Overhead
```

### Contoh Perhitungan:
**Brownies Keju (per pcs):**
- Bahan Baku: Rp 5.000
- BTKL: Rp 4.000 (dari sistem)
- Overhead: Rp 1.000
- **HPP = Rp 10.000**
- **Harga Jual = HPP × 2.2 = Rp 22.000**

---

## 🎯 Keuntungan Sistem

### 1. **Otomatis & Akurat**
- Total BTKL otomatis dihitung
- Tidak perlu hitung manual
- Data real-time

### 2. **Transparan**
- Bisa lihat kontribusi setiap pegawai
- Grouping per produk
- Summary otomatis

### 3. **Terintegrasi**
- Data BTKL langsung tersedia di Product
- Bisa digunakan untuk perhitungan HPP
- Filter dan search mudah

---

## 📌 Tips Penggunaan

### ✅ DO:
1. Input data BTKL setiap kali ada produksi
2. Gunakan grouping untuk lihat per produk
3. Cek total BTKL di halaman Product sebelum set harga jual

### ❌ DON'T:
1. Jangan set pegawai admin sebagai BTKL
2. Jangan lupa input jumlah unit yang benar
3. Jangan input data duplikat untuk pegawai yang sama

---

## 🔍 Cara Lihat Data

### Lihat Total BTKL per Produk:
1. **Menu BTKL** → Klik **Group** → Pilih **Produk**
2. **Menu Produk** → Edit produk → Lihat field "Total BTKL per Pcs"

### Filter Data:
1. **Filter by Pegawai**: Lihat BTKL per pegawai tertentu
2. **Filter by Produk**: Lihat semua pegawai yang membuat produk tertentu

---

## 🚀 Update Terbaru

### v1.0 - Fitur Lengkap
- ✅ Grouping by Product di tabel BTKL
- ✅ Summarizer untuk total BTKL per pcs
- ✅ Auto-display total BTKL di halaman Product
- ✅ Filter hanya pegawai BTKL (BTKTL tidak muncul)
- ✅ Auto-calculate total cost
- ✅ Summary total biaya

---

## 📞 Troubleshooting

**Q: Total BTKL tidak muncul di Product?**
A: Pastikan sudah ada data BTKL untuk produk tersebut di menu BTKL.

**Q: Grouping tidak muncul?**
A: Klik tombol "Group" di toolbar tabel BTKL, lalu pilih "Produk".

**Q: Pegawai BTKTL masih muncul di dropdown?**
A: Clear cache dengan `php artisan optimize:clear` dan refresh browser.
