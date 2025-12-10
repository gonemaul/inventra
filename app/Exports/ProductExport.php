<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::with(['category', 'unit', 'supplier', 'brand'])->get();
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->category->name ?? '-',
            $product->unit->name ?? '-',
            $product->brand->name ?? '-',
            $product->supplier->name ?? '-',
            $product->purchase_price,
            $product->selling_price,
            $product->stock,
        ];
    }

    public function headings(): array
    {
        return ['Kode', 'Nama', 'Kategori', 'Satuan', 'Merk', 'Supplier', 'Harga Beli', 'Harga Jual', 'Stok'];
    }
}
