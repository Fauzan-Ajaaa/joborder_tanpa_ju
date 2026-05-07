# Panduan Testing: Fitur Produk dengan Bahan Baku

## 🧪 Test Cases

### Test Case 1: Membuat Produk dengan Komposisi Bahan Baku

**Tujuan:** Memastikan produk dapat dibuat dengan komposisi bahan baku

**Langkah:**
1. Buka `/admin/products/create`
2. Isi form:
   - Nama: Test Produk 1
   - Kode: TEST001
   - Harga: 10000
   - Stok: 0
3. Tambah bahan baku:
   - Tepung Terigu: 0.3 kg
   - Gula Pasir: 0.1 kg
4. Klik Save

**Expected Result:**
- ✅ Produk tersimpan
- ✅ Data di tabel `product_materials` ada 2 record
- ✅ Relasi `$product->materials` mengembalikan 2 item

**SQL Verification:**
```sql
SELECT * FROM product_materials WHERE product_id = [ID_PRODUK];
```

---

### Test Case 2: Penjualan Produk Mengurangi Stok Bahan Baku

**Tujuan:** Memastikan stok bahan baku berkurang saat produk dijual

**Pre-condition:**
- Produk "Roti Tawar" (ID: 1) ada dengan komposisi:
  - Tepung: 0.5 kg
  - Gula: 0.1 kg
- Stok Tepung: 500 kg
- Stok Gula: 300 kg

**Langkah:**
1. Buka `/admin/transactions/create`
2. Isi form:
   - Tipe: Penjualan
   - Status: Selesai
3. Tambah item:
   - Produk: Roti Tawar
   - Quantity: 10
   - Harga: 25000
4. Klik Save

**Expected Result:**
- ✅ Stok Roti Tawar: -10
- ✅ Stok Tepung: 495 kg (500 - 5)
- ✅ Stok Gula: 299 kg (300 - 1)

**SQL Verification:**
```sql
-- Cek stok sebelum
SELECT stock FROM raw_materials WHERE id IN (1, 2);

-- Lakukan transaksi

-- Cek stok sesudah
SELECT stock FROM raw_materials WHERE id IN (1, 2);
```

---

### Test Case 3: Status Pending Tidak Mengurangi Stok

**Tujuan:** Memastikan stok tidak berkurang jika status pending

**Langkah:**
1. Catat stok awal bahan baku
2. Buat transaksi penjualan dengan status: **Pending**
3. Cek stok bahan baku

**Expected Result:**
- ✅ Stok bahan baku TIDAK berubah
- ✅ Stok produk TIDAK berubah

---

### Test Case 4: Multiple Products dalam Satu Transaksi

**Tujuan:** Memastikan sistem dapat handle multiple products

**Langkah:**
1. Buat transaksi dengan 3 produk berbeda:
   - Roti Tawar: 5 unit
   - Roti Manis: 10 unit
   - Cake Coklat: 2 unit
2. Status: Selesai
3. Simpan

**Expected Result:**
- ✅ Stok semua produk berkurang
- ✅ Stok bahan baku berkurang sesuai total kebutuhan semua produk
- ✅ Tidak ada error

**Calculation Example:**
```
Tepung yang dibutuhkan:
- Roti Tawar: 0.5 × 5 = 2.5 kg
- Roti Manis: 0.3 × 10 = 3 kg
- Cake Coklat: 0.6 × 2 = 1.2 kg
Total: 6.7 kg
```

---

### Test Case 5: Edit Transaksi (Pending → Completed)

**Tujuan:** Memastikan stok berkurang saat status diubah

**Langkah:**
1. Buat transaksi dengan status: Pending
2. Catat stok bahan baku
3. Edit transaksi, ubah status ke: Selesai
4. Cek stok

**Expected Result:**
- ✅ Stok berkurang setelah status diubah ke completed

---

### Test Case 6: Produk Tanpa Komposisi

**Tujuan:** Memastikan sistem tidak error untuk produk tanpa bahan baku

**Langkah:**
1. Buat produk tanpa menambah komposisi bahan baku
2. Jual produk tersebut
3. Cek stok

**Expected Result:**
- ✅ Transaksi berhasil
- ✅ Stok produk berkurang
- ✅ Tidak ada error
- ✅ Stok bahan baku tidak berubah

---

## 🔍 Manual Testing Checklist

### Setup Testing
- [ ] Database di-migrate fresh
- [ ] Seeder dijalankan
- [ ] Login ke admin panel berhasil
- [ ] Semua menu dapat diakses

### CRUD Produk
- [ ] Create produk tanpa bahan baku
- [ ] Create produk dengan 1 bahan baku
- [ ] Create produk dengan multiple bahan baku
- [ ] Edit produk: tambah bahan baku
- [ ] Edit produk: hapus bahan baku
- [ ] Edit produk: ubah quantity bahan baku
- [ ] Delete produk (cascade delete product_materials)

### Transaksi Penjualan
- [ ] Create sale dengan status pending
- [ ] Create sale dengan status completed
- [ ] Edit sale: pending → completed
- [ ] Edit sale: completed → pending (cek apakah stok kembali?)
- [ ] Sale dengan multiple products
- [ ] Sale dengan quantity besar

