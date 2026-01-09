<?php

namespace App\Imports;

use App\Models\Size;
use App\Models\Unit;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductType;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToArray, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    // Cache Memori (Key = Nama di Excel, Value = ID Database)
    // protected $stockService;
    protected $categories;
    protected $units;
    protected $sizes;
    protected $suppliers;
    protected $brands;
    protected $types;

    public function __construct()
    {
        // $this->stockService = app(StockService::class);;
        // Load semua data master ke RAM biar ngebut
        // Format: ['oli mesin' => 1, 'ban' => 2]
        // Kita lowercase semua biar tidak sensitif huruf besar/kecil
        $this->categories = Category::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $this->units      = Unit::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $this->sizes       = Size::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $this->suppliers  = Supplier::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $this->brands     = Brand::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $this->types     = ProductType::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
    }

    public function array(array $array)
    {
        return $array;
    }

    public function transform(array $row)
    {
        // 1. NORMALISASI INPUT (Trim spasi & Lowercase)
        $catName   = strtolower(trim($row['kategori']));
        $unitName  = strtolower(trim($row['satuan']));
        $suppName  = isset($row['supplier']) ? strtolower(trim($row['supplier'])) : null;
        $brandName = isset($row['merk']) ? strtolower(trim($row['merk'])) : null;
        $typeName  = isset($row['type_produk']) ? strtolower(trim($row['type_produk'])) : null;
        $sizeName  = isset($row['ukuran']) ? strtolower(trim($row['ukuran'])) : null;

        // 3. LOOKUP ID
        $categoryId = $this->categories[$catName] ?? null;
        $unitId     = $this->units[$unitName] ?? null;
        $supplierId = $suppName ? ($this->suppliers[$suppName] ?? null) : null;
        $brandId    = $brandName ? ($this->brands[$brandName] ?? null) : null;
        $typeId     = $typeName ? ($this->types[$typeName] ?? null) : null;
        $sizeId     = $sizeName ? ($this->sizes[$sizeName] ?? null) : null;

        $generatedCode = $this->generateProductCode(
            $categoryId,
            $typeId,
            $brandId,
            $sizeId
        );

        $buyPrice  = (float) $row['harga_beli'];
        $sellPrice = (float) $row['harga_jual'];

        if (isset($row['margin_target']) && $row['margin_target'] !== '' && $row['margin_target'] !== null) {
            // KONDISI A: User mengisi manual -> Pakai input user
            $margin = (float) $row['margin_target'];
        } else {
            // KONDISI B: Kosong -> Hitung Otomatis
            // Cegah error "Division by Zero" jika harga jual 0
            if ($sellPrice > 0) {
                // margin = ((harga_jual - harga_beli) / harga_jual) * 100
                // markup = ((harga_jual - harga_beli) / harga_beli) * 100
                $margin = (($sellPrice - $buyPrice) / $sellPrice) * 100;
            } else {
                $margin = 0;
            }
        }

        return [
            'code'           => $row['kode_produk'] ?? $generatedCode,
            'name'           => $row['nama_produk'],
            'description'    => $row['deskripsi'] ?? null,

            // Foreign Keys (ID)
            'category_id'    => $categoryId,
            'unit_id'        => $unitId,
            'supplier_id'    => $supplierId,
            'brand_id'       => $brandId,
            'product_type_id'   => $typeId,
            'size_id'           => $sizeId ?? null,

            'purchase_price' => $buyPrice,
            'selling_price'  => $sellPrice,
            'target_margin_percent' => round($margin, 2),
            'stock'          => $row['stok_awal'] ?? 0,
            'min_stock'      => $row['min_stok'] ?? 0,
            'status'         => Product::STATUS_ACTIVE
        ];
        // $this->stockService->record(
        //     productId: $product->id,
        //     qty: $product->stock,
        //     type: StockMovement::TYPE_INITIAL,
        //     ref: 'INIT',
        //     desc: 'Stok Awal Import Produk Baru'
        // );
        //     return $product;
        // } catch (\Exception $e) {
        //     // Ini akan mencatat error di file storage/logs/laravel.log
        //     Log::error('Gagal Import Baris: ' . json_encode($row));
        //     Log::error('Penyebab: ' . $e->getMessage());
        //     return null;
        // }
    }

    public function rules(): array
    {
        return [
            // Data Utama
            'kode_produk' => 'nullable|unique:products,code',
            'nama_produk' => 'required|string',
            // Angka-angka
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',

            // 1. KATEGORI (Wajib & Harus Ada di DB)
            'kategori' => function ($attribute, $value, $fail) {
                if (!isset($this->categories[strtolower(trim($value))])) {
                    $fail("Kategori '{$value}' tidak ditemukan di master data.");
                }
            },

            // 2. SATUAN (Wajib & Harus Ada di DB)
            'satuan' => function ($attribute, $value, $fail) {
                if (!isset($this->units[strtolower(trim($value))])) {
                    $fail("Satuan '{$value}' tidak ditemukan di master data.");
                }
            },

            // 3. SUPPLIER (Opsional tapi kalau diisi harus benar)
            'supplier' => function ($attribute, $value, $fail) {
                if ($value && !isset($this->suppliers[strtolower(trim($value))])) {
                    $fail("Supplier '{$value}' belum terdaftar.");
                }
            },

            // 4. MERK (Opsional tapi kalau diisi harus benar)
            'merk' => function ($attribute, $value, $fail) {
                if ($value && !isset($this->brands[strtolower(trim($value))])) {
                    $fail("Merk '{$value}' belum terdaftar.");
                }
            },

            // 5. Tipe Produk (Wajib, harus valid)
            'type_produk' => ['required', function ($attr, $val, $fail) {
                if (!isset($this->types[strtolower(trim($val))])) $fail("Tipe Produk '{$val}' tidak ada di database.");
            }],

            // 6. Ukuran (Opsional, harus valid)
            'ukuran' => ['nullable', function ($attr, $val, $fail) {
                // Bisa handle angka (10) atau teks (XL) dengan menjadikannya string dulu
                $valStr = (string) $val;
                if (!isset($this->sizes[strtolower(trim($valStr))])) $fail("Ukuran '{$val}' belum terdaftar.");
            }],
        ];
    }

    public function generateProductCode($categoryId, $typeId, $brandId, $sizeId)
    {
        // 1. Ambil Data Master (Pastikan data ini ada)
        // Gunakan optional() atau null check jika data import ada yang kosong
        $category = Category::find($categoryId);
        $type     = ProductType::find($typeId);
        $brand    = Brand::find($brandId);
        $size     = Size::find($sizeId);

        // Guard Clause: Jika master data ada yang tidak ketemu, return null atau error
        if (!$category || !$type || !$brand || !$size) {
            return 'ERR-CODE-MISSING';
        }

        // 2. Susun Prefix Dasar
        // Format: KAT-TIP-BRD-SIZ (Contoh: OLI-GRG-SHL-1L)
        // Kita gunakan strtoupper biar konsisten huruf besar
        $prefix = strtoupper("{$category->code}-{$type->code}-{$brand->code}-{$size->code}");

        // 3. Generate Random Suffix dengan Pengecekan Duplikat
        $limit = 0;
        do {
            // Opsi A: Angka Random 4 Digit (1000 - 9999) -> Contoh: OLI-GRG-SHL-1L-4829
            $randomSuffix = mt_rand(1000, 9999);

            // Opsi B: Alphanumeric Random 4 Karakter (A1B2) -> Lebih unik lagi
            // $randomSuffix = strtoupper(Str::random(4));

            $finalCode = $prefix . '-' . $randomSuffix;

            // Cek di database apakah kode ini sudah ada?
            $exists = Product::where('code', $finalCode)->exists();

            $limit++;
            // Safety break agar tidak infinite loop jika sistem error (sangat jarang terjadi)
            if ($limit > 100) break;
        } while ($exists);

        return $finalCode;
    }
}
