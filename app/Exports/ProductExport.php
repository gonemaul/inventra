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
        return Product::with(['category', 'unit', 'supplier', 'brand', 'productType','saleItems', 'size'])->get();
    }

    public function map($product): array
    {
        return [
            $product->code,
            $product->name,
            $product->description ?? '',
            $product->category->name ?? '-',
            $product->productType->name ?? '-',
            $product->unit->name ?? '-',
            $product->size->name ?? '-',
            $product->supplier->name ?? '-',
            $product->brand->name ?? '-',
            $product->purchase_price,
            $product->selling_price,
            $product->stock,
            $product->min_stock ?? 0,
            $product->saleItems->sum('quantity')
        ];
    }

    public function headings(): array
    {
        // Must match DataImportController::getTemplateHeaders('products')
        return [
            'Kode Produk',
            'Nama Produk',
            'Deskripsi',
            'Kategori',
            'Type Produk',
            'Satuan',
            'Ukuran',
            'Supplier',
            'Merk',
            'Harga Beli',
            'Harga Jual',
            'Stok Awal',
            'Min Stok',
            'Terjual'
        ];
    }
}
