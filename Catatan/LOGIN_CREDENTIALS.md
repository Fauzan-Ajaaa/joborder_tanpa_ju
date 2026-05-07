# 🔐 Login Credentials & Setup Guide

## Admin Login

**URL:** `http://localhost/windsurf-project-4/public/admin`

**Credentials:**
- **Email:** `admin@admin.com`
- **Password:** `password`

---

## 🚀 Quick Start

### 1. Pastikan Database Sudah Di-Migrate
```bash
php artisan migrate:fresh --seed
```

### 2. Jalankan Development Server
```bash
php artisan serve
```

### 3. Akses Admin Panel
Buka browser dan akses:
```
http://localhost:8000/admin
```

Atau jika menggunakan XAMPP:
```
http://localhost/windsurf-project-4/public/admin
```

### 4. Login dengan Credentials di Atas

---

## 📋 Menu yang Tersedia

### 1. **Dashboard**
- Overview sistem
- Statistik singkat

### 2. **Produk** (`/admin/products`)
- ✅ Create: Tambah produk baru dengan komposisi bahan baku
- ✅ Read: Lihat daftar produk
- ✅ Update: Edit produk dan komposisi bahan
- ✅ Delete: Hapus produk

**Fitur Khusus:**
- Input komposisi bahan baku per produk
- Tampilan jumlah bahan baku yang digunakan
- Badge warna untuk indikator stok

### 3. **Bahan Baku** (`/admin/raw-materials`)
- ✅ Create: Tambah bahan baku baru
- ✅ Read: Lihat daftar bahan baku
- ✅ Update: Edit bahan baku
- ✅ Delete: Hapus bahan baku

**Fitur Khusus:**
- Stok minimum warning
- Unit measurement (kg, liter, dll)
- Price per unit

### 4. **Transaksi** (`/admin/transactions`)
- ✅ Create: Buat transaksi (Penjualan/Produksi/Pembelian)
- ✅ Read: Lihat daftar transaksi
- ✅ Update: Edit transaksi
- ✅ Delete: Hapus transaksi

**Fitur Khusus:**
- Auto-generate nomor transaksi
- Multiple items per transaksi
- Pengurangan stok otomatis saat penjualan
- Status tracking (Pending/Completed/Cancelled)

### 5. **Karyawan** (`/admin/employees`)
- ✅ CRUD karyawan
- Data gaji dan posisi

### 6. **Payroll** (`/admin/payrolls`)
- ✅ CRUD penggajian
- Tracking pembayaran gaji

### 7. **Expenses** (`/admin/expenses`)
- ✅ CRUD pengeluaran
- Kategori pengeluaran

### 8. **Chart of Accounts** (`/admin/chart-of-accounts`)
- ✅ CRUD akun akuntansi
- Struktur hierarki akun

---

## 🎯 Testing CRUD Produk dengan Bahan Baku

### Test 1: Create Product
1. Klik menu **Produk** → **Create**
2. Isi form:
   ```
   Nama: Donat Coklat
   Kode: PRD005
   Deskripsi: Donat lembut dengan topping coklat
   Harga: 5000
   Stok: 0
   ```
3. Scroll ke **Komposisi Bahan Baku**
4. Klik **Tambah Bahan Baku**
5. Pilih bahan:
   ```
   Bahan: Tepung Terigu
   Jumlah: 0.1
   
   Bahan: Gula Pasir
   Jumlah: 0.05
   
   Bahan: Telur
   Jumlah: 0.02
   ```
6. Klik **Create**
7. ✅ **Expected:** Produk tersimpan dengan 3 bahan baku

### Test 2: Read/View Products
1. Klik menu **Produk**
2. ✅ **Expected:** Melihat daftar produk dengan kolom:
   - Kode
   - Nama Produk
   - Harga
   - Stok (dengan badge warna)
   - Jumlah Bahan (badge info)

### Test 3: Update Product
1. Klik tombol **Edit** pada produk
2. Ubah data (misalnya harga atau tambah bahan baku)
3. Klik **Save**
4. ✅ **Expected:** Data terupdate

### Test 4: Delete Product
1. Klik tombol **Edit** pada produk
2. Klik tombol **Delete** di header
3. Konfirmasi delete
4. ✅ **Expected:** Produk terhapus (cascade delete product_materials)

---

## 🧪 Testing Transaksi Penjualan

### Test: Penjualan dengan Pengurangan Stok Otomatis

**Pre-condition:**
- Produk "Roti Tawar" ada (dari seeder)
- Stok Tepung: 500 kg
- Stok Gula: 300 kg

