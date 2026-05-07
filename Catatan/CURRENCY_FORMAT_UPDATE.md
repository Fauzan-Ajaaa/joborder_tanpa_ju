# Update Format Mata Uang dari IDR ke Rp.

## Ringkasan Perubahan
Semua tampilan mata uang di aplikasi telah diubah dari format "IDR" menjadi "Rp." dengan format angka Indonesia (pemisah ribuan titik, pemisah desimal koma).

## File yang Dimodifikasi

### 1. **Payrolls (Penggajian)**

#### File: `app/Filament/Admin/Resources/Payrolls/Tables/PayrollsTable.php`
- **Kolom yang diubah:**
  - `base_salary` - Gaji Pokok
  - `allowances` - Tunjangan
  - `overtime` - Lembur
  - `bonuses` - Bonus
  - `deductions` - Potongan
  - `net_salary` - Gaji Bersih

- **Format baru:** `Rp. 1.000.000` (dengan pemisah ribuan titik)

#### File: `app/Filament/Admin/Resources/Payrolls/Schemas/PayrollForm.php`
- Sudah menggunakan prefix `Rp` (tidak ada perubahan diperlukan)

---

### 2. **Products (Produk)**

#### File: `app/Filament/Admin/Resources/Products/Tables/ProductsTable.php`
- **Kolom yang diubah:**
  - `price` - Harga Produk

- **Format baru:** `Rp. 50.000`

#### File: `app/Filament/Admin/Resources/Products/Schemas/ProductForm.php`
- Sudah menggunakan prefix `Rp` (tidak ada perubahan diperlukan)

---

### 3. **Raw Materials (Bahan Baku)**

#### File: `app/Filament/Admin/Resources/RawMaterials/Tables/RawMaterialsTable.php`
- **Kolom yang diubah:**
  - `price_per_unit` - Harga per Unit

- **Format baru:** `Rp. 10.000`

#### File: `app/Filament/Admin/Resources/RawMaterials/Schemas/RawMaterialForm.php`
- Sudah menggunakan prefix `Rp` (tidak ada perubahan diperlukan)

---

### 4. **Transactions (Transaksi)**

#### File: `app/Filament/Admin/Resources/Transactions/Tables/TransactionsTable.php`
- **Kolom yang diubah:**
  - `total_amount` - Total Transaksi

- **Format baru:** `Rp. 500.000`

#### File: `app/Filament/Admin/Resources/Transactions/Schemas/TransactionForm.php`
- **Field yang ditambahkan prefix `Rp`:**
  - `unit_price` - Harga Satuan (untuk penjualan produk)
  - `subtotal` - Subtotal (untuk penjualan produk)
  - `unit_price` - Harga Satuan (untuk pembelian bahan baku)
  - `cost` - Total Harga (untuk pembelian bahan baku)

---

### 5. **Expenses (Pengeluaran)**

#### File: `app/Filament/Admin/Resources/Expenses/Tables/ExpensesTable.php`
- **Kolom yang diubah:**
  - `amount` - Jumlah Pengeluaran

- **Format baru:** `Rp. 250.000`

#### File: `app/Filament/Admin/Resources/Expenses/Schemas/ExpenseForm.php`
- **Field yang ditambahkan prefix `Rp`:**
  - `amount` - Jumlah Pengeluaran

---

### 6. **Chart of Accounts (Bagan Akun)**

#### File: `app/Filament/Admin/Resources/ChartOfAccounts/Tables/ChartOfAccountsTable.php`
- **Kolom yang diubah:**
  - `balance` - Saldo Akun

- **Format baru:** `Rp. 1.000.000`

#### File: `app/Filament/Admin/Resources/ChartOfAccounts/Schemas/ChartOfAccountForm.php`
- **Field yang ditambahkan prefix `Rp`:**
  - `balance` - Saldo Akun

---

## Detail Implementasi

### Format Tampilan di Tabel
Semua kolom mata uang di tabel menggunakan format:
```php
->money('IDR')
->formatStateUsing(fn ($state) => 'Rp. ' . number_format($state, 0, ',', '.'))
```

**Penjelasan:**
- `money('IDR')` - Tetap menggunakan currency code IDR untuk validasi
- `formatStateUsing()` - Custom formatter untuk menampilkan "Rp." dengan format Indonesia
- `number_format($state, 0, ',', '.')` - Format angka dengan:
  - 0 desimal
  - Koma (,) sebagai pemisah desimal
  - Titik (.) sebagai pemisah ribuan

### Format Input di Form
Semua field mata uang di form menggunakan:
```php
->prefix('Rp')
```

**Hasil:**
- Input field akan menampilkan "Rp" di sebelah kiri
- User hanya perlu memasukkan angka tanpa simbol mata uang

---

## Contoh Tampilan

### Sebelum:
- Tabel: `IDR 1,000,000.00`
- Form: Tidak ada prefix

### Sesudah:
- Tabel: `Rp. 1.000.000`
- Form: `Rp [input field]`

---

## Testing

Untuk memastikan perubahan berfungsi dengan baik, lakukan testing pada:

1. ✅ **Payrolls** - Buat/edit payroll dan periksa tampilan di tabel
2. ✅ **Products** - Buat/edit produk dan periksa harga
3. ✅ **Raw Materials** - Buat/edit bahan baku dan periksa harga per unit
4. ✅ **Transactions** - Buat transaksi penjualan/pembelian dan periksa total
5. ✅ **Expenses** - Buat/edit pengeluaran dan periksa jumlah
6. ✅ **Chart of Accounts** - Buat/edit akun dan periksa saldo

---

## Catatan Penting

1. **Konsistensi Format**: Semua tampilan mata uang sekarang konsisten menggunakan format "Rp." dengan pemisah ribuan titik
2. **Database**: Tidak ada perubahan pada struktur database, hanya tampilan yang diubah
3. **Validasi**: Tetap menggunakan `money('IDR')` untuk validasi yang benar
4. **User Experience**: Lebih mudah dibaca dengan format Indonesia yang familiar

---

## Tanggal Update
21 Oktober 2025
