<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product; // Sesuaikan namespace model

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 1. Ambil Type dari Input
        $type = $this->input('type');

        // 2. Rule Dasar (Selalu Valid)
        $rules = [
            'type' => ['required', 'in:full,price,stock'],
        ];

        // 3. Cabang Logika berdasarkan Type
        if ($type === 'price') {
            // --- VALIDASI KHUSUS HARGA ---
            $rules = array_merge($rules, [
                'purchase_price' => ['required', 'numeric', 'min:0'],
                'selling_price'  => ['required', 'numeric', 'min:0'],
            ]);
        } elseif ($type === 'stock') {
            // --- VALIDASI KHUSUS STOK ---
            // Mengasumsikan frontend mengirim 'stock' sebagai Nilai Akhir (Final Value)
            $rules = array_merge($rules, [
                'qty'     => ['required', 'integer', 'min:0'],
                'note'      => ['nullable', 'string', 'max:255'],
                'adjustment' => ['required', Rule::in(['add', 'reduce', 'set'])]
            ]);
        } else {
            // --- VALIDASI FULL UPDATE (Default) ---
            // Ambil ID produk dari route parameter untuk ignore unique
            $productId = $this->route('product') ? $this->route('product')->id : null;

            $rules = array_merge($rules, [
                // Identitas
                'image'       => ['nullable', 'image', 'max:20480', 'mimes:jpeg,png,webp'], // Max 20MB
                'name'        => ['required', 'string', 'max:255'],
                'code'        => ['required', 'string', 'max:50', Rule::unique('products')->ignore($productId)],
                'status'      => ['required', Rule::in(Product::STATUSES)], // Hardcode atau Product::STATUSES
                'description' => ['nullable', 'string'],

                // Relasi
                'category_id' => ['required', 'integer', 'exists:categories,id'],
                'unit_id'     => ['required', 'integer', 'exists:units,id'],
                'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
                'size_id'     => ['nullable', 'integer', 'exists:sizes,id'],
                'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
                'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],

                // Di Full Update, Harga & Stok biasanya juga wajib/bisa diedit
                'purchase_price' => ['required', 'numeric', 'min:0'],
                'selling_price'  => ['required', 'numeric', 'min:0'],
                'min_stock'      => ['required', 'integer', 'min:0'],
                'target_margin_percent' => ['required', 'numeric', 'min:0']
            ]);
        }

        return $rules;
    }

    // Custom Pesan Error agar user paham
    public function messages(): array
    {
        return [
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.min'      => 'Harga jual tidak boleh minus.',
            'stock.required'         => 'Jumlah stok wajib diisi.',
            'code.unique'            => 'Kode barang sudah digunakan produk lain.',
        ];
    }
}
