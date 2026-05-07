# Auto-Generated Number Update - Dokumentasi

## Perubahan yang Dilakukan

### 1. **Payroll Number Auto-Generated**
   - Field `payroll_number` sekarang otomatis terisi saat membuat payroll baru
   - Format: `PAY-YYYYMM-XXXX` (contoh: PAY-202510-0001)
   - Field di-disable di form (read-only)
   - Nomor increment berdasarkan bulan dan tahun

### 2. **Employee Number Auto-Generated**
   - Field `employee_number` sekarang otomatis terisi saat membuat karyawan baru
   - Format: `EMP-XXXX` (contoh: EMP-0001)
   - Field di-disable di form (read-only)
   - Nomor increment berdasarkan total karyawan

### 3. **Base Salary Auto-Fill dari Data Employee**
   - Saat memilih employee di form Payroll, field `base_salary` akan otomatis terisi
   - Nilai diambil dari `base_salary` yang ada di data employee
   - Menggunakan reactive form untuk update real-time
   - User masih bisa mengubah nilai jika diperlukan

### 4. **Format Currency pada Base Salary**
   - Field `base_salary` di form Employee sekarang menggunakan prefix "Rp"
   - Konsisten dengan format currency di Payroll

## File yang Dimodifikasi

1. **app/Filament/Admin/Resources/Payrolls/Schemas/PayrollForm.php**
   - Field `payroll_number` diubah menjadi disabled
   - Menambahkan helper text untuk informasi auto-generate
   - Set `dehydrated(false)` agar tidak di-submit ke database (akan di-generate otomatis)
   - Menambahkan reactive pada `employee_id` untuk auto-fill base_salary
   - Menambahkan `afterStateUpdated` callback untuk mengambil base_salary dari employee

2. **app/Filament/Admin/Resources/Employees/Schemas/EmployeeForm.php**
   - Field `employee_number` diubah menjadi disabled
   - Menambahkan helper text untuk informasi auto-generate
   - Set `dehydrated(false)` agar tidak di-submit ke database (akan di-generate otomatis)
   - Menambahkan prefix "Rp" pada field `base_salary`

3. **app/Models/Payroll.php** (sudah ada sebelumnya)
   - Method `boot()` dengan event `creating` untuk generate payroll_number
   - Format: PAY-{YearMonth}-{Sequential Number}

4. **app/Models/Employee.php** (sudah ada sebelumnya)
   - Method `boot()` dengan event `creating` untuk generate employee_number
   - Format: EMP-{Sequential Number}

## Cara Penggunaan

### Membuat Employee Baru
1. Buka halaman Employees
2. Klik "New employee"
3. Field "Employee Number" akan menampilkan "Auto-generated"
4. Isi data karyawan lainnya:
   - Name
   - Email
   - Phone
   - Address
   - Position
   - Department
   - Hire Date
   - Base Salary (dengan prefix Rp)
   - Status
5. Klik Save
6. Employee number akan otomatis tergenerate (contoh: EMP-0001, EMP-0002, dst)

### Membuat Payroll Baru
1. Buka halaman Payrolls
2. Klik "New payroll"
3. Field "Payroll Number" akan menampilkan "Auto-generated"
4. **Pilih Employee dari dropdown**
   - Setelah memilih employee, field "Base Salary" akan otomatis terisi sesuai base salary employee tersebut
5. Isi data payroll lainnya:
   - Period Start & End
   - Base Salary (sudah terisi otomatis, bisa diubah jika perlu)
   - Allowances, Overtime, Bonuses, Deductions
   - Tax (%)
   - Net Salary (otomatis terhitung)
6. Klik Save
7. Payroll number akan otomatis tergenerate (contoh: PAY-202510-0001)

## Contoh Format Number

### Employee Number
- Employee pertama: **EMP-0001**
- Employee kedua: **EMP-0002**
- Employee ke-100: **EMP-0100**

### Payroll Number
- Payroll pertama di Oktober 2025: **PAY-202510-0001**
- Payroll kedua di Oktober 2025: **PAY-202510-0002**
- Payroll pertama di November 2025: **PAY-202511-0001** (reset setiap bulan)

## Catatan Penting

1. **Auto-Generated Number**: Nomor akan otomatis tergenerate saat save, tidak perlu input manual
2. **Read-Only Field**: Field number di form tidak bisa diubah manual
3. **Sequential**: Nomor akan increment secara berurutan
4. **Monthly Reset (Payroll)**: Payroll number akan reset setiap bulan baru
5. **Unique**: Setiap nomor dijamin unique karena di-generate oleh sistem
6. **Base Salary Auto-Fill**: Saat memilih employee di payroll, base salary otomatis terisi dari data employee
7. **Editable Base Salary**: Meskipun auto-fill, base salary di payroll masih bisa diubah manual jika diperlukan

## Testing

Untuk memastikan fitur berjalan dengan baik:
1. Buat employee baru dan periksa apakah employee_number tergenerate otomatis
2. Buat payroll baru dan periksa apakah payroll_number tergenerate dengan format yang benar
3. Buat beberapa payroll di bulan yang sama, pastikan nomor increment dengan benar
4. Periksa apakah field number tidak bisa diubah manual di form
5. **Test Base Salary Auto-Fill**:
   - Buat employee baru dengan base salary tertentu (contoh: Rp 5.000.000)
   - Buat payroll baru, pilih employee tersebut
   - Periksa apakah base salary di payroll otomatis terisi dengan Rp 5.000.000
   - Coba ubah employee yang dipilih, periksa apakah base salary berubah sesuai employee baru
