# 🧪 Dashboard Testing Guide

## Pre-requisites

Pastikan database sudah memiliki data sample:
- ✅ Transactions (sale & purchase)
- ✅ Payrolls
- ✅ Expenses
- ✅ Products
- ✅ Raw Materials
- ✅ Employees
- ✅ Suppliers
- ✅ Chart of Accounts

## Testing Steps

### 1. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan filament:cache-components
```

### 2. Start Server
```bash
php artisan serve
```

### 3. Access Dashboard
```
URL: http://localhost:8000/admin
Login dengan kredensial admin
```

### 4. Visual Checks

#### ✅ StatsOverviewWidget
- [ ] 8 stat cards tampil dengan benar
- [ ] Nilai currency format IDR (Rp xxx.xxx)
- [ ] Trend percentage muncul (+x.x% atau -x.x%)
- [ ] Icon arrow up/down sesuai trend
- [ ] Color hijau untuk positif, merah untuk negatif
- [ ] Mini chart tampil di setiap card

#### ✅ RevenueChartWidget
- [ ] Chart tampil dengan 2 lines (Pendapatan & Beban)
- [ ] X-axis menampilkan 6 bulan terakhir
- [ ] Y-axis format "Rp xM"
- [ ] Tooltip menampilkan currency format
- [ ] Line hijau untuk pendapatan
- [ ] Line merah untuk beban
- [ ] Legend tampil di atas

#### ✅ BalanceSheetChartWidget
- [ ] Bar chart tampil dengan 3 bars
- [ ] Labels: Aset, Kewajiban, Modal
- [ ] Color: Biru, Merah, Hijau
- [ ] Y-axis format "Rp xM"
- [ ] Tooltip menampilkan currency format

#### ✅ RecentActivityWidget
- [ ] Table tampil dengan 5 kolom
- [ ] Headers: Tanggal, Deskripsi, Tipe, Jumlah, Status
- [ ] Data sorted by tanggal (terbaru di atas)
- [ ] Badge untuk Tipe (success/danger/info)
- [ ] Badge untuk Status (success/warning/info)
- [ ] Currency format di kolom Jumlah
- [ ] Color hijau untuk Penerimaan/Penjualan
- [ ] Color merah untuk Pengeluaran
- [ ] Hover effect pada row

#### ✅ QuickActionsWidget
- [ ] 6 action cards tampil dalam grid
- [ ] Setiap card memiliki icon, label, description
- [ ] Hover effect: card naik, shadow bertambah
- [ ] Icon scale up saat hover
- [ ] Arrow icon muncul saat hover
- [ ] Klik card redirect ke form create yang benar

### 5. Functional Tests

#### Test 1: Trend Calculation
1. Catat nilai pendapatan bulan ini
2. Tambah transaksi penjualan baru
3. Refresh dashboard
4. Nilai pendapatan harus bertambah

#### Test 2: Chart Data
1. Periksa data 6 bulan terakhir di chart
2. Bandingkan dengan data di database
3. Pastikan nilai sesuai

#### Test 3: Recent Activity
1. Buat transaksi baru (sale/purchase)
2. Refresh dashboard
3. Transaksi baru harus muncul di top

#### Test 4: Quick Actions
1. Klik "Buat Transaksi Penjualan"
2. Harus redirect ke form create transaction
3. Test semua 6 action cards

### 6. Responsive Test

#### Desktop (1920x1080)
- [ ] Stats: 4 cards per row
- [ ] Charts: 2 charts side by side
- [ ] Activity: Full width table
- [ ] Actions: 3 cards per row

#### Tablet (768x1024)
- [ ] Stats: 2 cards per row
- [ ] Charts: 1 chart per row (stacked)
- [ ] Activity: Scrollable table
- [ ] Actions: 2 cards per row

#### Mobile (375x667)
- [ ] Stats: 1 card per row
- [ ] Charts: 1 chart per row
- [ ] Activity: Scrollable table
- [ ] Actions: 1 card per row

### 7. Performance Test

#### Load Time
- [ ] Dashboard loads < 2 seconds
- [ ] Charts render < 1 second
- [ ] No console errors

#### Database Queries
```bash
# Enable query log
DB::enableQueryLog();

# After page load, check queries
dd(DB::getQueryLog());
```
- [ ] Total queries < 20
- [ ] No N+1 queries
- [ ] Eager loading used properly

### 8. Dark Mode Test

1. Toggle dark mode di Filament
2. Check semua widgets:
   - [ ] Background colors correct
   - [ ] Text readable
   - [ ] Charts visible
   - [ ] Badges contrast good
   - [ ] Hover effects work

### 9. Error Handling

#### Empty Data Test
1. Truncate semua table (backup dulu!)
2. Refresh dashboard
3. Check:
   - [ ] No PHP errors
   - [ ] Stats show 0 or "Rp 0"
   - [ ] Charts show empty state
   - [ ] Activity shows "Belum ada aktivitas"
   - [ ] Quick actions still work

#### Invalid Data Test
1. Insert data dengan tanggal invalid
2. Insert data dengan amount null
3. Refresh dashboard
4. Check:
   - [ ] No PHP errors
   - [ ] Invalid data skipped gracefully

### 10. Browser Compatibility

Test di berbagai browser:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

## Common Issues & Solutions

### Issue: Widget tidak muncul
**Solution:**
```bash
php artisan filament:cache-components
php artisan view:clear
```

### Issue: Chart tidak render
**Solution:**
1. Check browser console for JS errors
2. Pastikan Chart.js loaded
3. Check data format (harus numeric)

### Issue: Trend percentage salah
**Solution:**
1. Check timezone di config/app.php
2. Pastikan kolom tanggal di-cast ke 'date'
3. Verify query whereMonth/whereYear

### Issue: Quick action link broken
**Solution:**
1. Check resource namespace
2. Verify route exists: `php artisan route:list`
3. Update URL di QuickActionsWidget

### Issue: Performance lambat
**Solution:**
1. Add eager loading: `with('relation')`
2. Add database indexes
3. Cache heavy queries
4. Reduce data limit

## Automated Testing (Optional)

Create test file: `tests/Feature/DashboardTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class DashboardTest extends TestCase
{
    public function test_dashboard_loads_successfully()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/admin');
        
        $response->assertStatus(200);
    }
    
    public function test_stats_widget_displays()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/admin');
        
        $response->assertSee('Total Pendapatan');
        $response->assertSee('Total Beban');
        $response->assertSee('Laba Bersih');
    }
}
```

Run tests:
```bash
php artisan test --filter=DashboardTest
```

## Checklist Summary

- [ ] All widgets visible
- [ ] Data accurate
- [ ] Charts interactive
- [ ] Links working
- [ ] Responsive design
- [ ] Dark mode support
- [ ] No errors
- [ ] Performance good
- [ ] Browser compatible

## Sign-off

**Tested by:** _________________
**Date:** _________________
**Status:** ☐ PASS  ☐ FAIL
**Notes:** _________________

---

**Happy Testing! 🎉**
