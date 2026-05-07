# 🚀 Quick Reference: Produk dengan Bahan Baku

## 📌 Ringkasan Singkat

**Fitur:** Setiap produk memiliki komposisi bahan baku. Ketika produk dijual, stok bahan baku otomatis berkurang.

**Contoh:**
```
Jual 10 Roti Tawar
→ Stok Roti Tawar: -10
→ Stok Tepung: -5 kg (0.5 kg × 10)
→ Stok Gula: -1 kg (0.1 kg × 10)
```

---

## ⚡ Quick Commands

### Migration & Seeding
```bash
# Fresh migration dengan seed (produk, bahan baku, dan BOM)
php artisan migrate:fresh --seed

# Hanya migration baru
php artisan migrate
```

> 🔧 *Catatan:* `product_materials` table dan sedernya sudah dihapus; gunakan
> tabel BOM (`bill_of_materials`/`bill_of_material_items`) untuk komposisi produk.

### Testing di Tinker
```bash
php artisan tinker
```

```php
# Cek komposisi produk (BOM)
$product = Product::find(1);
foreach ($product->bomItems as $item) {
    echo "{$item->rawMaterial->name}: {$item->quantity_needed} {$item->unit}\n";
}

# Cek produk yang pakai bahan tertentu (relasi many-to-many melalui BOM)
RawMaterial::find(1)->products;
```

### Database Queries
```sql
-- Lihat komposisi produk
SELECT p.name, rm.name, pm.quantity_needed, rm.unit
FROM products p
JOIN product_materials pm ON p.id = pm.product_id
JOIN raw_materials rm ON pm.raw_material_id = rm.id;

-- Cek stok bahan baku
SELECT name, stock, unit, minimum_stock
FROM raw_materials
ORDER BY stock ASC;

-- Produk yang pakai bahan tertentu
SELECT p.name, pm.quantity_needed
FROM products p
JOIN product_materials pm ON p.id = pm.product_id
WHERE pm.raw_material_id = 1;
```

---

## 🎯 Common Tasks

### 1. Tambah Produk Baru dengan Bahan Baku
**URL:** `/admin/products/create`

**Steps:**
1. Isi nama, kode, harga, stok
2. Scroll ke "Komposisi Bahan Baku"
3. Klik "Tambah Bahan Baku"
4. Pilih bahan + jumlah
5. Save

### 2. Jual Produk
**URL:** `/admin/transactions/create`

**Steps:**
1. Tipe: Penjualan
2. Status: Selesai ⚠️ (penting!)
3. Tambah item produk
4. Save

**Result:** Stok produk & bahan baku berkurang otomatis

### 3. Cek Stok Bahan Baku
**URL:** `/admin/raw-materials`

**Info yang ditampilkan:**
- Nama bahan
- Stok saat ini
- Stok minimum
- Status (low stock warning)

---

## 🔍 Troubleshooting

### Stok tidak berkurang?
**Checklist:**
- [ ] Status transaksi = "Selesai"?
- [ ] Produk punya komposisi bahan baku?
- [ ] Tipe transaksi = "Penjualan"?

**Debug:**
```php
$product = Product::find(1);
dd($product->materials); // Harus ada data
```

### Error "Undefined relationship"?
**Fix:** Pastikan model Product punya method `materials()`

### Stok jadi negatif?
**Cause:** Tidak ada validasi stok minimum
**Solution:** Tambah validasi (lihat IMPLEMENTATION_SUMMARY.md)

---

## 📊 Data Structure

### Tabel product_materials
```
id | product_id | raw_material_id | quantity_needed | created_at | updated_at
1  | 1          | 1               | 0.50           | ...        | ...
2  | 1          | 2               | 0.10           | ...        | ...
```

### Relasi
```
Product (1) ←→ (N) ProductMaterial (N) ←→ (1) RawMaterial
```

---

## 💡 Tips & Tricks

### 1. Eager Loading (Performance)
```php
// ❌ Bad (N+1 query)
$products = Product::all();
foreach ($products as $product) {
    $product->materials; // Query per product
}

// ✅ Good (Single query)
$products = Product::with('materials.rawMaterial')->all();
```

### 2. Kalkulasi Biaya Produksi
```php
// Di model Product
public function getProductionCostAttribute()
{
    return $this->materials->sum(function($m) {
        return $m->quantity_needed * $m->rawMaterial->price_per_unit;
    });
}

// Usage
$product->production_cost;
```

### 3. Validasi Stok Sebelum Jual
```php
// Di TransactionForm atau CreateTransaction
foreach ($product->materials as $material) {
    $needed = $material->quantity_needed * $quantity;
    if ($material->rawMaterial->stock < $needed) {
        throw new \Exception("Stok {$material->rawMaterial->name} tidak cukup!");
    }
}
```

