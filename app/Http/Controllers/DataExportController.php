<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

// Buat nanti jika perlu
// use App\Exports\SaleExport;

class DataExportController extends Controller
{
    public function download(Request $request)
    {
        $type = $request->type;
        $date = now()->format('Y-m-d');

        return match ($type) {
            'products' => Excel::download(new ProductExport, "Data_Produk_{$date}.xlsx"),
            // 'sales' => Excel::download(new SaleExport, "Laporan_Penjualan_{$date}.xlsx"),
            default => back()->withErrors('Tipe export belum didukung.'),
        };
    }
}
