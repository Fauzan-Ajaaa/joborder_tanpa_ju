<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\SalesTransaction;
use App\Models\RawMaterialUsage;
use App\Models\Transaction;
use App\Models\Payroll;
use App\Models\BiayaOverhead;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\Penggajian;
use App\Models\JobOrder;
use App\Models\JobOrderMaterial;
use App\Models\JobOrderLabor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalService
{
    /**
     * Generate jurnal dari transaksi penjualan
     */
    
    /**
     * Generate jurnal dari penjualan dengan HPP terpisah
     */
    public function createJournalFromSales(SalesTransaction $sales): JournalEntry
    {
        return DB::transaction(function () use ($sales) {
            $subtotal = $sales->subtotal ?? 0;
            $discountAmount = $sales->discount_amount ?? 0;
            $fobCost = $sales->fob_cost ?? 0;
            $ppnAmount = $sales->ppn_amount ?? 0;

            // Kas yang diterima = subtotal - diskon + PPN (tidak termasuk FOB karena FOB jurnal terpisah)
            $totalKas = $subtotal - $discountAmount + $ppnAmount;

            // === JURNAL PENJUALAN: Dr Kas + Dr Diskon / Cr Penjualan + Cr PPN ===
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $sales->transaction_date,
                'description' => "Penjualan " . ($sales->customer ? $sales->customer->name : $sales->customer_name),
                'source_type' => 'sales',
                'source_id' => $sales->id,
                'status' => 'posted',
                'total_debit' => $totalKas + $discountAmount,
                'total_credit' => $subtotal + $ppnAmount,
            ]);

            // DEBIT: Kas (yang benar-benar diterima)
            $this->addJournalItem($journal, $totalKas, 0, '1102', 'Kas');

            // DEBIT: Diskon Penjualan (contra revenue - mengurangi pendapatan)
            if ($discountAmount > 0) {
                $this->addJournalItem($journal, $discountAmount, 0, '4202', 'Diskon Penjualan');
            }

            // CREDIT: Penjualan per produk (nilai penuh sebelum diskon)
            $sales->loadMissing(['salesItems.product']);
            $salesItems = $sales->salesItems;

            if ($salesItems->isNotEmpty()) {
                foreach ($salesItems as $item) {
                    $product = $item->product;
                    $itemSubtotal = (float) ($item->subtotal ?? ($item->quantity * $item->unit_price));
                    if ($itemSubtotal <= 0) continue;

                    if ($product) {
                        // Auto-generate COA penjualan jika belum ada
                        $salesCoa = \App\Models\ChartOfAccount::where('account_name', 'Penjualan - ' . $product->name)
                            ->where('code', 'like', '41%')->first();

                        if (!$salesCoa) {
                            $coaId = \App\Services\CoaAutoGenerateService::createCoaForProductSales($product);
                            if ($coaId) {
                                $salesCoa = \App\Models\ChartOfAccount::find($coaId);
                            }
                        }

                        // Auto-generate COA retur penjualan jika belum ada (untuk persiapan retur di masa depan)
                        $returnCoa = \App\Models\ChartOfAccount::where('account_name', 'Retur Penjualan - ' . $product->name)
                            ->where('code', 'like', '4201%')->first();

                        if (!$returnCoa) {
                            \App\Services\CoaAutoGenerateService::createCoaForProductSalesReturn($product);
                        }

                        // Gunakan COA penjualan untuk jurnal (nilai penuh sebelum diskon)
                        if ($salesCoa) {
                            $this->addJournalItem($journal, 0, $itemSubtotal, $salesCoa->code, $salesCoa->account_name);
                        } else {
                            $this->addJournalItem($journal, 0, $itemSubtotal, '41', 'Penjualan - ' . $product->name);
                        }
                    } else {
                        $this->addJournalItem($journal, 0, $itemSubtotal, '41', 'Penjualan');
                    }
                }
            } else {
                $this->addJournalItem($journal, 0, $subtotal, '41', 'Penjualan');
            }

            // CREDIT: PPN Keluaran
            if ($ppnAmount > 0) {
                $this->addJournalItem($journal, 0, $ppnAmount, '22', 'PPN Keluaran');
            }

            // === JURNAL TRANSPORT (hanya untuk opsi "Diantar") ===
            if ($fobCost > 0) {
                $fobType = $sales->fob_type ?? 'shipping_point';
                
                if ($fobType === 'destination') {
                    // DIANTAR: Customer bayar transport, jadi Kas bertambah
                    // Dr. Kas / Cr. Beban Transport Penjualan (sebagai pendapatan)
                    $transportJournal = JournalEntry::create([
                        'journal_number' => JournalEntry::generateJournalNumber('JU'),
                        'transaction_date' => $sales->transaction_date,
                        'description' => "Pendapatan Transport Pengiriman " . ($sales->customer ? $sales->customer->name : $sales->customer_name),
                        'source_type' => 'sales_transport',
                        'source_id' => $sales->id,
                        'status' => 'posted',
                        'total_debit' => $fobCost,
                        'total_credit' => $fobCost,
                    ]);
                    $this->addJournalItem($transportJournal, $fobCost, 0, '1102', 'Kas');
                    $this->addJournalItem($transportJournal, 0, $fobCost, '4301', 'Beban Transport Penjualan');
                }
                // TAKE AWAY/DINE IN: Tidak ada jurnal transport karena tidak ada biaya transport
            }

            return $journal;
        });
    }

    /**
     * Generate jurnal HPP terpisah untuk penjualan
     */
    public function createHppJournal(SalesTransaction $sales): ?JournalEntry
    {
        $hppTotal = 0;
        $jobOrder = null;

        if ($sales->job_order_id) {
            $jobOrder = \App\Models\JobOrder::where('kode_job', $sales->job_order_id)
                ->orWhere('id', $sales->job_order_id)
                ->with('product.inventoryCoa')
                ->first();

            if ($jobOrder) {
                // Hitung HPP sama persis dengan show page: BBB + BTKL + BOP
                // BBB: dari job_order_materials
                $bbb = DB::table('job_order_materials')
                    ->where('job_order_id', $jobOrder->id)
                    ->selectRaw('SUM(qty_total * harga_per_unit) as total')
                    ->value('total') ?? 0;

                // Durasi = durasi per unit × total qty (SAMA dengan show page)
                $productId = $jobOrder->jobOrderDetails()->first()?->product_id ?? $jobOrder->product_id;
                $totalQty = $jobOrder->jobOrderDetails()->sum('quantity') ?: ($jobOrder->quantity ?? 1);
                $bom = DB::table('bill_of_materials')->where('product_id', $productId)->first();
                $bomProcess = DB::table('bill_of_material_processes')
                    ->where('bill_of_material_id', $bom?->id)
                    ->first();
                $durasiJam = $bomProcess ? ((float)$bomProcess->duration_minutes * $totalQty) / 60 : 0;

                // BTKL: tarif × durasi total
                $btklRate = $bom ? (float)$bom->btkl_rate_per_hour : 0;
                $btkl = $durasiJam * $btklRate;

                // BOP: por_per_jam × durasi total (TANPA bahan penolong)
                $por = DB::table('overhead_por')
                    ->where('periode', now()->format('Y-m'))
                    ->orderByDesc('id')->first();
                $porPerJam = (float)($por?->por_per_jam ?? 0);
                $bopFromPor = $porPerJam * $durasiJam;

                $hppTotal = round((float)$bbb + $btkl + $bopFromPor, 2);
            }
        }

        if ($hppTotal <= 0) {
            return null;
        }

        return DB::transaction(function () use ($sales, $hppTotal, $jobOrder) {
            $hppJournal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $sales->transaction_date,
                'description' => "HPP Penjualan " . ($sales->customer ? $sales->customer->name : $sales->customer_name),
                'source_type' => 'hpp',
                'source_id' => $sales->id,
                'status' => 'posted',
                'total_debit' => $hppTotal,
                'total_credit' => $hppTotal,
            ]);

            // Debit Harga Pokok Penjualan
            $this->addJournalItem($hppJournal, $hppTotal, 0, '59', 'Harga Pokok Penjualan');

            // Credit Persediaan Barang Jadi - pakai COA spesifik produk (nyambung dari jurnal produksi)
            $product = $jobOrder?->product;
            if ($product && $product->inventoryCoa) {
                $this->addJournalItem($hppJournal, 0, $hppTotal, $product->inventoryCoa->code, $product->inventoryCoa->account_name);
            } else {
                // Fallback cari dari DB berdasarkan nama produk
                $invCoa = $product
                    ? \App\Models\ChartOfAccount::where('account_name', 'Pers. Barang Jadi - ' . $product->name)->first()
                    : null;
                $this->addJournalItem($hppJournal, 0, $hppTotal, $invCoa ? $invCoa->code : '1105', $invCoa ? $invCoa->account_name : 'Pers. Barang Jadi');
            }
            
            // Log untuk debugging
            \Log::info('Journal HPP dibuat', [
                'hpp_journal_id' => $hppJournal->id,
                'hpp_journal_number' => $hppJournal->journal_number,
                'hpp_total' => $hppTotal,
                'sales_transaction_id' => $sales->id,
            ]);
            
            return $hppJournal;
        });
    }

    /**
     * Generate jurnal dari retur penjualan
     */
    public function createJournalFromSalesReturn(SalesReturn $salesReturn): JournalEntry
    {
        return DB::transaction(function () use ($salesReturn) {
            $salesReturn->loadMissing(['salesTransaction']);

            $sale = $salesReturn->salesTransaction;
            $customerName = data_get($sale, 'customer.name')
                ?? data_get($sale, 'customer_name')
                ?? '-';

            $subtotal = (float)($salesReturn->subtotal ?? 0);
            $ppnAmount = (float)($salesReturn->ppn_amount ?? 0);
            $grandTotal = (float)($salesReturn->grand_total ?? ($subtotal + $ppnAmount));

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $salesReturn->return_date,
                'description' => "Retur Penjualan {$customerName}",
                'source_type' => 'sales_return',
                'source_id' => $salesReturn->id,
                'status' => 'posted',
                'total_debit' => $grandTotal,
                'total_credit' => $grandTotal,
            ]);

            // DEBIT SIDE
            // Debit Retur Penjualan per produk (menggunakan akun retur yang auto-generated)
            $salesReturn->loadMissing(['items.product']);
            $totalDebited = 0;
            foreach ($salesReturn->items as $item) {
                $itemSubtotal = (float)$item->subtotal;
                if ($itemSubtotal <= 0) continue;

                $product = $item->product;
                if ($product) {
                    // Cari atau buat COA retur penjualan spesifik produk
                    $returnCoaName = 'Retur Penjualan - ' . $product->name;
                    $returnCoa = \App\Models\ChartOfAccount::where('account_name', $returnCoaName)
                        ->where('code', 'like', '4201%')->first();

                    // Jika belum ada, auto-generate menggunakan CoaAutoGenerateService
                    if (!$returnCoa) {
                        $coaId = \App\Services\CoaAutoGenerateService::createCoaForProductSalesReturn($product);
                        if ($coaId) {
                            $returnCoa = \App\Models\ChartOfAccount::find($coaId);
                        }
                    }

                    if ($returnCoa) {
                        $this->addJournalItem($journal, $itemSubtotal, 0, $returnCoa->code, $returnCoa->account_name);
                    } else {
                        // Fallback ke akun retur penjualan umum
                        $this->addJournalItem($journal, $itemSubtotal, 0, '4201', 'Retur Penjualan - ' . $product->name);
                    }
                } else {
                    // Jika tidak ada produk, gunakan akun retur penjualan umum
                    $this->addJournalItem($journal, $itemSubtotal, 0, '4201', 'Retur Penjualan');
                }
                $totalDebited += $itemSubtotal;
            }

            // Fallback jika tidak ada items
            if ($totalDebited <= 0 && $subtotal > 0) {
                $this->addJournalItem($journal, $subtotal, 0, '4201', 'Retur Penjualan');
            }

            // Debit PPN Keluaran (membalik PPN)
            if ($ppnAmount > 0) {
                $this->addJournalItem($journal, $ppnAmount, 0, '22', 'PPN Keluaran');
            }

            // CREDIT SIDE
            // Credit Kas (pengembalian dana)
            $this->addJournalItem($journal, 0, $grandTotal, '1102', 'Kas');

            return $journal;
        });
    }

    /**
     * Generate jurnal dari penggajian (saat dibuat)
     * Debit Beban Gaji, Credit Utang Gaji (belum dibayar)
     */
    public function createJournalFromPenggajian(Penggajian $penggajian): JournalEntry
    {
        return DB::transaction(function () use ($penggajian) {
            Log::info("JournalService: createJournalFromPenggajian called for ID: " . $penggajian->id_gaji);
            
            $penggajian->loadMissing(['employee']);

            $employeeName = $penggajian->employee->name ?? 'Unknown';
            $totalGaji = (float)($penggajian->total_gaji_bersih ?? 0);

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $penggajian->tanggal_penggajian,
                'description' => "Penggajian {$employeeName} - {$penggajian->no_transaksi_gaji}",
                'source_type' => 'penggajian',
                'source_id' => $penggajian->id_gaji,
                'status' => 'posted',
                'total_debit' => $totalGaji,
                'total_credit' => $totalGaji,
            ]);

            // Debit: Beban Gaji dan Upah
            $this->addJournalItem($journal, $totalGaji, 0, '57', 'Beban Gaji dan Upah');

            // Credit: Hutang Gaji (belum dibayar)
            $this->addJournalItem($journal, 0, $totalGaji, '2102', 'Hutang Gaji');

            Log::info("JournalService: Journal created successfully for penggajian ID: " . $penggajian->id_gaji);

            return $journal;
        });
    }

    /**
     * Generate jurnal dari pembayaran penggajian (saat dibayar)
     * Debit Utang Gaji, Credit Kas
     */
    public function createJournalFromPenggajianPayment(Penggajian $penggajian): JournalEntry
    {
        return DB::transaction(function () use ($penggajian) {
            Log::info("JournalService: createJournalFromPenggajianPayment called for ID: " . $penggajian->id_gaji);
            
            $penggajian->loadMissing(['employee']);

            $employeeName = $penggajian->employee->name ?? 'Unknown';
            $totalGaji = (float)($penggajian->total_gaji_bersih ?? 0);

            Log::info("JournalService: Creating payment journal - Employee: {$employeeName}, Amount: {$totalGaji}");

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => now(),
                'description' => "Pembayaran Gaji {$employeeName} - {$penggajian->no_transaksi_gaji}",
                'source_type' => 'penggajian_payment',
                'source_id' => $penggajian->id_gaji,
                'status' => 'posted',
                'total_debit' => $totalGaji,
                'total_credit' => $totalGaji,
            ]);

            // Debit: Hutang Gaji (membayar utang)
            $this->addJournalItem($journal, $totalGaji, 0, '2102', 'Hutang Gaji');

            // Credit: Kas (uang keluar)
            $this->addJournalItem($journal, 0, $totalGaji, '1102', 'Kas');

            Log::info("JournalService: Payment journal created successfully for penggajian ID: " . $penggajian->id_gaji);

            return $journal;
        });
    }

    /**
     * Generate jurnal dari pembayaran hutang purchase
     */
    public function createJournalFromPurchasePayment($purchase, $payment): JournalEntry
    {
        return DB::transaction(function () use ($purchase, $payment) {
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $payment->payment_date,
                'description' => "Pelunasan Hutang {$purchase->supplier->name} - PO {$purchase->purchase_number}",
                'source_type' => 'purchase_payment',
                'source_id' => $payment->id,
                'status' => 'posted',
                'total_debit' => $payment->amount,
                'total_credit' => $payment->amount,
            ]);

            // Debit Utang Usaha
            $this->addJournalItem($journal, $payment->amount, 0, '2101', 'Hutang Usaha');

            // Credit Kas
            $this->addJournalItem($journal, 0, $payment->amount, '1102', 'Kas');

            return $journal;
        });
    }

    /**
     * Generate jurnal dari pembelian bahan baku (Metode Perpetual - Sederhana)
     */
    public function createJournalFromPurchase(Purchase $purchase): JournalEntry
    {
        return DB::transaction(function () use ($purchase) {
            // Hapus journal yang ada untuk purchase ini (untuk avoid duplicate)
            $existingJournal = JournalEntry::where('source_type', 'purchase')
                ->where('source_id', $purchase->id)
                ->first();
            
            if ($existingJournal) {
                // Hapus journal items dulu
                JournalEntryItem::where('journal_entry_id', $existingJournal->id)->delete();
                // Hapus journal
                $existingJournal->delete();
            }
            
            // Hitung nilai-nilai berdasarkan tax_type dan diskon
            $totalDpp = 0; // Total DPP (harga beli SEBELUM PPN)
            
            // Hitung total DPP dari semua item
            $totalItemDpp = 0;
            foreach ($purchase->items as $item) {
                if ($item->tax_type === 'after_tax') {
                    // Item termasuk PPN, maka DPP = subtotal / 1.11
                    $totalItemDpp += $item->subtotal / (1 + ($purchase->ppn_rate / 100));
                } else {
                    // Item sebelum PPN, maka DPP = subtotal
                    $totalItemDpp += $item->subtotal;
                }
            }
            
            // Kurangi diskon dari DPP
            $totalDpp = $totalItemDpp - ($purchase->discount_amount ?? 0);
            
            $ppnAmount = $purchase->ppn_amount; // PPN
            $fobCost = $purchase->fob_cost; // FOB
            
            // Total debit = DPP setelah diskon + PPN + FOB
            $totalDebit = $totalDpp + $ppnAmount + $fobCost;
            
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $purchase->purchase_date,
                'description' => "Pembelian {$purchase->supplier->name}",
                'source_type' => 'purchase',
                'source_id' => $purchase->id,
                'status' => 'posted',
                'total_debit' => $totalDebit,
                'total_credit' => $totalDebit,
            ]);

            $isAuxiliary = ($purchase->purchase_type ?? 'raw_material') === Purchase::TYPE_AUXILIARY_MATERIAL;
            
            // ============================================================
            // DEBIT 1: Persediaan/Beban per item
            // ============================================================
            // Untuk bahan penolong: Masuk ke Persediaan dulu (bukan langsung beban)
            // Untuk bahan baku: DPP masuk ke Persediaan, FOB terpisah
            
            if ($isAuxiliary) {
                // BAHAN PENOLONG: Masuk ke Persediaan per item (sama seperti bahan baku)
                // Hitung proporsi FOB per item
                $totalSubtotal = $purchase->items->sum('subtotal');
                
                foreach ($purchase->items as $item) {
                    $aux = $item->auxiliaryMaterial;
                    if (!$aux) continue;
                    
                    // Hitung DPP untuk item ini
                    $itemDpp = 0;
                    if ($item->tax_type === 'after_tax') {
                        $itemDpp = $item->subtotal / (1 + ($purchase->ppn_rate / 100));
                    } else {
                        $itemDpp = $item->subtotal;
                    }
                    
                    if ($itemDpp <= 0) continue;
                    
                    // Cari atau buat COA "Pers. Bahan Penolong - [nama bahan penolong]"
                    $coaName = 'Pers. Bahan Penolong - ' . $aux->name;
                    $persediaanCoa = \App\Models\ChartOfAccount::where('account_name', $coaName)
                        ->where('code', 'LIKE', '1107%')
                        ->first();
                    
                    if (!$persediaanCoa) {
                        // Buat COA baru jika belum ada
                        $persediaanCoa = \App\Models\ChartOfAccount::create([
                            'code' => '1107' . str_pad($aux->id, 2, '0', STR_PAD_LEFT),
                            'account_name' => $coaName,
                            'account_type' => 'asset',
                            'normal_balance' => 'debit',
                        ]);
                    }
                    
                    $this->addJournalItem($journal, $itemDpp, 0, $persediaanCoa->code, $persediaanCoa->account_name);
                }
                
                // FOB untuk bahan penolong: terpisah sebagai Beban Transport
                if ($fobCost > 0) {
                    $this->addJournalItem($journal, $fobCost, 0, '56', 'Beban Transport Pembelian');
                }
            } else {
                // BAHAN BAKU: DPP ke Persediaan per item
                foreach ($purchase->items as $item) {
                    $material = $item->rawMaterial;
                    
                    $itemDpp = 0;
                    if ($item->tax_type === 'after_tax') {
                        $itemDpp = $item->subtotal / (1 + ($purchase->ppn_rate / 100));
                    } else {
                        $itemDpp = $item->subtotal;
                    }
                    
                    if ($material) {
                        $persediaanCoa = \App\Models\ChartOfAccount::where('code', 'LIKE', '1104%')
                            ->where('account_name', 'LIKE', '%' . $material->name . '%')
                            ->where('account_name', 'LIKE', '%Pers%')
                            ->first();
                        
                        if (!$persediaanCoa) {
                            $persediaanCoa = \App\Models\ChartOfAccount::where('account_name', 'LIKE', '%Pers%Bahan Baku%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Persediaan%Baku%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Pers%Baku%' . $material->name . '%')
                                ->first();
                        }
                        
                        if ($persediaanCoa) {
                            $this->addJournalItem($journal, $itemDpp, 0, $persediaanCoa->code, $persediaanCoa->account_name);
                        } else {
                            $this->addJournalItem($journal, $itemDpp, 0, '1104', 'Pers Bahan Baku ' . $material->name);
                            \Log::warning("COA spesifik tidak ditemukan untuk material: " . $material->name);
                        }
                    } else {
                        throw new \Exception("Material tidak ditemukan untuk item pembelian");
                    }
                }
                
                // FOB untuk bahan baku: terpisah sebagai Beban Transport
                if ($fobCost > 0) {
                    $this->addJournalItem($journal, $fobCost, 0, '56', 'Beban Transport Pembelian');
                }
            }

            // DEBIT 2: PPN Masukan (jika ada) - untuk semua jenis
            if ($ppnAmount > 0) {
                $this->addJournalItem($journal, $ppnAmount, 0, '1117', 'PPN Masukkan');
            }

            // ============================================================
            // KREDIT 1: Kas atau Utang Usaha
            // ============================================================
            if ($purchase->payment_method === 'credit') {
                if ($purchase->down_payment > 0) {
                    // Ada DP: kredit Uang Muka Pembelian (bukan kas)
                    $this->addJournalItem($journal, 0, $purchase->down_payment, '1118', 'Uang Muka Pembelian');
                    $sisaUtang = $totalDebit - $purchase->down_payment;
                    $this->addJournalItem($journal, 0, $sisaUtang, '2101', 'Hutang Usaha');
                } else {
                    $this->addJournalItem($journal, 0, $totalDebit, '2101', 'Hutang Usaha');
                }
            } else {
                $this->addJournalItem($journal, 0, $totalDebit, '1102', 'Kas');
            }

            // KREDIT 2: Diskon Pembelian (jika ada)
            if ($purchase->discount_amount > 0) {
                $this->addJournalItem($journal, 0, $purchase->discount_amount, '58', 'Diskon Pembelian');
            }

            return $journal;
        });
    }

    /**
     * Generate jurnal dari retur pembelian (Metode Perpetual)
     */
    public function createJournalFromPurchaseReturn(PurchaseReturn $purchaseReturn): JournalEntry
    {
        return DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->loadMissing(['purchase.supplier', 'items.rawMaterial', 'items.auxiliaryMaterial']);
            $purchase = $purchaseReturn->purchase;
            
            $totalAmount = 0;
            $itemsData = [];

            $isAuxiliary = ($purchase->purchase_type ?? 'raw_material') === \App\Models\Purchase::TYPE_AUXILIARY_MATERIAL;

            foreach ($purchaseReturn->items as $item) {
                // Ambil tax_type dari purchase item
                $purchaseItem = $item->purchaseItem;
                $taxType = $purchaseItem ? $purchaseItem->tax_type : 'after_tax';
                $ppnRate = $purchase->ppn_rate ?? 0;

                $itemSubtotal = $item->subtotal;
                $itemDpp = $itemSubtotal;

                $material = $isAuxiliary ? $item->auxiliaryMaterial : $item->rawMaterial;
                
                $persediaanCoa = null;
                if ($material) {
                    if ($isAuxiliary) {
                        $persediaanCoa = \App\Models\ChartOfAccount::where('code', 'LIKE', '1107%')
                            ->where('account_name', 'LIKE', '%' . $material->name . '%')
                            ->where('account_name', 'LIKE', '%Pers%')
                            ->first();

                        if (!$persediaanCoa) {
                            $persediaanCoa = \App\Models\ChartOfAccount::where('account_name', 'LIKE', '%Pers%Bahan Penolong%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Persediaan%Penolong%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Pers%Penolong%' . $material->name . '%')
                                ->first();
                        }
                    } else {
                        $persediaanCoa = \App\Models\ChartOfAccount::where('code', 'LIKE', '1104%')
                            ->where('account_name', 'LIKE', '%' . $material->name . '%')
                            ->where('account_name', 'LIKE', '%Pers%')
                            ->first();

                        if (!$persediaanCoa) {
                            $persediaanCoa = \App\Models\ChartOfAccount::where('account_name', 'LIKE', '%Pers%Bahan Baku%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Persediaan%Baku%' . $material->name . '%')
                                ->orWhere('account_name', 'LIKE', '%Pers%Baku%' . $material->name . '%')
                                ->first();
                        }
                    }
                }
                
                $itemsData[] = [
                    'amount' => $itemDpp,
                    'coa' => $persediaanCoa,
                    'material_name' => $material ? $material->name : 'Unknown Material'
                ];
                
                $totalAmount += $itemDpp;
            }

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $purchaseReturn->return_date,
                'description' => "Retur Pembelian {$purchase->supplier->name} - {$purchaseReturn->return_number}",
                'source_type' => 'purchase_return',
                'source_id' => $purchaseReturn->id,
                'status' => 'posted',
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
            ]);

            // DEBIT: Utang Usaha (Jika kredit) atau Kas (Jika cash)
            if ($purchase->payment_method === 'credit') {
                $this->addJournalItem($journal, $totalAmount, 0, '2101', 'Hutang Usaha');
            } else {
                $this->addJournalItem($journal, $totalAmount, 0, '1102', 'Kas');
            }

            // KREDIT: Persediaan Bahan Baku / Penolong per item
            foreach ($itemsData as $data) {
                if ($data['coa']) {
                    $this->addJournalItem($journal, 0, $data['amount'], $data['coa']->code, $data['coa']->account_name);
                } else {
                    $defaultCoaCode = $isAuxiliary ? '1107' : '1104';
                    $defaultCoaName = $isAuxiliary ? 'Pers Bahan Penolong ' . $data['material_name'] : 'Pers Bahan Baku ' . $data['material_name'];
                    $this->addJournalItem($journal, 0, $data['amount'], $defaultCoaCode, $defaultCoaName);
                }
            }

            return $journal;
        });
    }

    /**
     * Generate jurnal dari biaya overhead
     */
    public function createJournalFromOverhead(BiayaOverhead $overhead): JournalEntry
    {
        return DB::transaction(function () use ($overhead) {
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $overhead->periode,
                'description' => "Biaya {$overhead->jenis_biaya}",
                'source_type' => 'overhead',
                'source_id' => $overhead->id,
                'status' => 'posted',
                'total_debit' => $overhead->total,
                'total_credit' => $overhead->total,
            ]);

            // Ambil akun yang dipilih di form
            $selectedAccount = $overhead->account;
            if ($selectedAccount) {
                // Debit akun yang dipilih (sesuai form)
                \App\Models\JournalEntryItem::create([
                    'journal_entry_id' => $journal->id,
                    'chart_of_account_id' => $selectedAccount->id,
                    'debit' => $overhead->total,
                    'credit' => 0,
                    'description' => $journal->description,
                ]);
            } else {
                // Fallback ke Biaya Overhead Pabrik jika tidak ada akun yang dipilih
                $this->addJournalItem($journal, $overhead->total, 0, '513', 'Biaya Overhead Pabrik');
            }

            // Credit Kas
            $this->addJournalItem($journal, 0, $overhead->total, '1102', 'Kas');

            return $journal;
        });
    }

    /**
     * Generate jurnal dari pemakaian bahan baku (saat start job)
     */
    public function createJournalFromRawMaterialUsage(RawMaterialUsage $usage): JournalEntry
    {
        return DB::transaction(function () use ($usage) {
            // Ambil tanggal dari job order (gunakan order_date bukan mulai_job_at)
            $jobOrder = $usage->jobOrder;
            $transactionDate = $jobOrder ? $jobOrder->order_date : now();
            
            // Gunakan harga_per_unit dari materials job order untuk sesuai dengan ringkasan biaya di view
            $jobOrderMaterial = $jobOrder->materials()
                ->where('bahan_baku_id', $usage->raw_material_id)
                ->first();
            
            $unitPrice = $jobOrderMaterial ? (float) $jobOrderMaterial->harga_per_unit : (float) $usage->unit_price;
            $actualCost = (float) $usage->quantity_used * $unitPrice;
            
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $transactionDate, // Gunakan tanggal pemesanan
                'description' => "Pemakaian Bahan Baku",
                'source_type' => 'raw_material_usage',
                'source_id' => $usage->id,
                'status' => 'posted',
                'total_debit' => $actualCost,
                'total_credit' => $actualCost,
            ]);

            // Gunakan COA spesifik material untuk persediaan (inventory_coa_id)
            $material = $usage->rawMaterial;
            $coaCode = '1104'; // Default untuk Pers. Bahan Baku
            $coaName = 'Pers. Bahan Baku';
            
            if ($material && $material->inventory_coa_id) {
                $coa = \App\Models\ChartOfAccount::find($material->inventory_coa_id);
                if ($coa) {
                    $coaCode = $coa->code;
                    $coaName = $coa->account_name ?? 'Pers. Bahan Baku';
                }
            } elseif ($material && $material->chart_of_account_id) {
                // Fallback ke chart_of_account_id jika inventory_coa_id tidak ada
                $coa = $material->chartOfAccount;
                if ($coa) {
                    $coaCode = $coa->code;
                    $coaName = $coa->account_name ?? 'Pers. Bahan Baku';
                }
            }

            // Debit BDP - Bahan Baku (sesuai seeder terbaru)
            $this->addJournalItem($journal, $actualCost, 0, '110601', 'BDP - Bahan Baku');

            // Credit Persediaan Bahan Baku (gunakan COA spesifik jika ada)
            $this->addJournalItem($journal, 0, $actualCost, $coaCode, $coaName);

            return $journal;
        });
    }

    /**
     * Generate jurnal saat selesai job (tenaga kerja dan overhead)
     */
    public function createJournalFromJobOrderFinish(JobOrder $jobOrder): array
    {
        $journals = [];
        
        // Hitung BTKL sama persis dengan yang ditampilkan di show page:
        // durasi per unit (dari BOM) × total qty × tarif BTKL
        $totalQty = $jobOrder->jobOrderDetails->sum('quantity');
        $productId = $jobOrder->jobOrderDetails->first()?->product_id ?? $jobOrder->product_id;
        $bomProcess = DB::table('bill_of_material_processes as bmp')
            ->join('bill_of_materials as bom', 'bom.id', '=', 'bmp.bill_of_material_id')
            ->where('bom.product_id', $productId)
            ->select('bmp.duration_minutes', 'bom.btkl_rate_per_hour')
            ->first();
        
        $durasiPerUnit = $bomProcess ? (float)$bomProcess->duration_minutes : 0;
        $durasiJam = ($durasiPerUnit * $totalQty) / 60;
        $btklRate = $bomProcess ? (float)$bomProcess->btkl_rate_per_hour : 0;
        if ($btklRate <= 0) {
            $calcService = app(\App\Services\JobOrderCalculationService::class);
            $btklRate = $calcService->getBtklRatePerHour();
        }
        $laborCost = round($durasiJam * $btklRate, 2);
        
        // Hitung BOP live sesuai yang ditampilkan di view (ringkasan biaya)
        $overheadCost = $this->calculateLiveBOP($jobOrder);

        // Jurnal 1: Tenaga Kerja Langsung
        if ($laborCost > 0) {
            $journal1 = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $jobOrder->order_date,
                'description' => "Tenaga Kerja Langsung - Job Order #{$jobOrder->id}",
                'source_type' => 'job_order_labor',
                'source_id' => $jobOrder->id,
                'status' => 'posted',
                'total_debit' => $laborCost,
                'total_credit' => $laborCost,
            ]);

            // DEBIT: BDP - BTKL (pakai $laborCost yang sudah dihitung benar)
            $this->addJournalItem($journal1, $laborCost, 0, '110602', 'BDP - BTKL');

            // CREDIT: Beban Gaji dan Upah
            $this->addJournalItem($journal1, 0, $laborCost, '57', 'Beban Gaji dan Upah');

            $journals[] = $journal1;
        }

        // Jurnal 2: Overhead - PROPORSI BERDASARKAN BIAYA OVERHEAD BULAN INI
        if ($overheadCost > 0) {
            $journal2 = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $jobOrder->order_date,
                'description' => "Overhead Pabrik - Job Order #{$jobOrder->id}",
                'source_type' => 'job_order_overhead',
                'source_id' => $jobOrder->id,
                'status' => 'posted',
                'total_debit' => $overheadCost,
                'total_credit' => $overheadCost,
            ]);

            // DEBIT: BDP - BOP (total overhead)
            $this->addJournalItem($journal2, $overheadCost, 0, '110603', 'BDP - BOP');

            // Hitung BOP dari POR (TANPA bahan penolong)
            $por = DB::table('overhead_por')
                ->where('periode', now()->format('Y-m'))
                ->orderByDesc('id')
                ->first();

            // Durasi sama persis dengan yang dipakai di calculateLiveBOP dan view
            $bopDurasiJam = ($durasiPerUnit * $totalQty) / 60;
            if ($bopDurasiJam <= 0) {
                $bopDurasiJam = (float)($jobOrder->durasi_jam ?? 0);
            }

            $porRate = $por ? (float) $por->por_per_jam : 0;
            // Gunakan $overheadCost (sudah dihitung di atas) sebagai basis kredit
            // agar debit header = total kredit item (tidak ada selisih)
            $bopPabrik = $overheadCost;

            // KREDIT 1: BOP per komponen berdasarkan PROPORSI biaya overhead bulan ini
            if ($bopPabrik > 0) {
                // Ambil semua biaya overhead BOP periode ini
                $bebanItems = \App\Models\BiayaOverhead::with('account')
                    ->where('kategori', 'BOP')
                    ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [now()->format('Y-m')])
                    ->get();

                $totalBebanBulan = $bebanItems->sum('total');

                if ($totalBebanBulan > 0) {
                    $totalAlokasi = 0;
                    $alokasiItems = [];
                    
                    foreach ($bebanItems as $beban) {
                        if ((float) $beban->total <= 0) continue;

                        // Hitung proporsi komponen ini terhadap total BOP bulan
                        $proporsi = (float) $beban->total / $totalBebanBulan;
                        $alokasi = $proporsi * $bopPabrik; // TANPA round dulu
                        
                        $totalAlokasi += $alokasi;
                        $alokasiItems[] = [
                            'beban' => $beban,
                            'alokasi' => $alokasi
                        ];
                    }

                    // Bulatkan dan catat jurnal
                    $totalAlokasiRounded = 0;
                    foreach ($alokasiItems as $idx => $item) {
                        $beban = $item['beban'];
                        $alokasiRounded = round($item['alokasi'], 2);
                        
                        // Item terakhir: sesuaikan dengan sisa supaya balance
                        if ($idx === count($alokasiItems) - 1) {
                            $alokasiRounded = round($bopPabrik - $totalAlokasiRounded, 2);
                        }
                        
                        if ($alokasiRounded > 0) {
                            // Format nama akun sesuai jenis biaya
                            $jenisBeban = $beban->jenis_biaya;
                            $accountName = '';
                            
                            // Jika beban adalah gaji (BTKL), gunakan format "BOP BTKL - {posisi}"
                            if (stripos($jenisBeban, 'gaji') !== false || stripos($jenisBeban, 'pembayaran gaji') !== false) {
                                // Extract posisi dari jenis biaya (misal: "Pembayaran Gaji Kasir" -> "Kasir")
                                $posisi = str_replace(['Pembayaran Gaji', 'Gaji', 'pembayaran gaji', 'gaji'], '', $jenisBeban);
                                $posisi = trim($posisi);
                                $accountName = "BOP BTKL - {$posisi}";
                            } else {
                                // Untuk beban lainnya, gunakan format "BOP - {nama beban}"
                                $accountName = "BOP - {$jenisBeban}";
                            }
                            
                            if ($beban->account) {
                                $this->addJournalItem($journal2, 0, $alokasiRounded, $beban->account->code, $accountName);
                            } else {
                                $this->addJournalItem($journal2, 0, $alokasiRounded, '53', $accountName);
                            }
                        }
                        
                        $totalAlokasiRounded += $alokasiRounded;
                    }
                } else {
                    // Fallback jika tidak ada breakdown beban
                    $bopPabrikRounded = round($bopPabrik, 2);
                    $this->addJournalItem($journal2, 0, $bopPabrikRounded, '53', 'BOP - Overhead Pabrik');
                }
            }

            // TIDAK PERLU KREDIT BAHAN PENOLONG DARI BOM
            // Karena bahan penolong sudah masuk lewat proporsi di atas (dari biaya_overhead)

            $journals[] = $journal2;
        }

        // Simpan semua journals yang sudah dibuat
        return $journals;
    }

    /**
     * Generate jurnal dari penyelesaian produksi (barang jadi)
     */
    public function createJournalFromProductionCompletion(JobOrder $jobOrder): JournalEntry
    {
        // Hitung total cost yang SAMA PERSIS dengan yang ditampilkan di ringkasan biaya (view)
        // 1. Material cost: sama dengan perhitungan $liveBbb di view
        $materials = $jobOrder->materials()->get();
        $materialCost = 0;
        foreach ($materials as $m) {
            $materialCost += (float)$m->qty_total * (float)$m->harga_per_unit;
        }
        
        // 2. Labor cost: hitung live sama persis dengan createJournalFromJobOrderFinish
        $jobOrder->loadMissing('jobOrderDetails');
        $totalQtyComp = $jobOrder->jobOrderDetails->sum('quantity');
        $productIdComp = $jobOrder->jobOrderDetails->first()?->product_id ?? $jobOrder->product_id;
        $bomProcessComp = DB::table('bill_of_material_processes as bmp')
            ->join('bill_of_materials as bom', 'bom.id', '=', 'bmp.bill_of_material_id')
            ->where('bom.product_id', $productIdComp)
            ->select('bmp.duration_minutes', 'bom.btkl_rate_per_hour')
            ->first();
        $durasiPerUnitComp = $bomProcessComp ? (float)$bomProcessComp->duration_minutes : 0;
        $durasiJamComp = ($durasiPerUnitComp * $totalQtyComp) / 60;
        $btklRateComp = $bomProcessComp ? (float)$bomProcessComp->btkl_rate_per_hour : 0;
        if ($btklRateComp <= 0) {
            $calcService = app(\App\Services\JobOrderCalculationService::class);
            $btklRateComp = $calcService->getBtklRatePerHour();
        }
        $laborCost = round($durasiJamComp * $btklRateComp, 2);
        
        // 3. Overhead cost: sesuai yang dijurnal di createJournalFromJobOrderFinish (gunakan method yang sama)
        $overheadCost = $this->calculateLiveBOP($jobOrder);
        
        $totalCost = $materialCost + $laborCost + $overheadCost;
        
        $journal = JournalEntry::create([
            'journal_number' => JournalEntry::generateJournalNumber('JU'),
            'transaction_date' => $jobOrder->order_date, // Gunakan tanggal pemesanan
            'description' => "Penyelesaian Produksi Job Order #{$jobOrder->id}",
            'source_type' => 'production_completion',
            'source_id' => $jobOrder->id,
            'status' => 'posted',
            'total_debit' => $totalCost,
            'total_credit' => $totalCost,
        ]);

        // Debit Persediaan Barang Jadi - gunakan COA spesifik produk jika ada
        $product = $jobOrder->product ?? \App\Models\Product::find($jobOrder->product_id);
        if ($product && $product->inventoryCoa) {
            $this->addJournalItem($journal, $totalCost, 0, $product->inventoryCoa->code, $product->inventoryCoa->account_name);
        } else {
            // Fallback cari dari DB berdasarkan nama produk
            $productName = $product ? $product->name : null;
            $invCoa = $productName
                ? \App\Models\ChartOfAccount::where('account_name', 'Pers. Barang Jadi - ' . $productName)->first()
                : null;
            $this->addJournalItem($journal, $totalCost, 0, $invCoa ? $invCoa->code : '1105', $invCoa ? $invCoa->account_name : 'Pers. Barang Jadi');
        }

        // Credit BDP - Bahan Baku (sesuai seeder terbaru) - gunakan 110601 sesuai yang digunakan saat mulai job
        if ($materialCost > 0) {
            $this->addJournalItem($journal, 0, $materialCost, '110601', 'BDP - Bahan Baku');
        }

        // Credit BDP - BTKL (sesuai seeder terbaru) - gunakan 110602 sesuai yang dijurnal saat selesai job
        if ($laborCost > 0) {
            $this->addJournalItem($journal, 0, $laborCost, '110602', 'BDP - BTKL');
        }

        // Credit BDP - BOP (sesuai seeder terbaru) - gunakan 110603 sesuai yang dijurnal saat selesai job
        if ($overheadCost > 0) {
            $this->addJournalItem($journal, 0, $overheadCost, '110603', 'BDP - BOP');
        }

        return $journal;
    }

    /**
     * Generate jurnal dari payroll/gaji
     * Debit Beban Gaji, Credit Kas (langsung dibayar)
     */
    public function createJournalFromPayroll(Payroll $payroll): JournalEntry
    {
        return DB::transaction(function () use ($payroll) {
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $payroll->payroll_date,
                'description' => "Penggajian Bulan {$payroll->payroll_date}",
                'source_type' => 'payroll',
                'source_id' => $payroll->id,
                'status' => 'posted',
                'total_debit' => $payroll->total_salary,
                'total_credit' => $payroll->total_salary,
            ]);

            // Debit: Beban Gaji
            $this->addJournalItem($journal, $payroll->total_salary, 0, '522', 'Beban Gaji');

            // Credit: Kas (langsung dibayar)
            $this->addJournalItem($journal, 0, $payroll->total_salary, '1102', 'Kas');

            return $journal;
        });
    }

    /**
     * Helper untuk tambah journal item
     */
    public function addJournalItem(JournalEntry $journal, float $debit, float $credit, string $accountCode, string $accountName): void
    {
        $account = \App\Models\ChartOfAccount::where('code', $accountCode)->first();
        if (!$account) {
            // Create account if not exists
            $account = \App\Models\ChartOfAccount::create([
                'code' => $accountCode,
                'account_name' => $accountName,
                'account_type' => $this->getAccountType($accountCode),
                'account_group_name' => $this->getAccountCategory($accountCode),
                'normal_balance_position' => $this->getNormalBalance($accountCode),
                'is_active' => true,
            ]);
        }

        $journalItem = \App\Models\JournalEntryItem::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $accountName,
        ]);

        // UPDATE BALANCE AKUN - INI YANG KURANG
        if ($account->normal_balance_position === 'debit') {
            // Untuk akun normal debit (asset, expense)
            $account->balance += $debit - $credit;
        } else {
            // Untuk akun normal credit (liability, equity, revenue)
            $account->balance += $credit - $debit;
        }
        $account->save();

        \Log::info('Journal item created successfully', [
            'journal_id' => $journal->id,
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'debit' => $debit,
            'credit' => $credit,
            'item_id' => $journalItem->id,
            'new_balance' => $account->balance
        ]);
    }

    /**
     * Helper untuk tentukan account type dari kode
     */
    private function getAccountType(string $code): string
    {
        if (in_array(substr($code, 0, 1), ['1'])) return 'asset';
        if (in_array(substr($code, 0, 1), ['2'])) return 'liability';
        if (in_array(substr($code, 0, 1), ['3'])) return 'equity';
        if (in_array(substr($code, 0, 1), ['4'])) return 'revenue';
        if (in_array(substr($code, 0, 1), ['5'])) return 'expense';
        return 'asset';
    }

    /**
     * Helper untuk tentukan account category dari kode
     */
    private function getAccountCategory(string $code): string
    {
        if (in_array(substr($code, 0, 1), ['1'])) return 'current_assets';
        if (in_array(substr($code, 0, 1), ['2'])) return 'current_liabilities';
        return 'other';
    }

    /**
     * Helper untuk tentukan normal balance dari kode
     */
    private function getNormalBalance(string $code): string
    {
        if (in_array(substr($code, 0, 1), ['1', '5'])) return 'debit';
        if (in_array(substr($code, 0, 1), ['2', '3', '4'])) return 'credit';
        return 'debit';
    }

    /**
     * Void/batalkan jurnal
     */
    public function voidJournal(JournalEntry $journal): bool
    {
        return $journal->update(['status' => 'void']);
    }

    /**
     * Hitung BOP live sesuai yang ditampilkan di view (ringkasan biaya)
     */
    private function calculateLiveBOP(JobOrder $jobOrder): float
    {
        // 1. POR per jam dari overhead_por bulan berjalan
        $por = DB::table('overhead_por')
            ->where('periode', now()->format('Y-m'))
            ->orderByDesc('id')
            ->first();

        // Durasi = durasi per unit (dari BOM) × total qty — SAMA PERSIS dengan view show
        $jobOrder->loadMissing('jobOrderDetails');
        $totalQty = $jobOrder->jobOrderDetails->sum('quantity');
        $productId = $jobOrder->jobOrderDetails->first()?->product_id ?? $jobOrder->product_id;

        $bomProcess = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', function($q) use ($productId) {
            $q->where('product_id', $productId);
        })->first();

        $durasiPerUnit = $bomProcess ? (float)$bomProcess->duration_minutes : 0;
        $durasiJam = ($durasiPerUnit * $totalQty) / 60;

        // Fallback ke durasi_jam tersimpan jika BOM tidak ditemukan
        if ($durasiJam <= 0) {
            $durasiJam = (float)($jobOrder->durasi_jam ?? 0);
        }

        $porRate = $por ? (float) $por->por_per_jam : 0;
        $overheadBop = $porRate * $durasiJam;

        return round($overheadBop, 2);
    }

    /**
     * Generate jurnal dari pemakaian bahan penolong saat selesai job
     */
    private function createJournalFromAuxiliaryMaterialUsage(JobOrder $jobOrder): array
    {
        $journals = [];
        
        // Ambil semua detail job order
        $details = $jobOrder->jobOrderDetails;
        if ($details->isEmpty()) {
            // Fallback untuk job order lama tanpa details
            if ($jobOrder->product && $jobOrder->quantity) {
                $details = collect([(object) [
                    'product_id' => $jobOrder->product_id,
                    'quantity' => $jobOrder->quantity
                ]]);
            }
        }

        // Kumpulkan pemakaian bahan penolong per material
        $auxiliaryUsages = [];
        
        foreach ($details as $detail) {
            $bom = \App\Models\BillOfMaterial::with(['auxiliaries.auxiliaryMaterial.expenseCoa'])
                ->where('product_id', $detail->product_id)->first();
            if (!$bom) continue;
            
            foreach ($bom->auxiliaries as $bomAux) {
                $auxiliaryMaterial = $bomAux->auxiliaryMaterial;
                if (!$auxiliaryMaterial) continue;
                
                $materialId = $auxiliaryMaterial->id;
                $qtyPerUnit = (float)($bomAux->quantity ?? 0);
                $totalQty = $qtyPerUnit * (float)($detail->quantity ?? 0);
                $unitCost = (float)($bomAux->unit_cost ?? 0);
                $totalCost = $totalQty * $unitCost;
                
                if ($totalCost <= 0) continue;
                
                if (!isset($auxiliaryUsages[$materialId])) {
                    $auxiliaryUsages[$materialId] = [
                        'material' => $auxiliaryMaterial,
                        'total_qty' => 0,
                        'total_cost' => 0,
                        'unit_cost' => $unitCost
                    ];
                }
                
                $auxiliaryUsages[$materialId]['total_qty'] += $totalQty;
                $auxiliaryUsages[$materialId]['total_cost'] += $totalCost;
            }
        }

        // Buat jurnal untuk setiap bahan penolong yang digunakan
        foreach ($auxiliaryUsages as $usage) {
            $material = $usage['material'];
            $totalCost = $usage['total_cost'];
            
            if ($totalCost <= 0) continue;
            
            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $jobOrder->order_date,
                'description' => "Pemakaian Bahan Penolong {$material->name} - Job Order #{$jobOrder->id}",
                'source_type' => 'auxiliary_material_usage',
                'source_id' => $jobOrder->id,
                'status' => 'posted',
                'total_debit' => $totalCost,
                'total_credit' => $totalCost,
            ]);

            // Cari COA expense untuk bahan penolong ini (BOP BP - [Nama Material])
            $expenseCoa = $material->expenseCoa;
            if ($expenseCoa) {
                // Debit BOP BP - [Nama Material] (expense COA)
                $this->addJournalItem($journal, $totalCost, 0, $expenseCoa->code, $expenseCoa->account_name);
            } else {
                // Fallback: gunakan COA generic BOP BP
                $coaName = "BOP BP - {$material->name}";
                $this->addJournalItem($journal, $totalCost, 0, '55', $coaName);
            }

            // Credit Persediaan Bahan Penolong - [Nama Material]
            $inventoryCoa = $material->chartOfAccount;
            if ($inventoryCoa) {
                $this->addJournalItem($journal, 0, $totalCost, $inventoryCoa->code, $inventoryCoa->account_name);
            } else {
                // Fallback: gunakan COA generic persediaan bahan penolong
                $coaName = "Pers Bahan Penolong {$material->name}";
                $this->addJournalItem($journal, 0, $totalCost, '1107', $coaName);
            }
            
            $journals[] = $journal;
        }

        return $journals;
    }

    /**
     * Generate jurnal pemakaian bahan penolong saat MULAI job order.
     * Debit: BOP BP - [nama]  (dari proporsi biaya_overhead bulan ini)
     * Credit: Pers. Bahan Penolong - [nama]  (sama)
     * Hanya untuk bahan penolong yang ada di BOM job ini DAN di biaya_overhead bulan ini.
     */
    public function createAuxiliaryMaterialJournalForJobStart(JobOrder $jobOrder): void
    {
        // Ambil semua biaya overhead BOP BP bulan ini
        $bebanItems = \App\Models\BiayaOverhead::with('account')
            ->where('kategori', 'BOP')
            ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [now()->format('Y-m')])
            ->whereHas('account', function($q) {
                $q->where('account_name', 'LIKE', 'BOP BP -%');
            })
            ->get();

        if ($bebanItems->isEmpty()) return;

        $totalBebanBulan = \App\Models\BiayaOverhead::where('kategori', 'BOP')
            ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [now()->format('Y-m')])
            ->sum('total');

        if ($totalBebanBulan <= 0) return;

        // Hitung BOP dari POR × durasi
        $por = DB::table('overhead_por')
            ->where('periode', now()->format('Y-m'))
            ->orderByDesc('id')
            ->first();

        $durasiJam = (float) ($jobOrder->durasi_jam ?? 0);
        if ($durasiJam <= 0) {
            $bomProcess = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', function ($q) use ($jobOrder) {
                $q->where('product_id', $jobOrder->product_id);
            })->first();
            $durasiJam = $bomProcess ? round($bomProcess->duration_minutes / 60, 4) : 0;
        }

        $porRate = $por ? (float) $por->por_per_jam : 0;
        $bopPabrik = $porRate * $durasiJam;

        if ($bopPabrik <= 0) return;

        // Ambil bahan penolong yang ada di BOM job ini
        $details = $jobOrder->jobOrderDetails;
        if ($details->isEmpty()) {
            if ($jobOrder->product_id) {
                $details = collect([(object)['product_id' => $jobOrder->product_id, 'quantity' => $jobOrder->quantity]]);
            }
        }

        $bomAuxIds = collect();
        foreach ($details as $detail) {
            $bom = \App\Models\BillOfMaterial::with('auxiliaries.auxiliaryMaterial')
                ->where('product_id', $detail->product_id)->first();
            if ($bom) {
                foreach ($bom->auxiliaries as $aux) {
                    if ($aux->auxiliaryMaterial) {
                        $bomAuxIds->push($aux->auxiliaryMaterial->id);
                    }
                }
            }
        }

        // Buat jurnal per bahan penolong yang ada di BOM DAN di biaya_overhead
        foreach ($bebanItems as $beban) {
            if ((float) $beban->total <= 0 || !$beban->account) continue;

            // Extract nama bahan dari "BOP BP - Bumbu Marinasi"
            $materialName = trim(str_replace('BOP BP -', '', $beban->account->account_name));

            // Cek apakah bahan ini ada di BOM job ini
            $material = \App\Models\AuxiliaryMaterial::where('name', $materialName)->first();
            if (!$material || !$bomAuxIds->contains($material->id)) continue;

            // Hitung proporsi alokasi
            $proporsi = (float) $beban->total / $totalBebanBulan;
            $alokasi = round($proporsi * $bopPabrik, 2);
            if ($alokasi <= 0) continue;

            $journal = JournalEntry::create([
                'journal_number'   => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $jobOrder->order_date,
                'description'      => "Pemakaian Bahan Penolong {$materialName} - Job Order #{$jobOrder->id}",
                'source_type'      => 'auxiliary_material_usage',
                'source_id'        => $jobOrder->id,
                'status'           => 'posted',
                'total_debit'      => $alokasi,
                'total_credit'     => $alokasi,
            ]);

            // Debit: BOP BP - [nama]
            $this->addJournalItem($journal, $alokasi, 0, $beban->account->code, $beban->account->account_name);

            // Credit: Pers. Bahan Penolong - [nama]
            $persCoa = \App\Models\ChartOfAccount::where('account_name', 'Pers. Bahan Penolong - ' . $materialName)
                ->where('code', 'LIKE', '1107%')->first();

            if ($persCoa) {
                $this->addJournalItem($journal, 0, $alokasi, $persCoa->code, $persCoa->account_name);
            } else {
                $this->addJournalItem($journal, 0, $alokasi, '1107', 'Pers. Bahan Penolong - ' . $materialName);
            }

            Log::info("Auxiliary usage journal created: {$materialName}, alokasi={$alokasi}");
        }
    }

    /**
     * Jurnal Pengakuan Gaji: Dr Beban Gaji & Upah / Cr Utang Gaji
     */
    public function createRecognitionJournal(\App\Models\Penggajian $penggajian): \App\Models\JournalEntry
    {
        return DB::transaction(function () use ($penggajian) {
            $penggajian->loadMissing('employee');
            $totalGaji = (float) $penggajian->total_gaji_bersih;
            $empName = $penggajian->employee->name ?? 'Karyawan';
            $periode = $penggajian->tanggal_penggajian->format('m/Y');

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $penggajian->tanggal_penggajian,
                'description' => "Pengakuan Gaji {$empName} - {$penggajian->no_transaksi_gaji} ({$periode})",
                'source_type' => 'penggajian_recognition',
                'source_id' => $penggajian->id_gaji,
                'status' => 'posted',
                'total_debit' => $totalGaji,
                'total_credit' => $totalGaji,
            ]);

            // Debit: Beban Gaji dan Upah
            $this->addJournalItem($journal, $totalGaji, 0, '57', 'Beban Gaji dan Upah');

            // Credit: Utang Gaji
            $this->addJournalItem($journal, 0, $totalGaji, '2102', 'Utang Gaji');

            return $journal;
        });
    }

    /**
     * Jurnal Distribusi Gaji: Dr BDP-BTKL/BOP-BTKTL/Beban Adm / Cr Beban Gaji & Upah
     * Juga otomatis tambah line di biaya_overhead untuk karyawan BTKTL
     */
    public function createDistributionJournal(\App\Models\Penggajian $penggajian): ?\App\Models\JournalEntry
    {
        return DB::transaction(function () use ($penggajian) {
            $penggajian->loadMissing('employee');
            $totalGaji = (float) $penggajian->total_gaji_bersih;
            $emp = $penggajian->employee;
            $empName = $emp->name ?? 'Karyawan';
            $empType = strtoupper($emp->employee_type ?? '');
            $periode = $penggajian->tanggal_penggajian->format('m/Y');

            // BTKL: tidak perlu jurnal distribusi, sudah ditangani lewat BOM/job order
            if ($empType === 'BTKL') {
                return null;
            }

            $journal = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('JU'),
                'transaction_date' => $penggajian->tanggal_penggajian,
                'description' => "Distribusi Gaji {$empName} ({$empType}) - {$penggajian->no_transaksi_gaji} ({$periode})",
                'source_type' => 'penggajian_distribution',
                'source_id' => $penggajian->id_gaji,
                'status' => 'posted',
                'total_debit' => $totalGaji,
                'total_credit' => $totalGaji,
            ]);

            if ($empType === 'BTKTL') {
                $coa = $emp->chart_of_account_id
                    ? \App\Models\ChartOfAccount::find($emp->chart_of_account_id)
                    : null;
                $coaCode = $coa ? $coa->code : '54';
                $coaName = $coa ? $coa->account_name : "BOP BTKTL - {$empName}";
                $this->addJournalItem($journal, $totalGaji, 0, $coaCode, $coaName);

                $empPosition = $emp->position ?? $empName;
                \App\Models\BiayaOverhead::updateOrCreate(
                    [
                        'bop_code' => $coaCode,
                        'periode' => $penggajian->tanggal_penggajian->startOfMonth()->format('Y-m-d'),
                    ],
                    [
                        'jenis_biaya' => "Pembayaran Gaji {$empPosition}",
                        'kategori' => 'BOP',
                        'chart_of_account_id' => $coa?->id,
                        'total' => $totalGaji,
                        'status_pembayaran' => 'debit',
                        'catatan' => "Dari distribusi gaji {$penggajian->no_transaksi_gaji}",
                    ]
                );

            } else {
                // Non-produksi: Beban Administrasi
                $this->addJournalItem($journal, $totalGaji, 0, '5401', "Beban Adm - {$empName}");
            }

            // Credit: Beban Gaji dan Upah (balik ke nol)
            $this->addJournalItem($journal, 0, $totalGaji, '57', 'Beban Gaji dan Upah');

            return $journal;
        });
    }
}
