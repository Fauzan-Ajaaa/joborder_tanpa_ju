<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function calk()
    {
        return view('reports.calk');
    }

    public function generateCalk(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));
        $periode = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Laporan Penjualan
        $sales = DB::table('sales_transactions')
            ->whereMonth('transaction_date', $bulan)
            ->whereYear('transaction_date', $tahun)
            ->selectRaw('
                COUNT(*) as total_transaksi,
                SUM(subtotal + (subtotal * ppn_rate / 100) + fob_cost) as total_penjualan,
                SUM(CASE WHEN payment_status = "paid" THEN (subtotal + (subtotal * ppn_rate / 100) + fob_cost) ELSE 0 END) as penjualan_tunai,
                SUM(CASE WHEN payment_status = "unpaid" THEN (subtotal + (subtotal * ppn_rate / 100) + fob_cost) ELSE 0 END) as penjualan_kredit
            ')
            ->first();

        // Detail produk
        $detailProduk = DB::table('sales_items as si')
            ->join('sales_transactions as st', 'si.sales_transaction_id', '=', 'st.id')
            ->join('products as p', 'si.product_id', '=', 'p.id')
            ->whereMonth('st.transaction_date', $bulan)
            ->whereYear('st.transaction_date', $tahun)
            ->selectRaw('
                p.name as nama_produk,
                SUM(si.quantity) as total_quantity,
                SUM(si.subtotal) as total_penjualan,
                AVG(si.unit_price) as harga_rata_rata
            ')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total_penjualan')
            ->limit(5)
            ->get();

        // Piutang
        $piutang = DB::table('customers as c')
            ->leftJoin('sales_transactions as st', function($join) use ($bulan, $tahun) {
                $join->on('c.id', '=', 'st.customer_id')
                    ->whereMonth('st.transaction_date', $bulan)
                    ->whereYear('st.transaction_date', $tahun)
                    ->where('st.payment_status', 'unpaid');
            })
            ->selectRaw('
                c.name as nama_pelanggan,
                c.total_outstanding as total_piutang,
                c.credit_limit as limit_kredit,
                c.credit_status as status_kredit,
                COUNT(st.id) as jumlah_transaksi_bulan_ini,
                COALESCE(SUM(st.grand_total), 0) as piutang_bulan_ini
            ')
            ->where('c.total_outstanding', '>', 0)
            ->groupBy('c.id', 'c.name', 'c.total_outstanding', 'c.credit_limit', 'c.credit_status')
            ->orderByDesc('c.total_outstanding')
            ->get();

        // Persediaan
        $persediaanProduk = DB::table('products')
            ->selectRaw('
                name as nama_produk,
                stock as jumlah_stok,
                price as harga_satuan,
                stock * price as nilai_persediaan
            ')
            ->where('stock', '>', 0)
            ->orderByDesc('nilai_persediaan')
            ->get();

        $persediaanBahan = DB::table('raw_materials')
            ->selectRaw('
                name as nama_bahan,
                stock as jumlah_stok,
                price_per_unit as harga_satuan,
                stock * price_per_unit as nilai_persediaan
            ')
            ->where('stock', '>', 0)
            ->orderByDesc('nilai_persediaan')
            ->get();

        // Arus Kas
        $penerimaan = DB::table('sales_transactions')
            ->whereMonth('transaction_date', $bulan)
            ->whereYear('transaction_date', $tahun)
            ->where('payment_status', 'paid')
            ->sum(DB::raw('subtotal + (subtotal * ppn_rate / 100) + fob_cost'));

        // Rasio
        $totalPenjualan = $sales->total_penjualan ?? 0;
        $totalPiutang = DB::table('customers')->sum('total_outstanding');
        $totalPersediaan = DB::table('products')
            ->where('stock', '>', 0)
            ->sum(DB::raw('stock * price'));

        $totalPelanggan = DB::table('customers')->count();
        $pelangganAktif = DB::table('customers')->where('total_orders', '>', 0)->count();

        $rasioPiutangTerhadapPenjualan = $totalPenjualan > 0 ? ($totalPiutang / $totalPenjualan) * 100 : 0;
        $rasioPelangganAktif = ($pelangganAktif / $totalPelanggan) * 100;

        return view('reports.calk-results', compact(
            'bulan', 'tahun', 'periode',
            'sales', 'detailProduk', 'piutang',
            'persediaanProduk', 'persediaanBahan',
            'penerimaan', 'totalPenjualan', 'totalPiutang',
            'totalPersediaan', 'totalPelanggan', 'pelangganAktif',
            'rasioPiutangTerhadapPenjualan', 'rasioPelangganAktif'
        ));
    }
}
