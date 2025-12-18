<?php

// app/Http/Requests/UpdatePurchaseRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:255',

            // Validasi Array Items
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer', // Nullable karena item baru id-nya null
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ];
    }
}
