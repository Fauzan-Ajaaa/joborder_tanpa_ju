<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\JobOrder;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = SalesTransaction::with(['salesItems.product'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);

        return view('invoices.index', compact('invoices'));
    }

    public function show($id)
    {
        $sales = SalesTransaction::with([
            'salesItems.product',
            'jobOrder',
            'jobOrder.customer',
            'jobOrder.labors.employee'
        ])->findOrFail($id);

        // Get company info (you can create a Company model or use hardcoded values)
        $company = [
            'name' => 'PT. Manufaktur Job Order Cost',
            'address' => 'Jl. Industri No. 123, Jakarta',
            'phone' => '+62 21 1234 5678',
            'email' => 'info@manufaktur.com',
            'tax_id' => '123.456.789.0-123.000'
        ];

        return view('invoices.show', compact('sales', 'company'));
    }

    public function generateInvoiceNumber($sales)
    {
        // Generate invoice number: INV-YYYYMMDD-XXXX
        $date = date('Ymd', strtotime($sales->transaction_date));
        $sequence = SalesTransaction::whereDate('transaction_date', $sales->transaction_date)
            ->where('id', '<=', $sales->id)
            ->count();
        
        return "INV-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function print($id)
    {
        $sales = SalesTransaction::with([
            'salesItems.product',
            'jobOrder',
            'jobOrder.customer',
            'jobOrder.labors.employee'
        ])->findOrFail($id);

        $company = [
            'name' => 'PT. Manufaktur Job Order Cost',
            'address' => 'Jl. Industri No. 123, Jakarta',
            'phone' => '+62 21 1234 5678',
            'email' => 'info@manufaktur.com',
            'tax_id' => '123.456.789.0-123.000'
        ];

        return view('invoices.print', compact('sales', 'company'));
    }
}