### Validasi Stok
- [ ] Jual produk dengan stok bahan baku cukup
- [ ] Jual produk dengan stok bahan baku tidak cukup (apakah jadi negatif?)
- [ ] Cek stok bahan baku setelah multiple transaksi

### Edge Cases
- [ ] Produk dengan 0 quantity_needed
- [ ] Produk dengan quantity_needed sangat besar
- [ ] Transaksi dengan quantity 0
- [ ] Transaksi dengan quantity negatif (jika bisa input)
- [ ] Delete bahan baku yang digunakan produk (constraint error?)

---

## 🐛 Bug Testing Scenarios

### Scenario 1: Stok Negatif
**Steps:**
1. Set stok Tepung = 1 kg
2. Jual Roti Tawar 10 unit (butuh 5 kg)
3. Cek stok Tepung

**Current Behavior:** Stok menjadi -4 kg
**Expected Behavior:** Error atau warning

---

### Scenario 2: Concurrent Transactions
**Steps:**
1. Buka 2 browser/tab
2. Buat transaksi penjualan bersamaan
3. Cek konsistensi stok

**Potential Issue:** Race condition

---

### Scenario 3: Decimal Precision
**Steps:**
1. Buat produk dengan quantity_needed = 0.333
2. Jual 3 unit
3. Cek stok (apakah 0.999 atau 1.0?)

---

## 📊 Performance Testing

### Load Test
```php
// Test dengan banyak data
for ($i = 0; $i < 100; $i++) {
    Transaction::create([...]);
    // Cek waktu eksekusi
}
```

### Query Optimization
```sql
-- Cek N+1 query problem
-- Enable query log di Laravel
DB::enableQueryLog();

// Lakukan transaksi

dd(DB::getQueryLog());
```

---

## 🔧 Testing dengan Tinker

### Setup
```bash
php artisan tinker
```

### Test 1: Cek Relasi
```php
$product = Product::find(1);
$product->materials; // Harus return collection
$product->rawMaterials; // Harus return collection dengan pivot

$material = RawMaterial::find(1);
$material->products; // Harus return products yang menggunakan material ini
```

### Test 2: Manual Decrement
```php
$product = Product::find(1);
$quantity = 5;

foreach ($product->materials as $material) {
    $needed = $material->quantity_needed * $quantity;
    echo "{$material->rawMaterial->name}: {$needed}\n";
    $material->rawMaterial->decrement('stock', $needed);
}

// Cek stok
RawMaterial::find(1)->stock;
```

### Test 3: Simulasi Transaksi
```php
$transaction = Transaction::create([
    'type' => 'sale',
    'transaction_date' => now(),
    'status' => 'completed',
    'total_amount' => 50000,
]);

$transaction->items()->create([
    'product_id' => 1,
    'quantity' => 2,
    'unit_price' => 25000,
    'subtotal' => 50000,
]);

// Cek apakah stok berkurang
Product::find(1)->stock;
RawMaterial::find(1)->stock;
```

---

## 📈 Monitoring & Logging

### Log Stok Changes
Tambahkan logging untuk tracking:

```php
// Di CreateTransaction
Log::info('Stock deduction', [
    'transaction_id' => $transaction->id,
    'product_id' => $product->id,
    'product_quantity' => $item->quantity,
    'materials' => $product->materials->map(function($m) use ($item) {
        return [
            'material_id' => $m->raw_material_id,
            'material_name' => $m->rawMaterial->name,
            'quantity_deducted' => $m->quantity_needed * $item->quantity,
            'stock_before' => $m->rawMaterial->stock,
            'stock_after' => $m->rawMaterial->stock - ($m->quantity_needed * $item->quantity),
        ];
    }),
]);
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

---

## ✅ Acceptance Criteria

Fitur dianggap berhasil jika:

1. **Functional Requirements:**
   - ✅ Produk dapat memiliki multiple bahan baku
   - ✅ Quantity bahan baku dapat diatur per unit produk
   - ✅ Stok bahan baku berkurang otomatis saat penjualan
   - ✅ Perhitungan stok akurat
   - ✅ Tidak ada error saat transaksi

2. **Non-Functional Requirements:**
   - ✅ Response time < 2 detik untuk transaksi
   - ✅ UI intuitif dan mudah digunakan
   - ✅ Data konsisten setelah multiple transaksi
   - ✅ Dokumentasi lengkap

3. **Edge Cases Handled:**
   - ✅ Produk tanpa bahan baku
   - ✅ Multiple products dalam satu transaksi
   - ✅ Status pending tidak mengubah stok
   - ✅ Edit status transaksi

---

## 🎯 Next Steps After Testing

1. **Fix Bugs:** Perbaiki bug yang ditemukan
2. **Add Validation:** Tambah validasi stok minimum
3. **Add Notifications:** Notifikasi low stock
4. **Add Reports:** Laporan penggunaan bahan baku
5. **Optimize Queries:** Gunakan eager loading untuk performa
6. **Add Tests:** Unit test dan feature test otomatis