**Steps:**
1. Klik menu **Transaksi** → **Create**
2. Isi form:
   ```
   Tipe: Penjualan
   Tanggal: [hari ini]
   Status: Selesai ⚠️ (PENTING!)
   ```
3. Tambah Item:
   ```
   Produk: Roti Tawar
   Jumlah: 10
   Harga: 25000
   ```
4. Klik **Create**

**Expected Result:**
- ✅ Transaksi tersimpan
- ✅ Stok Roti Tawar: -10
- ✅ Stok Tepung: 495 kg (500 - 5)
- ✅ Stok Gula: 299 kg (300 - 1)

**Verification:**
1. Buka menu **Bahan Baku**
2. Cek stok Tepung dan Gula
3. Stok harus berkurang sesuai perhitungan

---

## 🔍 Troubleshooting

### Problem: Tidak bisa login
**Solution:**
1. Pastikan database sudah di-seed
2. Cek apakah user admin ada:
   ```bash
   php artisan tinker
   >>> App\Models\User::count()
   ```
3. Jika tidak ada, jalankan:
   ```bash
   php artisan db:seed
   ```

### Problem: Menu tidak muncul
**Solution:**
1. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```
2. Refresh browser (Ctrl + F5)

### Problem: Error saat create produk
**Solution:**
1. Pastikan migration sudah dijalankan
2. Cek apakah tabel `product_materials` ada
3. Cek error di `storage/logs/laravel.log`

### Problem: Stok tidak berkurang
**Solution:**
1. Pastikan status transaksi = "Selesai"
2. Pastikan tipe transaksi = "Penjualan"
3. Pastikan produk memiliki komposisi bahan baku
4. Cek di menu Bahan Baku apakah stok berubah

---

## 📊 Data Sample yang Tersedia

### Products (4 items)
1. **Roti Tawar** (PRD001) - Rp 25,000
   - Tepung: 0.5 kg
   - Gula: 0.1 kg
   - Mentega: 0.05 kg
   - Susu: 0.2 L

2. **Roti Manis** (PRD002) - Rp 15,000
   - Tepung: 0.3 kg
   - Gula: 0.15 kg
   - Mentega: 0.03 kg
   - Telur: 0.1 kg

3. **Kue Kering** (PRD003) - Rp 50,000
   - Tepung: 0.4 kg
   - Gula: 0.2 kg
   - Mentega: 0.15 kg
   - Telur: 0.15 kg

4. **Cake Coklat** (PRD004) - Rp 75,000
   - Tepung: 0.6 kg
   - Gula: 0.3 kg
   - Mentega: 0.2 kg
   - Telur: 0.2 kg
   - Susu: 0.3 L

### Raw Materials (5 items)
1. **Tepung Terigu** - 500 kg @ Rp 12,000/kg
2. **Gula Pasir** - 300 kg @ Rp 15,000/kg
3. **Mentega** - 200 kg @ Rp 45,000/kg
4. **Telur** - 150 kg @ Rp 28,000/kg
5. **Susu Cair** - 100 L @ Rp 18,000/L

---

## 🎓 User Guide

### Workflow: Menambah Produk Baru

```
1. Siapkan data produk
   ├── Nama produk
   ├── Kode unik
   ├── Harga jual
   └── Komposisi bahan baku

2. Buka menu Produk → Create

3. Isi informasi dasar produk

4. Tambah komposisi bahan baku
   ├── Pilih bahan dari dropdown
   ├── Input jumlah yang dibutuhkan
   └── Ulangi untuk bahan lainnya

5. Save produk

6. Produk siap dijual!
```

### Workflow: Melakukan Penjualan

```
1. Buka menu Transaksi → Create

2. Pilih tipe: Penjualan

3. Set tanggal transaksi

4. Tambah item produk
   ├── Pilih produk
   ├── Input quantity
   └── Harga otomatis terisi

5. Set status: Selesai
   ⚠️ Penting! Stok hanya berkurang jika status = Selesai

6. Save transaksi

7. Sistem otomatis:
   ├── Kurangi stok produk
   └── Kurangi stok bahan baku
```

---

## 🔒 Security Notes

1. **Default Password:** Segera ganti password default setelah login pertama
2. **Production:** Jangan gunakan credentials default di production
3. **Environment:** Set `APP_ENV=production` dan `APP_DEBUG=false` di production

---

## 📞 Need Help?

Jika mengalami masalah:
1. Cek `storage/logs/laravel.log` untuk error details
2. Baca dokumentasi di `QUICK_REFERENCE.md`
3. Lihat test cases di `TESTING_GUIDE.md`
4. Review implementasi di `IMPLEMENTATION_SUMMARY.md`

---

**Last Updated:** 17 Oktober 2025
**System Version:** 1.0
**Status:** ✅ Ready to Use
