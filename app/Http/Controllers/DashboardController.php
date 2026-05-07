<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use App\Models\Transaction;
use App\Models\SalesTransaction;
use App\Models\Employee;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $filterType = $request->get('filter_type', 'month'); // 'month' or 'year'
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $showAll = $request->get('show_all', false); // New parameter for showing all data

        // Get today's date
        $today = Carbon::today();

        $stats = [
            'total_products' => Product::count(),
            'total_raw_materials' => RawMaterial::count(),
            'total_employees' => Employee::count(),
            
            // PERBAIKAN: Transaksi Pembelian Hari Ini
            'today_purchase_transactions' => Purchase::whereDate('purchase_date', $today)->count(),
            
            // PERBAIKAN: Transaksi Penjualan Hari Ini
            'today_sales_transactions' => SalesTransaction::whereDate('transaction_date', $today)->count(),
            
            // PERBAIKAN: Total Transaksi Sebulan (bulan berjalan)
            'monthly_transactions' => Purchase::whereBetween('purchase_date', [
                    now()->startOfMonth(), 
                    now()->endOfMonth()
                ])->count() 
                + SalesTransaction::whereBetween('transaction_date', [
                    now()->startOfMonth(), 
                    now()->endOfMonth()
                ])->count(),
            
            // PERBAIKAN: Pembelian Hari Ini (total rupiah)
            'today_purchases' => Purchase::whereDate('purchase_date', $today)
                ->sum('total_amount') ?? 0,
            
            // PERBAIKAN: Total Pembelian (keseluruhan)
            'total_purchases' => Purchase::sum('total_amount') ?? 0,
            
            // PERBAIKAN: Penjualan Hari Ini (total rupiah)
            'today_sales' => SalesTransaction::whereDate('transaction_date', $today)
                ->sum('total_amount') ?? 0,
            
            // PERBAIKAN: Total Penjualan (keseluruhan)
            'total_sales' => SalesTransaction::sum('total_amount') ?? 0,
            
            // PERBAIKAN: Total Hutang Hari Ini
            // Hanya pembelian kredit yang dibuat hari ini
            'total_debt_today' => Purchase::where('payment_method', 'credit')
                ->whereDate('purchase_date', $today)
                ->sum('total_amount') ?? 0,
            
            // PERBAIKAN: Total Hutang Akumulatif
            // Total hutang yang belum lunas (total_amount - paid_amount)
            'total_debt_accumulated' => Purchase::where('payment_method', 'credit')
                ->where(function($query) {
                    $query->where('payment_status', '!=', 'paid')
                          ->orWhereNull('payment_status');
                })
                ->get()
                ->sum(function($purchase) {
                    return $purchase->total_amount - ($purchase->paid_amount ?? 0);
                }),
            
            // Raw material stock calculations
            'low_stock_materials' => RawMaterial::when(Schema::hasColumn('raw_materials', 'min_stock'), function($query) {
                $query->whereRaw('stock < (CASE WHEN min_stock > 0 THEN min_stock ELSE 0 END)');
            }, function($query) {
                // Fallback jika min_stock tidak ada
                $query->where('stock', '<', 10); // Default threshold
            })
                ->with('supplier')
                ->orderBy('stock', 'asc')
                ->limit(10)
                ->get()
                ->filter(function($material) {
                    // Filter materials that are actually low stock
                    $minStock = $material->min_stock ?? 10; // Default jika null
                    return $material->stock < $minStock;
                })
                ->values(),
            
            // Auxiliary material stock calculations (bahan penolong)
            // Hanya tampilkan yang stok >= 0 dan < minimum_stock (abaikan stok negatif dari data lama)
            'low_stock_auxiliary_materials' => AuxiliaryMaterial::where('minimum_stock', '>', 0)
                ->whereColumn('stock', '<', 'minimum_stock')
                ->where('stock', '>=', 0) // Abaikan stok negatif
                ->with('supplier')
                ->orderBy('stock', 'asc')
                ->limit(10)
                ->get(),
        ];

        // Ambil 10 purchase order terbaru
        $recent_transactions = Purchase::latest('purchase_date')
            ->latest('id')
            ->take(10)
            ->get();

        // Ambil 10 penjualan terbaru
        $recent_sales = SalesTransaction::latest('transaction_date')
            ->latest('id')
            ->take(10)
            ->get();

        // Data grafik gabungan berdasarkan filter
        if ($filterType === 'year') {
            // Filter by year - show monthly data for selected year
            $purchaseChart = Purchase::selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as month, SUM(total_amount) as total')
                ->whereYear('purchase_date', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $salesChart = SalesTransaction::selectRaw('DATE_FORMAT(transaction_date, "%Y-%m") as month, SUM(total_amount) as total')
                ->whereYear('transaction_date', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Generate labels for all months in the selected year
            $periods = collect();
            for ($i = 1; $i <= 12; $i++) {
                $periods->push(sprintf('%04d-%02d', $year, $i));
            }
        } else {
            // Filter by month - show daily data for selected month and year
            $purchaseChart = Purchase::selectRaw('DATE(purchase_date) as day, SUM(total_amount) as total')
                ->whereYear('purchase_date', $year)
                ->whereMonth('purchase_date', $month)
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $salesChart = SalesTransaction::selectRaw('DATE(transaction_date) as day, SUM(total_amount) as total')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            // Generate labels for all days in the selected month
            $periods = collect();
            $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;
            
            if ($showAll) {
                // Show all days in the month, but limit to reasonable amount
                $maxDays = min($daysInMonth, 31); // Hard limit at 31 days
                for ($i = 1; $i <= $maxDays; $i++) {
                    $periods->push(sprintf('%04d-%02d-%02d', $year, $month, $i));
                }
            } else {
                // Show current date + 14 days before it (15 days total including today)
                $currentDay = now()->day;
                $startDate = max(1, $currentDay - 14); // Go back 14 days from today
                $endDate = min($daysInMonth, $currentDay); // Up to today
                for ($i = $startDate; $i <= $endDate; $i++) {
                    $periods->push(sprintf('%04d-%02d-%02d', $year, $month, $i));
                }
            }
        }

        $purchaseData = [];
        $salesData = [];
        $labels = [];

        foreach ($periods as $p) {
            if ($filterType === 'year') {
                $labels[] = Carbon::createFromFormat('Y-m', $p)->translatedFormat('M Y');
                $purchaseData[] = (float) ($purchaseChart->firstWhere('month', $p)->total ?? 0);
                $salesData[] = (float) ($salesChart->firstWhere('month', $p)->total ?? 0);
            } else {
                $labels[] = Carbon::createFromFormat('Y-m-d', $p)->format('d M');
                $purchaseData[] = (float) ($purchaseChart->firstWhere('day', $p)->total ?? 0);
                $salesData[] = (float) ($salesChart->firstWhere('day', $p)->total ?? 0);
            }
        }

        $charts = [
            'labels' => $labels,
            'purchases' => $purchaseData,
            'sales' => $salesData,
        ];

        // Debug logging
        Log::info('Dashboard statistics:', [
            'today_purchase_transactions' => $stats['today_purchase_transactions'],
            'today_sales_transactions' => $stats['today_sales_transactions'],
            'monthly_transactions' => $stats['monthly_transactions'],
            'today_purchases' => $stats['today_purchases'],
            'total_purchases' => $stats['total_purchases'],
            'today_sales' => $stats['today_sales'],
            'total_sales' => $stats['total_sales'],
            'total_debt_today' => $stats['total_debt_today'],
            'total_debt_accumulated' => $stats['total_debt_accumulated'],
        ]);

        // Generate filter options
        $years = range(2020, now()->year);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Check if POR for current month exists
        $currentPeriod = now()->format('Y-m');
        $porBulanIni = \App\Models\OverheadPor::where('dasar_alokasi', 'jam_tk_langsung')
            ->where('periode', $currentPeriod)
            ->first();
        $porMissing = is_null($porBulanIni);

        return view('dashboard', compact('stats', 'recent_transactions', 'recent_sales', 'charts', 'filterType', 'year', 'month', 'years', 'months', 'showAll', 'porMissing', 'currentPeriod'));
    }
}