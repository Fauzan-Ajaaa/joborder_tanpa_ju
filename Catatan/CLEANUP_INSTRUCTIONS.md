# Instruksi Cleanup - Menghapus Filament

Setelah migrasi ke Laravel murni, ikuti langkah-langkah berikut untuk membersihkan sisa-sisa Filament:

## 1. Hapus Folder Filament

Hapus folder-folder berikut secara manual:

```
app/Filament/
app/Providers/Filament/
resources/views/filament/
```

## 2. Hapus Konfigurasi Filament

Hapus file konfigurasi Filament:

```
config/filament.php
config/filament-*.php
```

## 3. Hapus Assets Filament

Hapus folder public yang berisi assets Filament:

```
public/css/filament/
public/js/filament/
```

## 4. Update Composer

Jalankan composer update untuk menghapus package Filament:

```bash
composer update
composer dump-autoload
```

## 5. Clear Cache

Clear semua cache Laravel:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

## 6. Rebuild Assets

Build ulang assets dengan Vite:

```bash
npm run build
```

## 7. Test Aplikasi

Jalankan aplikasi dan test semua fitur:

```bash
php artisan serve
```

Buka browser dan test:
- Login/Register
- Dashboard
- CRUD Products
- CRUD Raw Materials
- CRUD Transactions
- CRUD Suppliers
- CRUD Employees
- CRUD Sales

## 8. Optional: Hapus Migration Filament

Jika ada migration dari Filament yang tidak diperlukan, Anda bisa menghapusnya dari folder `database/migrations/`.

## Catatan Penting

⚠️ **BACKUP DATABASE** sebelum melakukan cleanup!

⚠️ Pastikan semua fitur sudah berfungsi dengan baik sebelum menghapus folder Filament.

## Jika Ada Masalah

Jika setelah cleanup ada error:

1. Cek apakah ada import/use statement yang masih mereferensi Filament
2. Grep untuk mencari sisa referensi:
   ```bash
   grep -r "Filament" app/
   grep -r "filament" resources/views/
   ```
3. Hapus semua referensi yang ditemukan

## Verifikasi Cleanup Berhasil

Jalankan command berikut untuk memastikan tidak ada error:

```bash
php artisan about
php artisan route:list
composer validate
```

Jika semua command di atas berjalan tanpa error, cleanup berhasil!
