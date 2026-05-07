# 🔧 Troubleshooting CRUD Issues

## Masalah: Create, Edit, Delete Tidak Berfungsi

### Langkah Troubleshooting

#### 1. Clear All Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan filament:cache-components
```

#### 2. Restart Development Server
```bash
# Stop server (Ctrl+C)
php artisan serve
```

#### 3. Periksa Routes
```bash
php artisan route:list --path=admin
```

**Expected Output:** Harus ada routes untuk:
- `/admin/products` (index)
- `/admin/products/create` (create)
- `/admin/products/{record}/edit` (edit)

#### 4. Periksa Database Connection
```bash
php artisan tinker
```
```php
DB::connection()->getPdo();
// Harus return PDO object tanpa error
```

#### 5. Test Manual di Browser

**Test Create:**
1. Buka: `http://127.0.0.1:8000/admin/products/create`
2. Isi form produk
3. Klik "Create"
4. Cek apakah ada error message

**Test Edit:**
1. Buka: `http://127.0.0.1:8000/admin/products`
2. Klik icon edit pada produk
3. Ubah data
4. Klik "Save"
5. Cek apakah ada error message

**Test Delete:**
1. Buka edit page produk
2. Klik tombol "Delete" di header
3. Konfirmasi delete
4. Cek apakah produk terhapus

---

## Kemungkinan Penyebab & Solusi

### Problem 1: Error "Class not found"

**Penyebab:** Autoload tidak update

**Solusi:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Problem 2: Error "SQLSTATE" atau Database Error

**Penyebab:** Database tidak terkoneksi atau tabel tidak ada

**Solusi:**
```bash
# Cek koneksi database di .env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Atau untuk MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Jalankan migration
php artisan migrate:fresh --seed
```

### Problem 3: Error "419 Page Expired" saat Submit Form

**Penyebab:** CSRF token expired

**Solusi:**
1. Refresh halaman (F5)
2. Isi form lagi
3. Submit

Atau tambahkan di `.env`:
```
SESSION_LIFETIME=120
```

### Problem 4: Form Tidak Muncul atau Blank

**Penyebab:** JavaScript error atau component tidak load

**Solusi:**
1. Buka Developer Tools (F12)
2. Cek Console untuk error
3. Cek Network tab untuk failed requests
4. Clear browser cache (Ctrl+Shift+Delete)

### Problem 5: Button Create/Edit/Delete Tidak Ada

**Penyebab:** Resource tidak terdaftar atau Pages tidak ditemukan

**Solusi:**
Pastikan file-file ini ada:

**Untuk Products:**
```
app/Filament/Admin/Resources/Products/
├── ProductResource.php
├── Pages/
│   ├── CreateProduct.php
│   ├── EditProduct.php
│   └── ListProducts.php
├── Schemas/
│   └── ProductForm.php
└── Tables/
    └── ProductsTable.php
```

### Problem 6: Error "Method not allowed"

**Penyebab:** Route tidak terdaftar atau middleware block

**Solusi:**
```bash
php artisan route:cache
php artisan optimize
```

---

## Debugging Step by Step

### Step 1: Cek Apakah User Sudah Login
```
URL: http://127.0.0.1:8000/admin
Expected: Dashboard muncul
If not: Login dengan admin@admin.com / password
```

### Step 2: Cek Apakah Menu Muncul
```
Expected: Sidebar menampilkan menu:
- Dashboard
- Produk
- Bahan Baku
- Transaksi
- dll

If not: 
- Clear cache
- Restart server
- Refresh browser
```

### Step 3: Cek Apakah List Page Muncul
```
URL: http://127.0.0.1:8000/admin/products
Expected: Tabel produk dengan data
If not: 
- Cek database apakah ada data
- Cek console browser untuk error
```

### Step 4: Cek Apakah Create Page Accessible
```
URL: http://127.0.0.1:8000/admin/products/create
Expected: Form create produk muncul
If not:
- Cek route:list apakah route ada
- Cek file CreateProduct.php ada
- Cek ProductForm.php ada
```

### Step 5: Test Create Functionality
```
1. Isi form dengan data valid
2. Klik "Create"
3. Expected: Redirect ke list page dengan success message
4. Verify: Data muncul di tabel

If error:
- Cek validation rules
- Cek database constraints
- Cek browser console
```

---

## Manual Testing Commands

### Test Database Connection
```bash
php artisan tinker
```
```php
// Test connection
DB::connection()->getPdo();

// Test query
DB::table('products')->count();

// Test create
$product = new App\Models\Product();
$product->name = 'Test Product';
$product->code = 'TEST001';
$product->price = 10000;
$product->stock = 0;
$product->save();

// Verify
App\Models\Product::where('code', 'TEST001')->first();
```

### Test Model Relations
```php
$product = App\Models\Product::first();
$product->materials; // Should return collection

$material = App\Models\RawMaterial::first();
$material->products; // Should return collection
```

### Test Resource Registration
```php
// Check if resources are registered
$panel = Filament\Facades\Filament::getCurrentPanel();
$resources = $panel->getResources();
dd($resources);
```

---

## Quick Fixes

### Fix 1: Regenerate Everything
```bash
# Clear all cache
php artisan optimize:clear

# Regenerate autoload
composer dump-autoload

# Clear Filament cache
php artisan filament:cache-components

# Restart server
php artisan serve
```

### Fix 2: Reset Database
```bash
php artisan migrate:fresh --seed
```

### Fix 3: Check Permissions (Linux/Mac)
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Fix 4: Reinstall Filament (Last Resort)
```bash
composer require filament/filament:"^3.0" --with-all-dependencies
php artisan filament:install --panels
```

---

## Verification Checklist

Setelah troubleshooting, verify:

- [ ] Login berhasil
- [ ] Dashboard muncul
- [ ] Menu sidebar muncul
- [ ] List page products accessible
- [ ] Create page products accessible
- [ ] Form create berfungsi
- [ ] Data tersimpan ke database
- [ ] Edit page accessible
- [ ] Form edit berfungsi
- [ ] Data terupdate
- [ ] Delete berfungsi
- [ ] Data terhapus dari database

---

## Error Messages & Solutions

### Error: "Target class [ProductResource] does not exist"
**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Error: "SQLSTATE[HY000] [2002] Connection refused"
**Solution:**
- Cek database service running
- Cek credentials di .env
- Untuk SQLite: cek file database.sqlite ada

### Error: "419 | Page Expired"
**Solution:**
- Refresh page
- Clear browser cache
- Check SESSION_DRIVER in .env

### Error: "Method Illuminate\Database\Eloquent\Collection::save does not exist"
**Solution:**
- Jangan save collection, save model individual
- Loop through collection dan save satu-satu

### Error: "Undefined relationship 'materials'"
**Solution:**
- Pastikan method materials() ada di Product model
- Pastikan return type HasMany atau BelongsToMany

---

## Contact & Support

Jika masih ada masalah:

1. **Cek Error Log:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable Debug Mode:**
   Di `.env`:
   ```
   APP_DEBUG=true
   ```

3. **Browser Console:**
   - Buka Developer Tools (F12)
   - Cek tab Console
   - Cek tab Network

4. **Screenshot Error:**
   - Ambil screenshot error message
   - Catat URL yang error
   - Catat langkah-langkah reproduce

---

**Last Updated:** 17 Oktober 2025
**Status:** Troubleshooting Guide
