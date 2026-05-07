# Payroll Tax Update - Dokumentasi

## Perubahan yang Dilakukan

### 1. **Field Tax Diubah Menjadi Persentase**
   - Field `tax` sekarang menerima input dalam bentuk persentase (0-100%)
   - Ditampilkan dengan suffix "%" di form dan tabel
   - Validasi: minimum 0%, maksimum 100%

### 2. **Net Salary Dihitung Otomatis**
   - Net Salary sekarang dihitung secara otomatis berdasarkan formula:
     ```
     Gross Salary = Base Salary + Allowances + Overtime + Bonuses
     Tax Amount = (Gross Salary × Tax%) / 100
     Net Salary = Gross Salary - Deductions - Tax Amount
     ```
   - Field Net Salary di-disable (read-only) di form
   - Perhitungan terjadi secara real-time saat mengubah nilai:
     - Base Salary
     - Allowances
     - Overtime
     - Bonuses
     - Deductions
     - Tax (%)

### 3. **Format Currency**
   - Semua field yang berhubungan dengan uang ditampilkan dengan format IDR (Rupiah)
   - Di form: menggunakan prefix "Rp"
   - Di tabel: menggunakan format currency IDR lengkap

### 4. **Perhitungan Otomatis di Model**
   - Method `calculateNetSalary()` ditambahkan di model `Payroll`
   - Perhitungan otomatis terjadi saat:
     - Create payroll baru
     - Update payroll yang sudah ada
   - Menggunakan event listener Laravel (boot method)

## File yang Dimodifikasi

1. **app/Filament/Admin/Resources/Payrolls/Schemas/PayrollForm.php**
   - Menambahkan reactive calculation untuk semua field yang mempengaruhi net salary
   - Menambahkan method `calculateNetSalary()`
   - Menambahkan prefix "Rp" untuk field currency
   - Menambahkan suffix "%" dan validasi untuk field tax

2. **app/Filament/Admin/Resources/Payrolls/Tables/PayrollsTable.php**
   - Mengubah format kolom currency dari `numeric()` ke `money('IDR')`
   - Menambahkan suffix "%" untuk kolom tax

3. **app/Models/Payroll.php**
   - Menambahkan method `calculateNetSalary()`
   - Menambahkan event listener di `boot()` method untuk auto-calculate saat create/update

4. **database/migrations/2025_10_20_022539_update_payrolls_tax_to_percentage.php**
   - Migration untuk mengupdate data payroll yang sudah ada
   - Menghitung ulang net_salary untuk semua record

## Cara Penggunaan

### Membuat Payroll Baru
1. Buka halaman Payrolls
2. Klik "New payroll"
3. Isi data:
   - Employee
   - Payroll Number
   - Period Start & End
   - Base Salary (contoh: 15000000)
   - Allowances (contoh: 0)
   - Overtime (contoh: 0)
   - Bonuses (contoh: 500000)
   - Deductions (contoh: 0)
   - **Tax (%)** (contoh: 5 untuk 5%)
4. Net Salary akan otomatis terhitung dan muncul
5. Klik Save

### Contoh Perhitungan
Jika:
- Base Salary: Rp 15.000.000
- Allowances: Rp 0
- Overtime: Rp 0
- Bonuses: Rp 500.000
- Deductions: Rp 0
- Tax: 5%

Maka:
- Gross Salary = 15.000.000 + 0 + 0 + 500.000 = Rp 15.500.000
- Tax Amount = (15.500.000 × 5) / 100 = Rp 775.000
- **Net Salary = 15.500.000 - 0 - 775.000 = Rp 14.725.000**

## Catatan Penting

1. **Tax sebagai Persentase**: Pastikan memasukkan nilai tax dalam bentuk persentase (0-100), bukan nominal rupiah
2. **Net Salary Read-Only**: Field Net Salary tidak bisa diubah manual karena dihitung otomatis
3. **Real-time Calculation**: Perhitungan terjadi secara real-time di form saat mengubah nilai field
4. **Data Lama**: Data payroll yang sudah ada telah diupdate melalui migration

## Testing

Untuk memastikan fitur berjalan dengan baik:
1. Buat payroll baru dan periksa perhitungan net salary
2. Edit payroll yang sudah ada dan ubah nilai tax atau field lainnya
3. Periksa tampilan di tabel apakah tax ditampilkan dengan format persentase
4. Periksa apakah format currency sudah benar (IDR/Rupiah)
