# Sistem Perusahaan Manufaktur - Laravel Murni

Aplikasi ini telah dikonversi dari Filament Admin Panel menjadi Laravel MVC murni dengan Tailwind CSS.

## 🚀 Quick Start

### 1. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Setup Environment
```bash
# Copy .env file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Setup Database
Edit file `.env` dan sesuaikan konfigurasi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perusahaan_manufaktur
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi:
```bash
php artisan migrate
```

### 4. Build Assets
```bash
# Untuk production
npm run build

# Untuk development (dengan hot reload)
npm run dev
```

### 5. Jalankan Aplikasi
```bash
php artisan serve
```

Akses aplikasi di: `http://localhost:8000`

### 6. Buat User Pertama
Buka browser dan akses: `http://localhost:8000/register`

Atau gunakan Tinker:
```bash
php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);
```

## 📁 Struktur Aplikasi

### Controllers
```
app/Http/Controllers/
├── Auth/
│   ├── AuthenticatedSessionController.php  # Login
│   └── RegisteredUserController.php        # Register
├── DashboardController.php                 # Dashboard
├── ProductController.php                   # CRUD Produk
├── RawMaterialController.php               # CRUD Bahan Baku
├── TransactionController.php               # CRUD Transaksi
├── SupplierController.php                  # CRUD Supplier
├── EmployeeController.php                  # CRUD Karyawan
└── SalesTransactionController.php          # CRUD Penjualan
```

### Views
```
resources/views/
├── layouts/
│   └── app.blade.php                       # Layout utama
├── components/
│   └── nav-link.blade.php                  # Component navigasi
├── auth/
│   ├── login.blade.php                     # Halaman login
│   └── register.blade.php                  # Halaman register
├── dashboard.blade.php                     # Dashboard
├── products/
│   └── index.blade.php                     # Daftar produk
├── raw-materials/
│   └── index.blade.php                     # Daftar bahan baku
├── suppliers/
│   └── index.blade.php                     # Daftar supplier
├── employees/
│   └── index.blade.php                     # Daftar karyawan
├── transactions/
│   └── index.blade.php                     # Daftar transaksi
└── sales/
    └── index.blade.php                     # Daftar penjualan
```

### Routes
```
routes/
├── web.php                                 # Routes utama
└── auth.php                                # Routes authentication
```

## 🎯 Fitur yang Tersedia

### ✅ Sudah Diimplementasikan

#### Master Data
- **Produk**: CRUD lengkap dengan material yang dibutuhkan
- **Bahan Baku**: CRUD lengkap dengan kartu stok
- **Supplier**: CRUD lengkap
- **Karyawan**: CRUD lengkap

#### Transaksi
- **Transaksi Bahan Baku**: Masuk/Keluar dengan update stok otomatis
- **Penjualan**: CRUD dengan multiple items dan update stok produk

#### Dashboard
- Statistik ringkasan (Total Produk, Bahan Baku, Transaksi, Penjualan, Karyawan)
- Transaksi bahan baku terbaru
- Penjualan terbaru

#### Authentication
- Login
- Register
- Logout
- Protected routes dengan middleware auth

### 📋 Belum Diimplementasikan (Perlu Dikembangkan)

Fitur-fitur berikut masih menggunakan Filament dan perlu dikonversi:

1. **BOM (Bill of Materials)**
   - Model: `BOM_Detail`, `BomDetailItem`
   - Perlu: Controller + Views

2. **Biaya Overhead**
   - Model: `BiayaOverhead`
   - Perlu: Controller + Views

3. **Chart of Accounts**
   - Model: `ChartOfAccount`
   - Perlu: Controller + Views

4. **Expenses**
   - Model: `Expense`
   - Perlu: Controller + Views

5. **Journal Entries**
   - Model: `JournalEntry`, `JournalEntryItem`, `JournalEntryLine`
   - Perlu: Controller + Views

6. **Ledger**
   - Perlu: Controller + Views untuk laporan

7. **Payroll**
   - Model: `Payroll`
   - Perlu: Controller + Views

## 🔧 Teknologi yang Digunakan

- **Backend**: Laravel 12
- **Frontend**: Tailwind CSS + Alpine.js
- **Database**: MySQL
- **PDF**: DomPDF (untuk laporan)
- **Authentication**: Laravel native auth

## 📝 Routes Utama

### Public Routes
- `GET /` - Redirect ke login
- `GET /login` - Halaman login
- `POST /login` - Proses login
- `GET /register` - Halaman register
- `POST /register` - Proses register

### Protected Routes (Require Auth)
- `GET /dashboard` - Dashboard
- Resource routes untuk:
  - `/products` - Produk
  - `/raw-materials` - Bahan Baku
  - `/transactions` - Transaksi
  - `/suppliers` - Supplier
  - `/employees` - Karyawan
  - `/sales` - Penjualan

## 🎨 Customization

### Mengubah Warna Theme
Edit file `resources/views/layouts/app.blade.php` dan ubah class Tailwind:
```html
<!-- Contoh: Ubah warna primary dari blue ke purple -->
<a class="bg-blue-600 hover:bg-blue-700">
<!-- Menjadi -->
<a class="bg-purple-600 hover:bg-purple-700">
```

### Menambah Menu Navigasi
Edit file `resources/views/layouts/app.blade.php` pada bagian navigation:
```html
<x-nav-link :href="route('nama-route')" :active="request()->routeIs('nama-route')">
    Nama Menu
</x-nav-link>
```

### Mengubah Pagination
Edit di controller:
```php
// Default: 15 items per page
$products = Product::paginate(15);

// Ubah menjadi 20 items per page
$products = Product::paginate(20);
```

## 🐛 Troubleshooting

### Error: Class not found
```bash
composer dump-autoload
```

### Error: Vite manifest not found
```bash
npm run build
```

### Error: SQLSTATE connection refused
Pastikan:
1. MySQL/MariaDB sudah running
2. Database sudah dibuat
3. Konfigurasi `.env` sudah benar

### Error: 419 Page Expired
Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Styling tidak muncul
Pastikan Vite sudah running:
```bash
npm run dev
```

Atau build assets:
```bash
npm run build
```

## 📚 Dokumentasi Tambahan

Lihat file `MIGRATION_GUIDE.md` untuk detail lengkap tentang proses migrasi dari Filament ke Laravel murni.

## 🔐 Security

- Semua routes kecuali login/register dilindungi dengan middleware `auth`
- Password di-hash menggunakan bcrypt
- CSRF protection aktif untuk semua form
- SQL injection protection melalui Eloquent ORM

## 📞 Support

Jika ada pertanyaan atau masalah, silakan buat issue atau hubungi developer.

## 📄 License

MIT License