### 4. Bulk Update Stok
```php
// Update multiple materials sekaligus
DB::transaction(function() use ($product, $quantity) {
    foreach ($product->materials as $material) {
        $material->rawMaterial->decrement(
            'stock', 
            $material->quantity_needed * $quantity
        );
    }
});
```

---

## 📝 Code Snippets

### Get Total Material Cost
```php
$product = Product::find(1);
$totalCost = $product->materials->sum(function($m) {
    return $m->quantity_needed * $m->rawMaterial->price_per_unit;
});
```

### Check if Enough Stock
```php
function hasEnoughStock(Product $product, int $quantity): bool
{
    foreach ($product->materials as $material) {
        $needed = $material->quantity_needed * $quantity;
        if ($material->rawMaterial->stock < $needed) {
            return false;
        }
    }
    return true;
}
```

### Get Material Usage Report
```php
$material = RawMaterial::find(1);
$usage = DB::table('product_materials')
    ->join('products', 'product_materials.product_id', '=', 'products.id')
    ->join('transaction_items', 'products.id', '=', 'transaction_items.product_id')
    ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
    ->where('product_materials.raw_material_id', $material->id)
    ->where('transactions.type', 'sale')
    ->where('transactions.status', 'completed')
    ->selectRaw('SUM(product_materials.quantity_needed * transaction_items.quantity) as total_used')
    ->first();
```

---

## 🎨 UI Components

### Form: Repeater untuk Bahan Baku
```php
Repeater::make('materials')
    ->relationship()
    ->schema([
        Select::make('raw_material_id')
            ->options(RawMaterial::pluck('name', 'id'))
            ->required()
            ->searchable(),
        TextInput::make('quantity_needed')
            ->numeric()
            ->required(),
    ])
    ->columns(2)
    ->addActionLabel('Tambah Bahan Baku');
```

### Table: Display Materials
```php
// Di ProductResource table
Tables\Columns\TextColumn::make('materials_count')
    ->counts('materials')
    ->label('Jumlah Bahan'),
```

---

## 📚 File Locations

### Models
```
app/Models/Product.php
app/Models/RawMaterial.php
app/Models/ProductMaterial.php
```

### Migrations
```
database/migrations/2025_10_17_073204_create_product_materials_table.php
```

### Filament Resources
```
app/Filament/Admin/Resources/Products/Schemas/ProductForm.php
app/Filament/Admin/Resources/Transactions/Pages/CreateTransaction.php
app/Filament/Admin/Resources/Transactions/Pages/EditTransaction.php
```

### Seeders
```
database/seeders/ProductMaterialSeeder.php
database/seeders/DatabaseSeeder.php
```

### Documentation
```
PRODUCT_MATERIALS_FEATURE.md
README_PRODUCT_MATERIALS.md
TESTING_GUIDE.md
IMPLEMENTATION_SUMMARY.md
QUICK_REFERENCE.md (this file)
```

---

## 🔗 Related Features

### Existing Features
- ✅ Raw Materials Management
- ✅ Products Management
- ✅ Transactions (Sale/Production/Purchase)
- ✅ Stock Management

### New Features
- ✅ Product-Material Composition
- ✅ Automatic Stock Deduction

### Potential Integrations
- 📊 Accounting (COGS calculation)
- 📈 Reports (Material usage)
- 🔔 Notifications (Low stock alerts)
- 📦 Inventory Management

---

## ⚙️ Configuration

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Decimal Precision
```php
// config/database.php
'mysql' => [
    'strict' => true,
    'modes' => [
        'STRICT_TRANS_TABLES',
        'NO_ZERO_IN_DATE',
        'NO_ZERO_DATE',
        'ERROR_FOR_DIVISION_BY_ZERO',
    ],
],
```

---

## 🎓 Learning Resources

### Documentation
- [Laravel Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Filament Form Builder](https://filamentphp.com/docs/forms)
- [Database Normalization](https://en.wikipedia.org/wiki/Database_normalization)

### Video Tutorials
- Laravel Many-to-Many Relationships
- Filament Repeater Component
- Inventory Management Systems

---

## 📞 Support

### Need Help?
1. Check TESTING_GUIDE.md untuk test cases
2. Check IMPLEMENTATION_SUMMARY.md untuk detail teknis
3. Check README_PRODUCT_MATERIALS.md untuk panduan lengkap

### Report Issues
- Document the error message
- Note the steps to reproduce
- Check logs: `storage/logs/laravel.log`

---

## ✅ Quick Checklist

### Before Production
- [ ] Test all CRUD operations
- [ ] Test stock deduction accuracy
- [ ] Test with multiple products
- [ ] Test edge cases (negative stock, etc)
- [ ] Backup database
- [ ] Train users
- [ ] Monitor first week closely

### After Production
- [ ] Monitor error logs
- [ ] Check stock accuracy daily
- [ ] Gather user feedback
- [ ] Plan enhancements
- [ ] Document issues & solutions

---

**Last Updated:** 17 Oktober 2025
**Quick Access:** Keep this file bookmarked for fast reference!
