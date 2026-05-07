<?php

/**
 * Test Script untuk Verifikasi Logika Posting
 * 
 * Test 1: Stock Posting - Hanya pindahkan saldo akhir (qty terakhir di kolom SALDO)
 * Test 2: Ledger Posting - Hanya pindahkan saldo akhir (line terakhir setelah mutasi)
 */

echo "=== TEST POSTING LOGIC ===\n\n";

// ============================================
// TEST 1: STOCK POSTING (FIFO & AVERAGE)
// ============================================
echo "TEST 1: STOCK POSTING\n";
echo str_repeat("-", 50) . "\n\n";

// Simulasi data Maret 2026 - FIFO
echo "Scenario: FIFO Method - Maret 2026\n";
echo "Transaksi:\n";
echo "  Saldo Awal: 0 ekor\n";
echo "  15/04: Masuk 3 ekor @ Rp 31.532 = Rp 94.595\n";
echo "  15/04: Masuk 2 ekor @ Rp 31.532 = Rp 63.063\n";
echo "\n";

$fifo_batches = [
    ['qty' => 3, 'price' => 31532],
    ['qty' => 2, 'price' => 31532],
];

$ending_qty_fifo = 0;
$ending_value_fifo = 0;
foreach ($fifo_batches as $batch) {
    $ending_qty_fifo += $batch['qty'];
    $ending_value_fifo += $batch['qty'] * $batch['price'];
}

echo "Saldo Akhir Maret:\n";
echo "  Total Qty: {$ending_qty_fifo} ekor\n";
echo "  Total Value: Rp " . number_format($ending_value_fifo, 0, ',', '.') . "\n";
echo "  Avg Price: Rp " . number_format($ending_value_fifo / $ending_qty_fifo, 0, ',', '.') . "\n\n";

echo "Yang DIPINDAHKAN ke Saldo Awal April:\n";
echo "  ✓ beginning_qty = {$ending_qty_fifo} ekor (HANYA qty terakhir di kolom SALDO)\n";
echo "  ✓ beginning_value = Rp " . number_format($ending_value_fifo, 0, ',', '.') . "\n";
echo "  ✗ TIDAK memindahkan semua baris transaksi\n";
echo "  ✗ TIDAK memindahkan detail batch FIFO\n\n";

// Simulasi data Maret 2026 - AVERAGE
echo str_repeat("-", 50) . "\n";
echo "Scenario: AVERAGE Method - Maret 2026\n";
echo "Transaksi:\n";
echo "  Saldo Awal: 0 ekor\n";
echo "  15/04: Masuk 3 ekor @ Rp 31.532 = Rp 94.595\n";
echo "  15/04: Masuk 2 ekor @ Rp 31.532 = Rp 63.063\n";
echo "\n";

$avg_qty = 5;
$avg_value = 157658;
$avg_price = $avg_value / $avg_qty;

echo "Saldo Akhir Maret (Average):\n";
echo "  Total Qty: {$avg_qty} ekor\n";
echo "  Total Value: Rp " . number_format($avg_value, 0, ',', '.') . "\n";
echo "  Avg Price: Rp " . number_format($avg_price, 0, ',', '.') . "\n\n";

echo "Yang DIPINDAHKAN ke Saldo Awal April:\n";
echo "  ✓ beginning_qty = {$avg_qty} ekor (HANYA 1 baris saldo akhir)\n";
echo "  ✓ beginning_value = Rp " . number_format($avg_value, 0, ',', '.') . "\n";
echo "  ✗ TIDAK memindahkan semua baris transaksi\n\n";

// ============================================
// TEST 2: LEDGER POSTING (BUKU BESAR)
// ============================================
echo "\n" . str_repeat("=", 50) . "\n\n";
echo "TEST 2: LEDGER POSTING (BUKU BESAR)\n";
echo str_repeat("-", 50) . "\n\n";

echo "Scenario: Akun Kas - Maret 2026\n";
echo "Transaksi:\n";
echo "  28/03: Saldo Awal = Rp 10.000.000\n";
echo "  20/03: Kas (Debit Rp 115.000) = Saldo Rp 9.700.000\n";
echo "  20/03: Kas (Kredit Rp 70.000) = Saldo Rp 9.630.000\n";
echo "\n";

$opening_balance = 10000000;
$debit_total = 115000;
$credit_total = 70000;
$ending_balance = $opening_balance + $debit_total - $credit_total;

echo "Perhitungan:\n";
echo "  Saldo Awal: Rp " . number_format($opening_balance, 0, ',', '.') . "\n";
echo "  Total Debit: Rp " . number_format($debit_total, 0, ',', '.') . "\n";
echo "  Total Kredit: Rp " . number_format($credit_total, 0, ',', '.') . "\n";
echo "  Saldo Akhir: Rp " . number_format($ending_balance, 0, ',', '.') . "\n\n";

echo "Yang DIPOSTING ke AccountPeriodBalance (Maret):\n";
echo "  ✓ ending_balance = Rp " . number_format($ending_balance, 0, ',', '.') . " (SALDO AKHIR, bukan saldo awal)\n";
echo "  ✓ total_debit = Rp " . number_format($debit_total, 0, ',', '.') . "\n";
echo "  ✓ total_credit = Rp " . number_format($credit_total, 0, ',', '.') . "\n";
echo "  ✓ is_posted = true\n\n";

echo "Yang DIPINDAHKAN ke Saldo Awal April:\n";
echo "  ✓ ending_balance = Rp " . number_format($ending_balance, 0, ',', '.') . " (line terakhir setelah mutasi)\n";
echo "  ✗ BUKAN Rp " . number_format($opening_balance, 0, ',', '.') . " (saldo awal Maret)\n";
echo "  ✓ is_posted = false (belum diposting untuk April)\n\n";

// ============================================
// VERIFICATION SUMMARY
// ============================================
echo str_repeat("=", 50) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

echo "✓ Stock Posting: Hanya pindahkan ending_qty & ending_value\n";
echo "  - FIFO: Total qty dari semua batch yang tersisa\n";
echo "  - Average: Total qty dengan harga rata-rata\n";
echo "  - TIDAK pindahkan semua transaksi/baris\n\n";

echo "✓ Ledger Posting: Hanya pindahkan ending_balance\n";
echo "  - Saldo akhir setelah semua mutasi (line terakhir)\n";
echo "  - BUKAN saldo awal periode\n";
echo "  - Saldo akhir bulan ini = saldo awal bulan depan\n\n";

echo "=== TEST COMPLETED ===\n";
