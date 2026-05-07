# Setup PHP Artisan Serve

## ✅ Perubahan yang Dilakukan

### 1. **Password Toggle dengan JavaScript**
**Files:**
- `resources/views/filament/hooks/password-toggle-script.blade.php` - Script untuk toggle
- `app/Providers/Filament/AdminPanelProvider.php` - Render hook integration

Fitur:
- 👁️ **Toggle Show/Hide Password** - Klik icon mata untuk lihat/sembunyikan password
- 🎯 Otomatis muncul di halaman login
- ⚡ Menggunakan JavaScript vanilla (no dependencies)
- 🎨 Icon SVG yang smooth
- 🔒 Aman - hanya toggle di client-side

### 2. **AdminPanelProvider Updated**
**File:** `app/Providers/Filament/AdminPanelProvider.php`

- Menambahkan render hook untuk password toggle
- Semua konfigurasi tetap sama (branding, colors, dll)

## 🚀 Cara Menjalankan dengan PHP Artisan Serve

### **Langkah 1: Pastikan Database Sudah Setup**
```bash
# Cek file .env ada dan konfigurasi database benar
# Jika belum ada .env, copy dari .env.example
copy .env.example .env

# Generate application key
php artisan key:generate
```

### **Langkah 2: Jalankan Migration (jika belum)**
```bash
php artisan migrate
```

### **Langkah 3: Jalankan Seeder (jika perlu user)**
```bash
php artisan db:seed
```

### **Langkah 4: Clear Cache**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### **Langkah 5: Jalankan Server**
```bash
php artisan serve
```

Server akan berjalan di: **http://127.0.0.1:8000**

### **Langkah 6: Akses Admin Panel**
Buka browser dan akses:
```
http://127.0.0.1:8000/admin
```

## 🔐 Fitur Login Page

### **Toggle Password Visibility**
1. Masukkan email Anda
2. Masukkan password
3. **Klik icon mata** di sebelah kanan field password untuk:
   - 👁️ **Tampilkan password** (icon mata terbuka)
   - 🙈 **Sembunyikan password** (icon mata tertutup)
4. Centang "Ingat saya" jika ingin tetap login
5. Klik tombol "Sign in"

### **Screenshot Fitur**
```
┌─────────────────────────────────┐
│  Manufacturing ERP              │
│                                 │
│  Email:                         │
│  ┌───────────────────────────┐ │
│  │ email@example.com         │ │
│  └───────────────────────────┘ │
│                                 │
│  Password:                      │
│  ┌─────────────────────────┬─┐ │
│  │ ••••••••••••            │👁│ │ <- Klik untuk toggle
│  └─────────────────────────┴─┘ │
│                                 │
│  ☑ Ingat saya                   │
│                                 │
│  ┌───────────────────────────┐ │
│  │      Sign in              │ │
│  └───────────────────────────┘ │
└─────────────────────────────────┘
```

## 🎯 Perbedaan XAMPP vs Artisan Serve

### **XAMPP (Apache)**
- URL: `http://localhost/perusahaan_manufaktur/public/admin`
- Port: 80 (default Apache)
- Perlu Apache & MySQL running
- Cocok untuk production-like environment

### **PHP Artisan Serve**
- URL: `http://127.0.0.1:8000/admin`
- Port: 8000 (default Laravel)
- Built-in PHP development server
- Cocok untuk development
- Lebih mudah untuk testing

## 📝 Tips

### **Custom Port**
Jika port 8000 sudah digunakan:
```bash
php artisan serve --port=8080
```
Akses: `http://127.0.0.1:8080/admin`

### **Custom Host**
Untuk akses dari komputer lain di jaringan:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
Akses: `http://[IP-KOMPUTER-ANDA]:8000/admin`

### **Background Process**
Di Windows, untuk run di background:
```bash
start /B php artisan serve
```

### **Stop Server**
Tekan `Ctrl + C` di terminal

## 🔧 Troubleshooting

### **Error: Port already in use**
```bash
# Gunakan port lain
php artisan serve --port=8080
```

### **Error: Database connection**
```bash
# Cek .env file
# Pastikan DB_CONNECTION, DB_DATABASE, dll sudah benar
php artisan config:clear
```

### **Error: 404 Not Found**
```bash
# Clear route cache
php artisan route:clear
php artisan optimize:clear
```

### **Login tidak bisa**
```bash
# Pastikan ada user di database
php artisan db:seed

# Atau buat user manual
php artisan tinker
>>> \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => bcrypt('password')]);
```

### **CSS/JS tidak muncul**
```bash
# Build assets
npm run build

# Atau untuk development
npm run dev
```

## 🎨 Fitur Password Toggle

### **Cara Kerja**
- Field password menggunakan `->revealable()` dari Filament
- Icon mata otomatis muncul di sebelah kanan field
- Klik icon untuk toggle antara:
  - Password tersembunyi (••••••)
  - Password terlihat (plain text)
- Aman karena hanya di client-side, tidak mengubah security

### **Kustomisasi**
Jika ingin ubah behavior, edit file:
```
app/Filament/Pages/Auth/Login.php
```

Method `getPasswordFormComponent()`:
```php
protected function getPasswordFormComponent(): Component
{
    return TextInput::make('password')
        ->label('Password')
        ->password()
        ->revealable()  // <- Ini yang membuat toggle
        ->required()
        ->placeholder('Masukkan password Anda');
}
```

## 📚 Referensi

- [Laravel Artisan Serve](https://laravel.com/docs/artisan#serving-your-application)
- [Filament Custom Login](https://filamentphp.com/docs/panels/users#customizing-the-login-page)
- [Filament Forms](https://filamentphp.com/docs/forms/fields/text-input)

---

**Selamat mencoba!** 🚀

Jika ada pertanyaan atau masalah, silakan cek troubleshooting di atas.
