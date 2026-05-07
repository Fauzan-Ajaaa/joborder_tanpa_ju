<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseReturnRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\AuxiliaryMaterial;
use App\Models\RawMaterial;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function index(): View
    {
        $returns = PurchaseReturn::with('purchase.supplier')->latest()->paginate(15);

        return view('purchase-returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        $purchaseId = $request->query('purchase_id');
        $purchase = null;

        if ($purchaseId) {
            $purchase = Purchase::with('items.rawMaterial', 'items.auxiliaryMaterial')->where('status', 'received')->findOrFail($purchaseId);
        }

        $purchases = Purchase::where('status', 'received')->latest()->get();

        return view('purchase-returns.create', compact('purchase', 'purchases'));
    }

    public function store(PurchaseReturnRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $purchase = Purchase::with('items')->findOrFail($validated['purchase_id']);

        $items = collect($validated['items'] ?? [])->filter(function ($item) {
            return isset($item['quantity']) && (float) $item['quantity'] > 0;
        });

        if ($items->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Minimal satu item harus diisi qty retur lebih dari 0.']);
        }

        // Cek duplicate: jika ada retur pending dengan purchase_id dan return_date yang sama dalam 10 detik terakhir, anggap duplicate
        $recentDuplicate = PurchaseReturn::where('purchase_id', $purchase->id)
            ->where('return_date', $validated['return_date'])
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($recentDuplicate) {
            return redirect()->route('purchase-returns.show', $recentDuplicate)
                ->with('info', 'Retur sudah dibuat sebelumnya.');
        }

        // Ambil semua retur sebelumnya untuk purchase ini (semua status)
        $existingReturnItems = PurchaseReturnItem::whereHas('purchaseReturn', function ($q) use ($purchase) {
            $q->where('purchase_id', $purchase->id);
        })->get()->groupBy('purchase_item_id');

        try {
            $purchaseReturn = DB::transaction(function () use ($validated, $purchase, $items, $existingReturnItems) {
                // Generate return_number dalam transaction untuk menghindari race condition
                $date = now()->format('Ymd');
                $lastReturn = PurchaseReturn::whereDate('created_at', today())
                    ->where('return_number', 'like', 'RTN-' . $date . '-%')
                    ->orderByDesc('return_number')
                    ->lockForUpdate()
                    ->first();
                
                $nextNumber = 1;
                if ($lastReturn && preg_match('/RTN-' . $date . '-(\d+)/', $lastReturn->return_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }
                
                $returnNumber = 'RTN-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                
                $purchaseReturn = PurchaseReturn::create([
                    'return_number' => $returnNumber,
                    'purchase_id' => $purchase->id,
                    'return_date' => $validated['return_date'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $errors = [];

                foreach ($items as $itemData) {
                    /** @var PurchaseItem $purchaseItem */
                    $purchaseItem = $purchase->items()->findOrFail($itemData['purchase_item_id']);

                    $qty = (float) $itemData['quantity'];

                    $isAuxiliary = (bool) $purchaseItem->auxiliary_material_id;
                    $materialName = $purchaseItem->item_name;
                    $purchaseUnit = $purchaseItem->display_unit;
                    $returnUnit = $itemData['unit'] ?? $purchaseUnit;

                    $getConversionFactor = $isAuxiliary
                        ? fn (string $from, string $to) => AuxiliaryMaterial::getConversionFactor($from, $to)
                        : fn (string $from, string $to) => RawMaterial::getConversionFactor($from, $to);

                    // Hitung total qty retur sebelumnya untuk item ini dalam satuan pembelian
                    $alreadyReturned = 0.0;
                    foreach ($existingReturnItems->get($purchaseItem->id, collect()) as $prevItem) {
                        $prevFromUnit = $prevItem->unit ?: $purchaseUnit;
                        $prevFactor = $getConversionFactor($prevFromUnit, $purchaseUnit) ?? 1;
                        $alreadyReturned += (float) $prevItem->quantity * $prevFactor;
                    }

                    // Hitung qty retur baru dalam satuan pembelian
                    $qtyConvFactor = $getConversionFactor($returnUnit, $purchaseUnit) ?? 1;
                    $qtyInPurchaseUnit = $qty * $qtyConvFactor;

                    if ($alreadyReturned + $qtyInPurchaseUnit > $purchaseItem->quantity + 1e-6) {
                        $errors[] = 'Total qty retur untuk ' . $materialName . ' tidak boleh melebihi qty yang dibeli.';
                        continue;
                    }

                    // Konversi harga satuan ke satuan retur
                    $priceConversion = $getConversionFactor($purchaseUnit, $returnUnit) ?? 1;
                    $unitPriceReturn = $purchaseItem->unit_price / $priceConversion;

                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'raw_material_id' => $purchaseItem->raw_material_id,
                        'auxiliary_material_id' => $purchaseItem->auxiliary_material_id,
                        'unit' => $returnUnit,
                        'quantity' => $qty,
                        'unit_price' => $unitPriceReturn,
                        'subtotal' => $qty * $unitPriceReturn,
                    ]);
                }

                if (!empty($errors)) {
                    throw new \Exception(implode(' ', $errors));
                }

                $purchaseReturn->calculateTotals();

                return $purchaseReturn;
            });

            return redirect()->route('purchase-returns.show', $purchaseReturn)
                ->with('success', 'Retur pembelian berhasil dibuat dan menunggu persetujuan.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function show(PurchaseReturn $purchaseReturn): View
    {
        $purchaseReturn->load('purchase.supplier', 'items.rawMaterial', 'items.auxiliaryMaterial');

        return view('purchase-returns.show', compact('purchaseReturn'));
    }

    public function approve(PurchaseReturn $return): RedirectResponse
    {
        if ($return->status !== 'pending') {
            return redirect()->route('purchase-returns.show', $return)
                ->with('error', 'Hanya retur dengan status pending yang bisa di-approve');
        }

        $return->load('items.rawMaterial', 'items.auxiliaryMaterial', 'items.purchaseItem');

        foreach ($return->items as $item) {
            $purchaseItem = $item->purchaseItem;
            $fromUnit = $item->unit ?: ($purchaseItem?->display_unit ?? $item->display_unit);

            if ($item->raw_material_id) {
                $rawMaterial = $item->rawMaterial;
                if ($rawMaterial) {
                    $toUnit = $rawMaterial->unit;
                    $factor = RawMaterial::getConversionFactor($fromUnit, $toUnit) ?? 1;
                    $qtyInBaseUnit = ($item->quantity ?? 0) * $factor;
                    $rawMaterial->stock -= $qtyInBaseUnit;
                    $rawMaterial->save();
                }
            } elseif ($item->auxiliary_material_id) {
                $auxiliaryMaterial = $item->auxiliaryMaterial;
                if ($auxiliaryMaterial) {
                    $toUnit = $auxiliaryMaterial->unit;
                    $factor = AuxiliaryMaterial::getConversionFactor($fromUnit, $toUnit) ?? 1;
                    $qtyInBaseUnit = ($item->quantity ?? 0) * $factor;
                    $auxiliaryMaterial->stock -= $qtyInBaseUnit;
                    $auxiliaryMaterial->save();
                }
            }
        }

        $return->status = 'completed';
        $return->save();

        // Create Journal for Purchase Return
        $journalService = new JournalService();
        $journalService->createJournalFromPurchaseReturn($return);

        return redirect()->route('purchase-returns.show', $return)
            ->with('success', 'Retur pembelian berhasil di-approve. Stok bahan telah dikurangi.');
    }
}
