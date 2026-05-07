# 🔧 Setup MySQL Database

## Masalah
Database saat ini menggunakan **SQLite** yang file-nya tidak ada, sehingga data tidak tersimpan.

## Solusi: Ubah ke MySQL

### Langkah 1: Buat Database MySQL

1. **Buka phpMyAdmin:**
   - URL: `http://localhost/phpmyadmin`

2. **Buat Database Baru:**
   - Klik tab "Databases"
   - Nama database: `perusahaan_manufaktur_2025`
   - Collation: `utf8mb4_unicode_ci`
   - Klik "Create"

### Langkah 2: Update File .env

Buka file `.env` di root project dan ubah bagian database:

**Dari:**
```env
DB_CONNECTION=mysql
DB_DATABASE=perusahaan_manufaktur_2025
```

**Menjadi:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perusahaan_manufaktur_2025
DB_USERNAME=root
DB_PASSWORD=
```

**Note:** 
- Jika MySQL Anda pakai password, isi `DB_PASSWORD=your_password`
- Jika port MySQL berbeda, ubah `DB_PORT`

### Langkah 3: Clear Config Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Langkah 4: Run Migration & Seeder

```bash
php artisan migrate:fresh --seed
```

### Langkah 5: Test Database

```bash
php test-db.php
```

**Expected Output:**
```
✅ Database connected successfully
Products in database: 4
Raw materials in database: 5
Product materials relations: 17
Users in database: 1
```

### Langkah 6: Restart Server

```bash
# Stop server (Ctrl+C)
php artisan serve
```

---

## Verification

### Cek di phpMyAdmin:
1. Buka `http://localhost/phpmyadmin`
2. Pilih database `windsurf_project_4`
3. Lihat tabel-tabel:
   - `products` (4 rows)
   - `raw_materials` (5 rows)
   - `product_materials` (17 rows)
   - `users` (1 row)

### Test di Admin Panel:
1. Login: `http://127.0.0.1:8000/admin`
2. Create produk baru
3. Refresh phpMyAdmin
4. ✅ Data muncul di tabel `products`!

---

## Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"
**Solution:**
- Cek username dan password MySQL
- Update di file `.env`
- Jalankan `php artisan config:clear`

### Error: "Unknown database 'windsurf_project_4'"
**Solution:**
- Buat database dulu di phpMyAdmin
- Pastikan nama database sama dengan di `.env`

### Error: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
- Pastikan MySQL service running
- Buka XAMPP Control Panel
- Start Apache dan MySQL

---

## Alternative: Tetap Pakai SQLite

Jika ingin tetap pakai SQLite:

### Langkah 1: Buat File Database

```bash
# Di PowerShell
New-Item -Path database/database.sqlite -ItemType File
```

### Langkah 2: Update .env

```env
DB_CONNECTION=sqlite
DB_DATABASE=C:\xampp2\htdocs\CascadeProjects\windsurf-project-4\database\database.sqlite
```

**Note:** Harus absolute path!

### Langkah 3: Run Migration

```bash
php artisan migrate:fresh --seed
```

---

## Rekomendasi

**Gunakan MySQL** karena:
- ✅ Lebih stabil untuk development
- ✅ Mudah di-manage via phpMyAdmin
- ✅ Performa lebih baik untuk data besar
- ✅ Lebih mirip dengan production environment

---

**Last Updated:** 17 Oktober 2025
