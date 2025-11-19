<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Size;
use App\Models\Brand;
use App\Models\ProductType;
use App\Models\Supplier;
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
            'name' => 'Oli & Pelumas',
            'code' => 'OIL',
            'description' => 'Oli Motor, Mobil, Diesel.',
            'slug' => Str::slug('Oli & Pelumas') // <-- TAMBAHAN WAJIB
        ]);
        $catSpr = Category::create([
            'name' => 'Sparepart Motor',
            'code' => 'SPR',
            'description' => 'Busi, Filter, Minyak Rem.',
            'slug' => Str::slug('Sparepart Motor') // <-- TAMBAHAN WAJIB
        ]);
        $catBat = Category::create([
            'name' => 'Aki & Ban',
            'code' => 'BAT',
            'description' => 'Aki, Ban Luar/Dalam.',
            'slug' => Str::slug('Aki & Ban') // <-- TAMBAHAN WAJIB
        ]);
        $catElc = Category::create([
            'name' => 'Elektronik Pasif',
            'code' => 'ELC',
            'description' => 'Lampu, Sakelar.',
            'slug' => Str::slug('Elektronik Pasif') // <-- TAMBAHAN WAJIB
        ]);
        $catAcc = Category::create([
            'name' => 'Aksesoris & Umum',
            'code' => 'ACC',
            'description' => 'Vanbel, Pembersih.',
            'slug' => Str::slug('Aksesoris & Umum') // <-- TAMBAHAN WAJIB
        ]);

        // --- Units (Minimal 5) ---
        $units = [
            ['name' => 'Pcs', 'code' => 'PCS', 'description' => 'Satuan dasar per buah.'],
            ['name' => 'Botol', 'code' => 'BTL', 'description' => 'Kemasan botol kecil/retail.'],
            ['name' => 'Dus', 'code' => 'DUS', 'description' => 'Satuan kardus isi 12/24.'],
            ['name' => 'Liter', 'code' => 'LTR', 'description' => 'Satuan ukur cairan.'],
            ['name' => 'Set', 'code' => 'SET', 'description' => 'Satu set (misal: 1 set kampas rem).'],
        ];
        Unit::insert($units);

        // --- Sizes (Minimal 5) ---
        $sizes = [
            ['name' => 'All Size', 'code' => 'ALL', 'description' => 'Tidak spesifik ukuran.'],
            ['name' => '0.8 Liter', 'code' => '0.8L', 'description' => 'Kemasan motor matic.'],
            ['name' => '1 Liter', 'code' => '1L', 'description' => 'Kemasan motor manual/mobil.'],
            ['name' => '4 Liter', 'code' => '4L', 'description' => 'Kemasan galon mobil.'],
            ['name' => 'S (Small)', 'code' => 'S', 'description' => 'Ukuran small untuk sparepart.'],
            ['name' => 'M (Medium)', 'code' => 'M', 'description' => 'Ukuran medium untuk sparepart.'],
        ];
        Size::insert($sizes);

        // --- Brands (Minimal 5) ---
        $brands = [
            ['name' => 'Shell', 'code' => 'SHELL', 'description' => 'Minyak dan pelumas utama.'],
            ['name' => 'Pertamina', 'code' => 'PRTMA', 'description' => 'Produk lokal/BUMN.'],
            ['name' => 'Motul', 'code' => 'MOTUL', 'description' => 'Pelumas premium.'],
            ['name' => 'Federal', 'code' => 'FDRL', 'description' => 'Ban dan pelumas populer.'],
            ['name' => 'GS Astra', 'code' => 'GSA', 'description' => 'Aki dan suku cadang.'],
            ['name' => 'IRC', 'code' => 'IRC', 'description' => 'Ban motor.'],
        ];
        Brand::insert($brands);

        ProductType::create(['name' => 'Matic', 'code' => 'MTC', 'description' => 'Untuk transmisi otomatis', 'category_id' => $catOil->id]);
        ProductType::create(['name' => 'Manual 4T', 'code' => 'M4T', 'description' => 'Untuk motor manual', 'category_id' => $catOil->id]);
        ProductType::create(['name' => 'Diesel', 'code' => 'DSL', 'description' => 'Untuk mesin diesel/alat berat', 'category_id' => $catOil->id]);

        ProductType::create(['name' => 'Filter Oli', 'code' => 'FLO', 'description' => 'Filter oli mesin', 'category_id' => $catSpr->id]);
        ProductType::create(['name' => 'Busi', 'code' => 'BUS', 'description' => 'Busi standar/racing', 'category_id' => $catSpr->id]);

        ProductType::create(['name' => 'Tubeless', 'code' => 'TBL', 'description' => 'Ban tanpa ban dalam', 'category_id' => $catBat->id]);

        Supplier::create(['name' => 'Bintang Lima Motor', 'phone' => '081234567890', 'address' => 'Jakarta', 'status' => 'active', 'type' => Supplier::TYPE_OFFLINE, 'description' => 'Supplier utama fast-moving oil.']);
        Supplier::create(['name' => 'Indo Sentosa Parts', 'phone' => '085612345678', 'address' => 'Bandung', 'status' => 'active', 'type' => Supplier::TYPE_ONLINE, 'description' => 'Sparepart & aksesoris.']);
        Supplier::create(['name' => 'Grosir Aki Jaya', 'phone' => '02198765432', 'address' => 'Tangerang', 'status' => 'inactive', 'type' => Supplier::TYPE_OFFLINE, 'description' => 'Khusus aki dan ban.']);

        // --- 3. --- AKTIFKAN FOREIGN KEY ---
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }
}
