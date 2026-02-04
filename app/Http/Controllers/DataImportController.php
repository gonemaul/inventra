<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Imports\CategoryImport;
use App\Imports\ProductImport;
use App\Jobs\ProcessStockJob;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Kita buat nanti
use Illuminate\Support\Facades\Validator; // Kita buat nanti
use Maatwebsite\Excel\Facades\Excel;  // Kita buat nanti

class DataImportController extends Controller
{
    // Mapping Tipe ke Class Import
    protected function getImportClass($type)
    {
        return match ($type) {
            'categories' => CategoryImport::class,
            'products' => ProductImport::class,
            default => null,
        };
    }

    // Mapping Header untuk Template
    protected function getTemplateHeaders($type)
    {
        return match ($type) {
            'categories' => ['Kode Kategori', 'Nama Kategori', 'Deskripsi'],
            'products' => ['Kode Produk', 'Nama Produk', 'Deskripsi', 'Kategori', 'Type Produk', 'Satuan', 'Ukuran', 'Supplier', 'Merk', 'Harga Beli', 'Harga Jual', 'Stok Awal', 'Min Stok'],
            default => [],
        };
    }

    // 1. DOWNLOAD TEMPLATE
    public function downloadTemplate(Request $request)
    {
        $type = $request->input('type');
        $headers = $this->getTemplateHeaders($type);

        if (empty($headers)) {
            return back()->withErrors('Tipe data tidak valid.');
        }

        // Download Excel kosong hanya dengan header
        return Excel::download(new TemplateExport($headers), "Template_Import_{$type}.xlsx");
    }

    // 2. PROSES IMPORT (Atomic / All-or-Nothing)
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction();
        $queueData = [];
        try {
            if ($request->type == 'products') {
                // Panggil Private Function Khusus Produk
                // Fungsi ini akan mengembalikan array data stok [id => qty]
                $queueData = $this->handleProductImport($request);
            } else {
                // Import Biasa (Kategori, Unit, dll) pakai cara lama
                $importClass = $this->getImportClass($request->type);
                if (! $importClass) {
                    throw new \Exception('Tipe import tidak valid.');
                }

                Excel::import(new $importClass, $request->file('file'));
            }

            DB::commit(); // Simpan jika sukses semua
            if ($request->type === 'products' && ! empty($queueData)) {
                foreach (array_chunk($queueData, 100, true) as $chunk) {
                    ProcessStockJob::dispatch($chunk);
                }
            }

            return back()->with('success', 'Data berhasil diimport 100%!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack(); // Batalkan semua jika ada validasi baris gagal

            // Format error biar enak dibaca user
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return back()->withErrors(['import_errors' => $messages]);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika ada error database/coding

            return back()->withErrors('Gagal Import: '.$e->getMessage());
        }
    }

    private function handleProductImport(Request $request)
    {
        // 1. Inisialisasi Class Import (Construct jalan disini untuk load relasi)
        $importer = new ProductImport;

        // 2. Baca Excel jadi Array
        $rows = Excel::toArray($importer, $request->file('file'))[0];

        $stockPayload = []; // Untuk Queue

        // 3. Looping Manual
        foreach ($rows as $index => $row) {
            // Skip baris kosong
            if (! array_filter($row)) {
                continue;
            }
            $rowNum = $index + 2; // Baris Excel (Header baris 1)

            // A. VALIDASI MANUAL (Panggil rules dari class import)
            $validator = Validator::make($row, $importer->rules());

            if ($validator->fails()) {
                // Lempar error biar ditangkap Catch di function store
                // Ini akan mentrigger Rollback
                throw new \Exception("Baris $rowNum: ".implode(', ', $validator->errors()->all()));
            }

            // B. KONVERSI DATA (Panggil function transform yg kita buat tadi)
            // Disini proses "Nama Kategori" -> "ID Kategori" terjadi
            $attributes = $importer->transform($row);

            // D. SIMPAN KE DB
            $available = Product::where('name', $attributes['name'])
                ->Where('category_id', $attributes['category_id'])
                ->Where('brand_id', $attributes['brand_id'])
                ->Where('supplier_id', $attributes['supplier_id'])->first();
            if ($available) {
                continue;
            } else {
                $product = Product::create($attributes);
            }

            // E. CATAT STOK AWAL (Untuk Queue)
            // if ($attributes['stock'] > 0) {
            $stockPayload[$product->id] = $attributes['stock'];
            // }
        }

        return $stockPayload; // Balikin ke Store untuk diproses Queue
    }
}
