<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_id' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'reason' => 'required|in:damaged,wrong_item,excess,quality_issue,other',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.purchase_item_id' => 'required_with:items.*.quantity|exists:purchase_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string',
        ];
    }
}
