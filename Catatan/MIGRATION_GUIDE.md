# Panduan Migrasi dari Filament ke Laravel Murni

## Perubahan yang Dilakukan

### 1. Struktur Aplikasi
- **Sebelum**: Menggunakan Filament Admin Panel
- **Sesudah**: Laravel MVC murni dengan Tailwind CSS

### 2. Controllers yang Dibuat
- `DashboardController` - Halaman dashboard utama
- `ProductController` - CRUD Produk
- `RawMaterialController` - CRUD Bahan Baku + Kartu Stok
- `TransactionController` - CRUD Transaksi Bahan Baku
- `SupplierController` - CRUD Supplier
- `EmployeeController` - CRUD Karyawan
- `SalesTransactionController` - CRUD Transaksi Penjualan
- `Auth\AuthenticatedSessionController` - Login
- `Auth\RegisteredUserController` - Register

### 3. Routes
Semua routes sekarang menggunakan resource controllers standar Laravel:
```php
Route::resource('products', ProductController::class);
Route::resource('raw-materials', RawMaterialController::class);
Route::resource('transactions', TransactionController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('employees', EmployeeController::class);
Route::resource('sales', SalesTransactionController::class);
```

### 4. Authentication
- Menggunakan Laravel authentication murni (bukan Filament auth)
- Login route: `/login`
- Register route: `/register`
- Dashboard route: `/dashboard`

### 5. Views
- Layout utama: `resources/views/layouts/app.blade.php`
- Login: `resources/views/auth/login.blade.php`
- Register: `resources/views/auth/register.blade.php`
- Dashboard: `resources/views/dashboard.blade.php`
- Products: `resources/views/products/index.blade.php`

## Langkah Instalasi

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Setup Database
```bash
php artisan migrate
```

### 3. Build Assets
```bash
npm run build
# atau untuk development
npm run dev
```

### 4. Jalankan Server
```bash
php artisan serve
```

### 5. Buat User Pertama
Akses `/register` untuk membuat akun pertama, atau gunakan tinker:
```bash
php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);
```

## Fitur yang Tersedia

### Master Data
- ✅ Produk (CRUD)
- ✅ Bahan Baku (CRUD + Kartu Stok)
- ✅ Supplier (CRUD)
- ✅ Karyawan (CRUD)

### Transaksi
- ✅ Transaksi Bahan Baku (Masuk/Keluar)
- ✅ Penjualan Produk

### Dashboard
- ✅ Statistik ringkasan
- ✅ Transaksi terbaru
- ✅ Penjualan terbaru

## Fitur yang Perlu Dikembangkan

Berikut adalah fitur dari Filament yang belum diimplementasikan:

1. **BOM (Bill of Materials)**
   - Controller: `BOMDetailController`
   - Views: CRUD untuk BOM

2. **Biaya Overhead**
   - Controller: `BiayaOverheadController`
   - Views: CRUD untuk overhead

3. **Chart of Accounts**
   - Controller: `ChartOfAccountController`
   - Views: CRUD untuk COA

4. **Expenses**
   - Controller: `ExpenseController`
   - Views: CRUD untuk expenses

5. **Journal Entries**
   - Controller: `JournalEntryController`
   - Views: CRUD untuk jurnal

6. **Ledger**
   - Controller: `LedgerController`
   - Views: Laporan buku besar

7. **Payroll**
   - Controller: `PayrollController`
   - Views: CRUD untuk payroll

## Struktur File

```
app/
├── Http/
│   └── Controllers/
│       ├── Auth/
│       │   ├── AuthenticatedSessionController.php
│       │   └── RegisteredUserController.php
│       ├── DashboardController.php
│       ├── ProductController.php
│       ├── RawMaterialController.php
│       ├── TransactionController.php
│       ├── SupplierController.php
│       ├── EmployeeController.php
│       └── SalesTransactionController.php
│
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── auth/
│   │   ├── login.blade.php
│   │   └── register.blade.php
│   ├── dashboard.blade.php
│   └── products/
│       └── index.blade.php
│
routes/
├── web.php
└── auth.php
```

## Catatan Penting

1. **Middleware**: Semua routes kecuali login/register memerlukan authentication
2. **Validation**: Semua form sudah memiliki validasi di controller
3. **Database Transactions**: Operasi yang kompleks menggunakan DB::transaction()
4. **Soft Deletes**: Model masih menggunakan soft deletes jika sudah dikonfigurasi
5. **Pagination**: Semua index menggunakan pagination (15 items per page)

## Customization

### Mengubah Jumlah Items per Page
Edit di masing-masing controller:
```php
$products = Product::paginate(15); // ubah 15 ke jumlah yang diinginkan
```

### Menambah Menu Navigasi
Edit `resources/views/layouts/app.blade.php` pada bagian navigation links.

### Mengubah Warna Theme
Edit Tailwind classes di views atau tambahkan custom CSS.

## Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
```

### Error: Vite manifest not found
```bash
npm run build
```

### Error: SQLSTATE connection refused
Pastikan database sudah running dan konfigurasi di `.env` sudah benar.
