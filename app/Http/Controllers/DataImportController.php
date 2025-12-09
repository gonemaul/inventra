<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TemplateExport; // Kita buat nanti
use App\Imports\CategoryImport; // Kita buat nanti
use App\Imports\ProductImport;  // Kita buat nanti

class DataImportController extends Controller
{
    // Mapping Tipe ke Class Import
    protected function getImportClass($type)
    {
        return match ($type) {
            'categories' => CategoryImport::class,
            'products'   => ProductImport::class,
            // 'suppliers' => SupplierImport::class,
            default => null,
        };
    }

    // Mapping Header untuk Template
    protected function getTemplateHeaders($type)
    {
        return match ($type) {
            'categories' => ['Kode Kategori', 'Nama Kategori', "Deskripsi"],
            'products'   => ['Kode Produk', 'Nama Produk', 'Deskripsi', 'Kategori', 'Type Produk', 'Satuan', 'Ukuran', 'Supplier', 'Merk', 'Harga Beli', 'Harga Jual', 'Stok Awal', 'Min Stok'],
            default => [],
        };
    }

    // 1. DOWNLOAD TEMPLATE
    public function downloadTemplate(Request $request)
    {
        $type = $request->input('type');
        $headers = $this->getTemplateHeaders($type);

        if (empty($headers)) return back()->withErrors('Tipe data tidak valid.');

        // Download Excel kosong hanya dengan header
        return Excel::download(new TemplateExport($headers), "Template_Import_{$type}.xlsx");
    }

    // 2. PROSES IMPORT (Atomic / All-or-Nothing)
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $importClass = $this->getImportClass($request->type);
        if (!$importClass) return back()->withErrors('Tipe import belum didukung.');

        DB::beginTransaction();
        try {
            Excel::import(new $importClass, $request->file('file'));

            DB::commit(); // Simpan jika sukses semua
            return back()->with('success', 'Data berhasil diimport 100%!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack(); // Batalkan semua jika ada validasi baris gagal

            // Format error biar enak dibaca user
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }

            return back()->withErrors(['import_errors' => $messages]);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika ada error database/coding
            return back()->withErrors('Gagal Import: ' . $e->getMessage());
        }
    }
}
