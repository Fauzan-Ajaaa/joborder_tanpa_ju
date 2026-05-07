# Modul Pembelian Bahan Baku

## Fitur Lengkap

### 1. Pembelian (Purchase Orders)
- ✅ Header Pembelian dengan nomor PO otomatis
- ✅ Detail item pembelian (multiple items)
- ✅ **FOB Cost** (Freight on Board / Biaya Pengiriman)
- ✅ **PPN** (Pajak Pertambahan Nilai) dengan rate configurable
- ✅ Perhitungan otomatis: Subtotal + FOB + PPN = Total
- ✅ Status: Draft, Approved, Received, Cancelled
- ✅ Update stok bahan baku otomatis saat status "Received"

### 2. Retur Pembelian (Purchase Returns)
- ✅ Retur dari pembelian yang sudah ada
- ✅ Multiple alasan retur: Damaged, Wrong Item, Excess, Quality Issue, Other
- ✅ Detail item yang diretur
- ✅ Status: Pending, Approved, Completed
- ✅ Pengurangan stok otomatis saat retur approved

## Database Structure

### Tabel: `purchases`
```sql
- id
- purchase_number (PO-YYYYMMDD-0001)
- purchase_date
- supplier_id
- subtotal
- fob_cost (Biaya Pengiriman)
- ppn_rate (Default 11%)
- ppn_amount (Calculated)
- total_amount (Subtotal + FOB + PPN)
- status (draft/approved/received/cancelled)
- notes
- timestamps
```

### Tabel: `purchase_items`
```sql
- id
- purchase_id
- raw_material_id
- quantity
- unit_price
- subtotal (quantity * unit_price)
- timestamps
```

### Tabel: `purchase_returns`
```sql
- id
- return_number (RTN-YYYYMMDD-0001)
- purchase_id
- return_date
- reason (damaged/wrong_item/excess/quality_issue/other)
- notes
- total_return_amount
- status (pending/approved/completed)
- timestamps
```

### Tabel: `purchase_return_items`
```sql
- id
- purchase_return_id
- purchase_item_id
- raw_material_id
- quantity
- unit_price
- subtotal
- timestamps
```

## Perhitungan Biaya

### Formula:
```
Subtotal = Σ (Quantity × Unit Price) untuk semua items
PPN Amount = (Subtotal + FOB Cost) × (PPN Rate / 100)
Total Amount = Subtotal + FOB Cost + PPN Amount
```

### Contoh:
```
Item 1: 100 kg × Rp 5,000 = Rp 500,000
Item 2: 50 kg × Rp 10,000 = Rp 500,000
-------------------------------------------
Subtotal                    = Rp 1,000,000
FOB Cost (Pengiriman)       = Rp   100,000
-------------------------------------------
Subtotal + FOB              = Rp 1,100,000
PPN 11%                     = Rp   121,000
-------------------------------------------
TOTAL                       = Rp 1,221,000
```

## Models

### 1. Purchase Model
**File:** `app/Models/Purchase.php`

**Features:**
- Auto-generate purchase_number
- Calculate totals automatically
- Relasi: supplier, items, returns

**Methods:**
- `calculateTotals()` - Recalculate subtotal, PPN, total
- `receive()` - Update status ke received & update stok
- `cancel()` - Cancel purchase order

### 2. PurchaseItem Model
**File:** `app/Models/PurchaseItem.php`

**Features:**
- Auto-calculate subtotal
- Trigger parent purchase calculateTotals()

### 3. PurchaseReturn Model
**File:** `app/Models/PurchaseReturn.php`

**Features:**
- Auto-generate return_number
- Calculate return totals
- Relasi: purchase, items

### 4. PurchaseReturnItem Model
**File:** `app/Models/PurchaseReturnItem.php`

**Features:**
- Auto-calculate subtotal
- Trigger parent return calculateTotals()

## Controllers

### 1. PurchaseController
**File:** `app/Http/Controllers/PurchaseController.php`

**Routes:**
- GET `/purchases` - List all purchases
- GET `/purchases/create` - Form create purchase
- POST `/purchases` - Store new purchase
- GET `/purchases/{id}` - View purchase detail
- GET `/purchases/{id}/edit` - Form edit purchase
- PUT `/purchases/{id}` - Update purchase
- DELETE `/purchases/{id}` - Delete purchase
- POST `/purchases/{id}/receive` - Mark as received
- POST `/purchases/{id}/approve` - Approve purchase

### 2. PurchaseReturnController
**File:** `app/Http/Controllers/PurchaseReturnController.php`

**Routes:**
- GET `/purchase-returns` - List all returns
- GET `/purchase-returns/create` - Form create return
- POST `/purchase-returns` - Store new return
- GET `/purchase-returns/{id}` - View return detail
- POST `/purchase-returns/{id}/approve` - Approve return

## Views

### Purchase Views
1. **Index** - `resources/views/purchases/index.blade.php`
   - List semua purchase orders
   - Filter by status, supplier, date range
   - Search by PO number
   - Bulk actions

2. **Create** - `resources/views/purchases/create.blade.php`
   - Form header (supplier, date, FOB, PPN rate)
   - Dynamic item rows (add/remove)
   - Real-time calculation
   - Save as draft or submit

3. **Edit** - `resources/views/purchases/edit.blade.php`
   - Edit purchase (only if status = draft)
   - Update items
   - Recalculate totals

