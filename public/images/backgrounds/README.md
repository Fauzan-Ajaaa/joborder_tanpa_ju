# Background Images untuk Halaman Auth

Folder ini berisi gambar background yang digunakan untuk halaman sign-in dan register.

## Cara Menggunakan:

1. **Upload gambar background** Anda ke folder ini dengan nama `auth-bg.jpg`
2. **Format yang direkomendasikan:**
   - Format: JPG atau PNG
   - Ukuran minimal: 1920x1080 pixels
   - File size: < 2MB untuk optimasi loading

3. **Alternatif nama file:**
   Jika Anda ingin menggunakan nama file lain, ubah baris berikut di `resources/views/layouts/guest.blade.php`:
   ```blade
   style="background-image: url('{{ asset('images/backgrounds/auth-bg.jpg') }}');"
   ```
   Ganti `auth-bg.jpg` dengan nama file Anda.

## Tips:
- Gunakan gambar dengan kontras rendah agar teks tetap mudah dibaca
- Gambar dengan gradien atau pattern bekerja lebih baik
- Pastikan gambar memiliki lisensi untuk penggunaan komersial jika diperlukan
