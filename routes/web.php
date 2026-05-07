<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalesTransactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\JournalController;
use App\Http\Controllers\Reports\JournalExportController;
use App\Http\Controllers\Reports\LedgerController;
use App\Http\Controllers\Reports\LedgerExportController;
use App\Http\Controllers\Reports\JobOrderHppController;
use App\Http\Controllers\Reports\CashBookController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\Masterdata\AssetController;
use App\Http\Controllers\Reports\IncomeStatementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Reports\BalanceSheetController;
use App\Http\Controllers\OverheadPorController;
use App\Http\Controllers\BiayaOverheadController;
use App\Http\Controllers\PayrollPrintController;
use App\Http\Controllers\PenggajianController;
use App\Http\Controllers\PenggajianPrintController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\BahanPenolongController;
use App\Http\Controllers\AuxiliaryMaterialController;
use App\Http\Controllers\BillOfMaterialController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\StockPostingController;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Customer Authentication Routes
Route::middleware(['catalog'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [CustomerAuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.submit');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

// Public Catalog Routes (Customer Facing)
Route::middleware(['catalog'])->prefix('catalog')->name('catalog.')->group(function () {
    Route::get('/', [CatalogController::class, 'index'])->name('index');

    // New cart & checkout routes - letakkan sebelum route dinamis {product}
    Route::get('/cart', [CatalogController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [CatalogController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update', [CatalogController::class, 'updateCart'])->name('cart.update');
    // Checkout multi-produk dari keranjang
    Route::post('/checkout', [CatalogController::class, 'checkout'])->name('checkout');
    // Checkout cepat (single product) - form + proses
    Route::get('/checkout/form', [CatalogController::class, 'quickCheckoutForm'])->name('checkout.form');
    Route::post('/checkout/single', [CatalogController::class, 'checkoutSingle'])->name('checkout.single');
    Route::get('/payment/{jobOrder}', [CatalogController::class, 'payment'])->name('payment');
    Route::post('/payment/{jobOrder}', [CatalogController::class, 'processPayment'])->name('payment.process');

    // Riwayat & detail pesanan katalog
    Route::get('/orders', [CatalogController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{jobOrder}/receipt', [CatalogController::class, 'receipt'])->name('orders.receipt');
    Route::get('/orders/{jobOrder}/invoice', [CatalogController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{jobOrder}', [CatalogController::class, 'showOrder'])->name('orders.show');

    // Legacy single-product order routes (bisa disembunyikan nanti)
    Route::get('/order', [CatalogController::class, 'order'])->name('order');
    Route::get('/order/{product}', [CatalogController::class, 'orderProduct'])->name('order.product');
    Route::post('/order', [CatalogController::class, 'storeOrder'])->name('order.store');

    // Detail produk - diletakkan paling akhir supaya tidak menangkap URL lain
    Route::get('/{product}', [CatalogController::class, 'show'])->name('show');
});

// Gambar produk dari database (public agar katalog bisa menampilkan tanpa login)
Route::get('products/{product}/image', [ProductController::class, 'image'])->name('products.image');

// Legacy routes for backward compatibility
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.legacy.index');
Route::get('/katalog/{product}', [CatalogController::class, 'show'])->name('catalog.legacy.show');
Route::get('/pesan', [CatalogController::class, 'order'])->name('catalog.legacy.order');
Route::get('/pesan/{product}', [CatalogController::class, 'orderProduct'])->name('catalog.legacy.order.product');
Route::post('/pesan', [CatalogController::class, 'storeOrder'])->name('catalog.legacy.order.store');
// Legacy aliases for cart & checkout (optional, for /pesan path)
Route::get('/keranjang', [CatalogController::class, 'cart'])->name('catalog.legacy.cart');
Route::post('/keranjang/tambah', [CatalogController::class, 'addToCart'])->name('catalog.legacy.cart.add');
Route::post('/keranjang/update', [CatalogController::class, 'updateCart'])->name('catalog.legacy.cart.update');
Route::post('/checkout', [CatalogController::class, 'checkout'])->name('catalog.legacy.checkout');
Route::get('/payment/{jobOrder}', [CatalogController::class, 'payment'])->name('catalog.legacy.payment');
Route::post('/payment/{jobOrder}', [CatalogController::class, 'processPayment'])->name('catalog.legacy.payment.process');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/perusahaan', [ProfileController::class, 'updatePerusahaan'])->name('profile.update-perusahaan');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Products
    Route::resource('products', ProductController::class);
    Route::delete('products/{product}/image', [ProductController::class, 'deleteImage'])->name('products.deleteImage');
    Route::post('products/{product}/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generateBarcode');

    // Raw Materials
    Route::get('/raw-materials/generate-code', [RawMaterialController::class, 'generateCode'])
        ->name('raw-materials.generate-code');
    Route::resource('raw-materials', RawMaterialController::class);
    Route::get('/raw-materials/{rawMaterial}/stock-card', [RawMaterialController::class, 'stockCard'])
        ->name('raw-materials.stock-card');
    Route::get('/raw-materials/{rawMaterial}/stock-card/pdf', [RawMaterialController::class, 'stockCardPdf'])
        ->name('raw-materials.stock-card.pdf');
    Route::patch('/raw-materials/{rawMaterial}/unit', [RawMaterialController::class, 'updateUnit'])
        ->name('raw-materials.update-unit');

    // Auxiliary Materials (Bahan Penolong)
    Route::get('/auxiliary-materials/generate-code', [AuxiliaryMaterialController::class, 'generateCode'])
        ->name('auxiliary-materials.generate-code');
    Route::resource('auxiliary-materials', AuxiliaryMaterialController::class);
    Route::get('/auxiliary-materials/{auxiliaryMaterial}/stock-card', [AuxiliaryMaterialController::class, 'stockCard'])
        ->name('auxiliary-materials.stock-card');
    Route::patch('/auxiliary-materials/{auxiliaryMaterial}/unit', [AuxiliaryMaterialController::class, 'updateUnit'])
        ->name('auxiliary-materials.update-unit');
    Route::patch('/auxiliary-materials/{auxiliaryMaterial}/minimum-stock', [AuxiliaryMaterialController::class, 'updateMinimumStock'])
        ->name('auxiliary-materials.update-minimum-stock');

    // Stock Posting
    Route::get('/stock-posting', [StockPostingController::class, 'index'])->name('stock-posting.index');
    Route::post('/stock-posting', [StockPostingController::class, 'store'])->name('stock-posting.store');

    // Purchases (Pembelian Bahan Baku)
    Route::resource('purchases', PurchaseController::class);
    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
        ->name('purchases.receive');
    Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])
        ->name('purchases.approve');
    Route::get('/purchases/{purchase}/pay-debt', [PurchaseController::class, 'payDebt'])
        ->name('purchases.pay-debt');
    Route::post('/purchases/{purchase}/store-debt-payment', [PurchaseController::class, 'storeDebtPayment'])
        ->name('purchases.store-debt-payment');
    Route::get('/hutang', [PurchaseController::class, 'debtIndex'])
        ->name('hutang.index');

    // Purchase Returns (Retur Pembelian Bahan Baku)
    Route::resource('purchase-returns', PurchaseReturnController::class)->only([
        'index', 'create', 'store', 'show',
    ]);
    Route::post('purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve'])
        ->name('purchase-returns.approve');

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
    Route::post('suppliers/create-ajax', [SupplierController::class, 'createAjax'])->name('suppliers.create-ajax');

    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/data', function(\App\Models\Employee $employee) {
        return response()->json([
            'tarif_per_jam' => (float)($employee->tarif_per_jam ?? 0),
            'jam_kerja_per_bulan' => (float)($employee->jam_kerja_per_bulan ?? 0),
            'base_salary' => (float)($employee->base_salary ?? 0),
            'employee_type' => $employee->employee_type,
        ]);
    });

    // Customers
    Route::resource('customers', CustomerController::class);

    // Test route untuk auto-seed COA
    Route::get('/test-auto-coa', function() {
        \App\Models\ChartOfAccount::ensureBasicCoaExists();
        return redirect()->route('chart-of-accounts.index')->with('success', 'Auto COA seeding completed!');
    })->name('test-auto-coa');

    // Chart of Accounts
    Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart-of-accounts.index');
    Route::get('/chart-of-accounts/create', [ChartOfAccountController::class, 'create'])->name('chart-of-accounts.create');
    Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('chart-of-accounts.store');
    Route::get('/chart-of-accounts/{chartOfAccount}/edit', [ChartOfAccountController::class, 'edit'])->name('chart-of-accounts.edit');
    Route::put('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'update'])->name('chart-of-accounts.update');
    Route::delete('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'destroy'])->name('chart-of-accounts.destroy');
    Route::get('/chart-of-accounts/bulk-balance', [ChartOfAccountController::class, 'bulkBalance'])->name('chart-of-accounts.bulk-balance');
    Route::post('/chart-of-accounts/bulk-balance', [ChartOfAccountController::class, 'storeBulkBalance'])->name('chart-of-accounts.store-bulk-balance');
    Route::get('/chart-of-accounts/{chartOfAccount}/manage-balance', [ChartOfAccountController::class, 'manageBalance'])->name('chart-of-accounts.manage-balance');
    Route::post('/chart-of-accounts/{chartOfAccount}/manage-balance', [ChartOfAccountController::class, 'storeBalance'])->name('chart-of-accounts.store-balance');
    Route::get('/chart-of-accounts/{chartOfAccount}/balance/{balance}/edit', [ChartOfAccountController::class, 'editBalance'])->name('chart-of-accounts.edit-balance');
    Route::put('/chart-of-accounts/{chartOfAccount}/balance/{balance}', [ChartOfAccountController::class, 'updateBalance'])->name('chart-of-accounts.update-balance');

    // Penggajian (Non-BTKL)
    Route::resource('penggajian', PenggajianController::class);
    Route::get('penggajian/{penggajian}/slip', [PenggajianPrintController::class, 'show'])->name('penggajian.slip');
    Route::post('penggajian/{penggajian}/pay', [PenggajianController::class, 'pay'])->name('penggajian.pay');
    Route::post('penggajian/{penggajian}/recognize', [PenggajianController::class, 'recognize'])->name('penggajian.recognize');
    Route::post('penggajian/{penggajian}/distribute', [PenggajianController::class, 'distribute'])->name('penggajian.distribute');

    // Overhead POR (Transaksi)
    Route::resource('overhead-por', OverheadPorController::class)->except(['show']);

    // Assets (Master Data)
    Route::resource('aset', AssetController::class)->parameters([
        'aset' => 'asset'
    ]);
    Route::get('aset/{asset}/depreciation', [AssetController::class, 'calculateDepreciation'])
        ->name('aset.depreciation');

    // Beban Overhead (Master Data)
    Route::resource('biaya-overhead', BiayaOverheadController::class)->except(['show']);

    // Bill of Materials
    Route::resource('bill-of-materials', BillOfMaterialController::class);
    Route::patch('bill-of-materials/{billOfMaterial}/toggle-status', [BillOfMaterialController::class, 'toggleStatus'])
        ->name('bill-of-materials.toggle-status');
    Route::get('bill-of-materials/by-product/{productId}', [BillOfMaterialController::class, 'getBomByProduct'])
        ->name('bill-of-materials.by-product');

    // Units (Master Data)
    Route::resource('units', UnitController::class)->except(['show']);
    // Job Orders (Produksi)
    Route::resource('job-orders', JobOrderController::class)->only([
        'index', 'create', 'store', 'show', 'edit', 'update', 'destroy',
    ]);
    Route::post('job-orders/{job_order}/start', [JobOrderController::class, 'start'])
        ->name('job-orders.start');
    Route::post('job-orders/{job_order}/finish', [JobOrderController::class, 'finish'])
        ->name('job-orders.finish');
    Route::post('job-orders/{job_order}/regenerate-journal', [JobOrderController::class, 'regenerateJournal'])
        ->name('job-orders.regenerate-journal');
    Route::delete('job-orders/{job_order}/delete-sales', [JobOrderController::class, 'deleteAllSales'])
        ->name('job-orders.delete-sales');

    // Sales Transactions
    Route::resource('sales', SalesTransactionController::class);
    Route::get('sales/{sale}/receipt', [SalesTransactionController::class, 'receipt'])
        ->name('sales.receipt');
    Route::get('sales/create-from-job-order/{jobOrder}', [SalesTransactionController::class, 'createFromJobOrder'])
        ->name('sales.create.from-job-order');
    Route::post('sales/{sale}/payment-status', [SalesTransactionController::class, 'updatePaymentStatus'])
        ->name('sales.payment-status.update');
    
    // Payment Proof Routes
    Route::get('sales/{sale}/payment-proof', [SalesTransactionController::class, 'showPaymentProof'])
        ->name('sales.payment-proof');
    Route::post('sales/{sale}/upload-payment-proof', [SalesTransactionController::class, 'uploadPaymentProof'])
        ->name('sales.upload-payment-proof');
    Route::post('sales/{sale}/approve-payment', [SalesTransactionController::class, 'approvePayment'])
        ->name('sales.approve-payment');
    Route::post('sales/{sale}/reject-payment', [SalesTransactionController::class, 'rejectPayment'])
        ->name('sales.reject-payment');

    // Sales Reports
    Route::get('sales-reports', [SalesReportController::class, 'index'])->name('sales_reports.index');
    Route::get('sales-reports/report', [SalesReportController::class, 'report'])->name('sales_reports.report');
    Route::get('sales-reports/export-pdf', [SalesReportController::class, 'exportPdf'])->name('sales_reports.export_pdf');

    // Purchase Reports
    Route::get('purchase-reports', [PurchaseReportController::class, 'index'])->name('purchase_reports.index');
    Route::get('purchase-reports/report', [PurchaseReportController::class, 'report'])->name('purchase_reports.report');
    Route::get('purchase-reports/export-pdf', [PurchaseReportController::class, 'exportPdf'])->name('purchase_reports.export_pdf');

    // Sales Returns
    Route::get('sales-returns', [SalesReturnController::class, 'globalIndex'])->name('sales-returns.index');
    Route::get('sales/{sale}/returns', [SalesReturnController::class, 'index'])->name('sales.returns.index');
    Route::get('sales/{sale}/returns/create', [SalesReturnController::class, 'create'])->name('sales.returns.create');
    Route::post('sales/{sale}/returns', [SalesReturnController::class, 'store'])->name('sales.returns.store');

    Route::get('sales/returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales.returns.show');

    // Reports Prefix Group
    Route::prefix('reports')->name('reports.')->group(function () {
        // Halaman HTML
        Route::get('jurnal-umum', [JournalController::class, 'index'])->name('jurnal-umum');
        Route::get('buku-besar', [LedgerController::class, 'index'])->name('buku-besar');
        Route::post('buku-besar/post', [LedgerController::class, 'post'])->name('buku-besar.post');
        Route::post('buku-besar/post-manual', [LedgerController::class, 'postManual'])->name('buku-besar.post-manual');
        Route::get('buku-besar/check-posted', [LedgerController::class, 'checkPosted'])->name('buku-besar.check-posted');
        Route::get('jurnal-penyesuaian', [\App\Http\Controllers\Reports\DepreciationJournalController::class, 'index'])->name('jurnal-penyesuaian');
        Route::post('jurnal-penyesuaian/post', [\App\Http\Controllers\Reports\DepreciationJournalController::class, 'post'])->name('jurnal-penyesuaian.post');
        Route::get('jurnal-penyesuaian/pdf', [\App\Http\Controllers\Reports\DepreciationJournalController::class, 'pdf'])->name('jurnal-penyesuaian.pdf');
        Route::get('calk', [\App\Http\Controllers\Reports\ReportController::class, 'calk'])->name('calk');
        Route::get('calk/generate', [\App\Http\Controllers\Reports\ReportController::class, 'generateCalk'])->name('calk.generate');
        
        // Ekspor PDF
        Route::get('jurnal-umum/pdf', [JournalExportController::class, 'jurnalUmumPdf'])->name('jurnal-umum.pdf');
        Route::get('buku-besar/pdf', [LedgerExportController::class, 'ledgerPdf'])->name('buku-besar.pdf');
        
        // Cash Book Reports
        Route::get('cash-book', [CashBookController::class, 'index'])->name('cash-book.index');
        Route::get('cash-book/data', [CashBookController::class, 'getData'])->name('cash-book.data');
        Route::post('cash-book/store', [CashBookController::class, 'store'])->name('cash-book.store');
        Route::get('cash-book/export-pdf', [CashBookController::class, 'exportPdf'])->name('cash-book.export-pdf');
        
        // Neraca Saldo
        Route::get('neraca-saldo', [\App\Http\Controllers\Reports\NeracaSaldoController::class, 'index'])->name('neraca-saldo');
        Route::get('neraca-saldo/print', [\App\Http\Controllers\Reports\NeracaSaldoController::class, 'print'])->name('neraca-saldo.print');

        // Laporan Lainnya
        Route::get('neraca', [BalanceSheetController::class, 'index'])->name('neraca');
        Route::get('neraca/pdf', [BalanceSheetController::class, 'pdf'])->name('neraca.pdf');
        Route::get('laba-rugi', [IncomeStatementController::class, 'index'])->name('laba-rugi');
        Route::get('laba-rugi/pdf', [IncomeStatementController::class, 'pdf'])->name('laba-rugi.pdf');
        Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet');
        Route::get('income-statement', [IncomeStatementController::class, 'index'])->name('income-statement');
        
        // Laporan HPP Job Order
        Route::get('job-order-hpp', [JobOrderHppController::class, 'index'])->name('job-order-hpp');
        Route::get('job-order-hpp/pdf', [JobOrderHppController::class, 'pdf'])->name('job-order-hpp.pdf');
    });

    // Cetak BTKL (Payroll)
    Route::get('/admin/payrolls/print', [PayrollPrintController::class, 'index'])
        ->name('payroll.print.index');
    Route::get('/admin/payrolls/{payroll}/print', [PayrollPrintController::class, 'show'])
        ->name('payroll.print');

    // Cetak Penggajian (Non-BTKL)
    Route::get('/admin/penggajian/print', [PenggajianPrintController::class, 'index'])
        ->name('penggajian.print.index');
    Route::get('/admin/penggajian/{penggajian}/print', [PenggajianPrintController::class, 'show'])
        ->name('penggajian.print');
});

require __DIR__.'/auth.php';