4. **Show** - `resources/views/purchases/show.blade.php`
   - View purchase detail
   - Print PO
   - Actions: Approve, Receive, Cancel
   - Link to create return

### Purchase Return Views
1. **Index** - `resources/views/purchase-returns/index.blade.php`
   - List semua retur
   - Filter by status, date
   - Search by return number

2. **Create** - `resources/views/purchase-returns/create.blade.php`
   - Select purchase order
   - Select items to return
   - Input quantity & reason
   - Calculate return amount

3. **Show** - `resources/views/purchase-returns/show.blade.php`
   - View return detail
   - Approve/reject actions

## Form Requests

### 1. PurchaseRequest
**File:** `app/Http/Requests/PurchaseRequest.php`

**Validation:**
```php
'purchase_date' => 'required|date',
'supplier_id' => 'required|exists:suppliers,id',
'fob_cost' => 'required|numeric|min:0',
'ppn_rate' => 'required|numeric|min:0|max:100',
'items' => 'required|array|min:1',
'items.*.raw_material_id' => 'required|exists:raw_materials,id',
'items.*.quantity' => 'required|numeric|min:0.01',
'items.*.unit_price' => 'required|numeric|min:0',
```

### 2. PurchaseReturnRequest
**File:** `app/Http/Requests/PurchaseReturnRequest.php`

**Validation:**
```php
'purchase_id' => 'required|exists:purchases,id',
'return_date' => 'required|date',
'reason' => 'required|in:damaged,wrong_item,excess,quality_issue,other',
'items' => 'required|array|min:1',
'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
'items.*.quantity' => 'required|numeric|min:0.01',
```

## Routes

**File:** `routes/web.php`

```php
// Purchases
Route::resource('purchases', PurchaseController::class);
Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
    ->name('purchases.receive');
Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])
    ->name('purchases.approve');
Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
    ->name('purchases.cancel');

// Purchase Returns
Route::resource('purchase-returns', PurchaseReturnController::class);
Route::post('purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve'])
    ->name('purchase-returns.approve');
```

## Business Logic

### 1. Create Purchase
1. User input header (supplier, date, FOB, PPN rate)
2. User add items (material, qty, price)
3. System calculate subtotal per item
4. System calculate total: subtotal + FOB + PPN
5. Save as draft
6. User can approve → status = approved
7. User can receive → status = received & update stock

### 2. Receive Purchase
1. Check status = approved
2. Update status = received
3. Loop through items:
   - Add quantity to raw_material stock
   - Create transaction record (optional)
4. Save purchase

### 3. Create Return
1. Select purchase (status = received)
2. Show items from purchase
3. User select items to return
4. Input return quantity (max = purchased qty)
5. Select reason
6. Calculate return amount
7. Save as pending
8. Admin approve → reduce stock

### 4. Approve Return
1. Check status = pending
2. Update status = approved
3. Loop through return items:
   - Reduce quantity from raw_material stock
   - Create transaction record (optional)
4. Update return status = completed

## UI Features

### Purchase Form
- ✅ Supplier dropdown (searchable)
- ✅ Date picker
- ✅ FOB cost input
- ✅ PPN rate input (default 11%)
- ✅ Dynamic item rows:
  - Material dropdown
  - Quantity input
  - Unit price input
  - Subtotal (auto-calculated)
  - Remove button
- ✅ Add item button
- ✅ Summary section:
  - Subtotal
  - FOB Cost
  - PPN Amount
  - **Total Amount** (bold, large)
- ✅ Notes textarea
- ✅ Save as Draft / Submit button

### Purchase Detail
- ✅ Header info (PO number, date, supplier, status)
- ✅ Items table
- ✅ Cost breakdown
- ✅ Action buttons:
  - Approve (if draft)
  - Receive (if approved)
  - Cancel
  - Create Return (if received)
  - Print PO
  - Edit (if draft)
  - Delete (if draft)

### Return Form
- ✅ Purchase selection
- ✅ Return date
- ✅ Reason dropdown
- ✅ Items from purchase (checkboxes)
- ✅ Return quantity input
- ✅ Return amount calculation
- ✅ Notes

## Status Flow

### Purchase Status:
```
Draft → Approved → Received
  ↓
Cancelled
```

### Return Status:
```
Pending → Approved → Completed
```

## Permissions (Optional)

```php
'create_purchase'
'edit_purchase'
'delete_purchase'
'approve_purchase'
'receive_purchase'
'cancel_purchase'
'create_return'
'approve_return'
```

## Next Steps

1. ✅ Migration created
2. ✅ Models created (Purchase, PurchaseItem)
3. ⏳ Complete remaining models (PurchaseReturn, PurchaseReturnItem)
4. ⏳ Create Controllers
5. ⏳ Create FormRequests
6. ⏳ Create Views
7. ⏳ Add Routes
8. ⏳ Test CRUD operations
9. ⏳ Test calculations
10. ⏳ Test stock updates

## Testing Checklist

- [ ] Create purchase with multiple items
- [ ] Calculate totals correctly (subtotal + FOB + PPN)
- [ ] Approve purchase
- [ ] Receive purchase & check stock updated
- [ ] Create return from purchase
- [ ] Approve return & check stock reduced
- [ ] Cancel purchase
- [ ] Edit draft purchase
- [ ] Delete draft purchase
- [ ] Print PO
- [ ] Filter & search purchases
- [ ] Pagination works
