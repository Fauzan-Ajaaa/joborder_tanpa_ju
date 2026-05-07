# File BTKL yang Tidak Terpakai (Bisa Dihapus)

Karena kita menggunakan **Payrolls** sebagai BTKL, file-file berikut tidak terpakai dan bisa dihapus:

## 1. Model
- `app/Models/Btkl.php` ❌ TIDAK TERPAKAI

## 2. Migration
- `database/migrations/2025_10_31_035120_add_employee_type_to_employees_table.php` ✅ TERPAKAI (jangan hapus)
- `database/migrations/2025_10_31_035154_create_btkls_table.php` ❌ TIDAK TERPAKAI (bisa dihapus)

## 3. Resources Filament
- `app/Filament/Admin/Resources/Btkls/` ❌ TIDAK TERPAKAI (semua folder)
  - BtklResource.php
  - Pages/CreateBtkl.php
  - Pages/EditBtkl.php
  - Pages/ListBtkls.php
  - Schemas/BtklForm.php
  - Tables/BtklsTable.php

## 4. Widgets
- `app/Filament/Admin/Resources/Products/Widgets/BtklSummaryWidget.php` ❌ TIDAK TERPAKAI

## 5. Relation Managers
- `app/Filament/Admin/Resources/Products/RelationManagers/BtklsRelationManager.php` ❌ TIDAK TERPAKAI

## Yang TETAP DIPAKAI (JANGAN HAPUS):
✅ `app/Models/Payroll.php` - Ini yang dipakai sebagai BTKL
✅ `app/Filament/Admin/Resources/Payrolls/` - Ini yang dipakai
✅ `database/migrations/*_add_product_id_to_payrolls_table.php` - Migration untuk Payrolls
✅ `database/migrations/*_add_work_date_to_payrolls_table.php` - Migration untuk Payrolls

## Cara Hapus Manual:
1. Hapus folder: `app/Filament/Admin/Resources/Btkls`
2. Hapus file: `app/Models/Btkl.php`
3. Hapus file: `database/migrations/2025_10_31_035154_create_btkls_table.php`
4. Hapus file: `app/Filament/Admin/Resources/Products/Widgets/BtklSummaryWidget.php`
5. Hapus file: `app/Filament/Admin/Resources/Products/RelationManagers/BtklsRelationManager.php`

Setelah hapus, jalankan:
```bash
php artisan optimize:clear
```

## Rollback Migration (Opsional):
Jika sudah terlanjur migrate tabel btkls, bisa rollback:
```bash
php artisan migrate:rollback --step=1
```
Tapi ini opsional, tidak masalah jika tabel btkls tetap ada di database (tidak mengganggu).
