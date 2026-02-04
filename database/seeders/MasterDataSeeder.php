<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. --- LOGIKA CLEANUP AMAN (PENTING) ---
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Truncate (Bersihkan) Tabel Master Data
        Category::truncate();
        Unit::truncate();
        Size::truncate();
        Brand::truncate();
        ProductType::truncate();
        Supplier::truncate();
        // User::truncate(); // Opsional, jika mau reset user

        // 2. --- SEEDING DATA MASTER ---

        // --- Categories (Minimal 5) ---
        $catOil = Category::create([
            'name' => 'Oli',
            'code' => 'OIL',
            'description' => 'Oli Motor, Mobil, Diesel.',
            'slug' => Str::slug('Oli & Pelumas'), // <-- TAMBAHAN WAJIB
        ]);
        $catSpr = Category::create([
            'name' => 'Sparepart Motor',
            'code' => 'SPR',
            'description' => 'Busi, Filter, Minyak Rem.',
            'slug' => Str::slug('Sparepart Motor'), // <-- TAMBAHAN WAJIB
        ]);
        $catBat = Category::create([
            'name' => 'Ban',
            'code' => 'BAN',
            'description' => 'Ban Luar/Dalam motor.',
            'slug' => Str::slug('Ban'), // <-- TAMBAHAN WAJIB
        ]);
        // $catElc = Category::create([
        //     'name' => 'Elektronik Pasif',
        //     'code' => 'ELC',
        //     'description' => 'Lampu, Sakelar.',
        //     'slug' => Str::slug('Elektronik Pasif') // <-- TAMBAHAN WAJIB
        // ]);
        // $catAcc = Category::create([
        //     'name' => 'Aksesoris & Umum',
        //     'code' => 'ACC',
        //     'description' => 'Vanbel, Pembersih.',
        //     'slug' => Str::slug('Aksesoris & Umum') // <-- TAMBAHAN WAJIB
        // ]);

        // --- Units (Minimal 5) ---
        $units = [
            ['name' => 'Pcs', 'code' => 'PCS', 'description' => 'Satuan dasar per buah.', 'is_decimal' => false],
            ['name' => 'Botol', 'code' => 'BTL', 'description' => 'Kemasan botol kecil/retail.', 'is_decimal' => false],
            ['name' => 'Dus', 'code' => 'DUS', 'description' => 'Satuan kardus isi 12/24.', 'is_decimal' => false],
            ['name' => 'Liter', 'code' => 'LTR', 'description' => 'Satuan ukur cairan.', 'is_decimal' => true],
            ['name' => 'Set', 'code' => 'SET', 'description' => 'Satu set (misal: 1 set kampas rem).', 'is_decimal' => false],
        ];
        Unit::insert($units);

        // --- Sizes (Minimal 5) ---
        $sizes = [
            ['name' => 'All Size', 'code' => 'ALL', 'description' => 'Tidak spesifik ukuran.'],
            ['name' => '800 ML', 'code' => '0.8L', 'description' => 'Kemasan motor bebel/matic.'],
            ['name' => '700 ML', 'code' => '0.7L', 'description' => 'Kemasan oli 2T.'],
            ['name' => '650 ML', 'code' => '0.65L', 'description' => 'Kemasan motor matic honda new.'],
            ['name' => '1 Liter', 'code' => '1L', 'description' => 'Kemasan motor manual/mobil.'],
            ['name' => '4 Liter', 'code' => '4L', 'description' => 'Kemasan galon mobil.'],
            ['name' => '5 Liter', 'code' => '5L', 'description' => 'Kemasan galon mobil.'],
        ];
        Size::insert($sizes);

        // --- Brands (Minimal 5) ---
        $brands = [
            ['name' => 'AHM', 'code' => 'AHM', 'description' => 'Minyak dan pelumas dari honda.'],
            ['name' => 'Yamalube', 'code' => 'YMLB', 'description' => 'Minyak dan pelumas dari yamaha.'],
            ['name' => 'Shell', 'code' => 'SHELL', 'description' => 'Minyak dan pelumas utama.'],
            ['name' => 'Pertamina', 'code' => 'PRTMA', 'description' => 'Produk lokal/BUMN.'],
            ['name' => 'Motul', 'code' => 'MOTUL', 'description' => 'Pelumas premium.'],
            ['name' => 'Federal', 'code' => 'FDRL', 'description' => 'Ban dan pelumas populer.'],
            ['name' => 'GS Astra', 'code' => 'GSA', 'description' => 'Aki dan suku cadang.'],
            ['name' => 'IRC', 'code' => 'IRC', 'description' => 'Ban motor.'],
            ['name' => 'FP', 'code' => 'FP', 'description' => 'Ban motor.'],
        ];
        Brand::insert($brands);

        ProductType::create(['name' => 'Matic', 'code' => 'MTC', 'description' => 'Untuk transmisi otomatis', 'category_id' => $catOil->id]);
        ProductType::create(['name' => 'Manual 4T', 'code' => 'M4T', 'description' => 'Untuk motor manual', 'category_id' => $catOil->id]);
        ProductType::create(['name' => 'Diesel', 'code' => 'DSL', 'description' => 'Untuk mesin diesel/alat berat', 'category_id' => $catOil->id]);

        ProductType::create(['name' => 'Filter Oli', 'code' => 'FLO', 'description' => 'Filter oli mesin', 'category_id' => $catSpr->id]);
        ProductType::create(['name' => 'Busi', 'code' => 'BUS', 'description' => 'Busi standar/racing', 'category_id' => $catSpr->id]);

        ProductType::create(['name' => 'Tubeless', 'code' => 'TBL', 'description' => 'Ban tanpa ban dalam', 'category_id' => $catBat->id]);

        Supplier::create(['name' => 'Sahabat Motor', 'phone' => '085952732791', 'address' => 'Kertosono', 'status' => 'active', 'type' => Supplier::TYPE_OFFLINE, 'description' => 'Supplier utama.']);

        // --- 3. --- AKTIFKAN FOREIGN KEY ---
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
}
