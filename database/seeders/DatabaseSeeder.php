<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Size;
use App\Models\Supplier; // (Opsional, jika Anda ingin menautkan supplier)
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }
        User::truncate();
        Product::truncate();

        User::create([
            'name' => 'AdminDev',
            'email' => 'admin@dev.com',
            'password' => bcrypt('password'),
        ]);

        $this->call(MasterDataSeeder::class);
        $this->call(ProductSeeder::class);
        // Product::create([
        //     'category_id' => $catBaju->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeM->id,
        //     'supplier_id' => $supplierA->id,
        //     'name' => 'Kaos Polos Hitam',
        //     'code' => 'KPH-M',
        //     'stock' => 100,
        //     'min_stock' => 10,
        //     'purchase_price' => 35000,
        //     'selling_price' => 55000,
        //     'description' => 'Kaos polos bahan katun 30s.',
        //     'status' => Product::STATUS_ACTIVE, // 'active'
        // ]);

        // Product::create([
        //     'category_id' => $catCelana->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeL->id,
        //     'supplier_id' => null, // Boleh null
        //     'name' => 'Jeans Biru Slim Fit',
        //     'code' => 'JNS-L',
        //     'stock' => 50,
        //     'min_stock' => 5,
        //     'purchase_price' => 120000,
        //     'selling_price' => 180000,
        //     'description' => 'Celana jeans slim fit warna biru.',
        //     'status' => Product::STATUS_ACTIVE, // 'active'
        // ]);

        // Product::create([
        //     'category_id' => $catElektronik->id,
        //     'unit_id' => $unitBox->id,
        //     'size_id' => $sizeAll->id, // Pakai 'All Size'
        //     'supplier_id' => null,
        //     'name' => 'Mouse Wireless V2',
        //     'code' => 'MOU-V2',
        //     'stock' => 75,
        //     'min_stock' => 20,
        //     'purchase_price' => 80000,
        //     'selling_price' => 125000,
        //     'description' => 'Mouse wireless 2.4GHz dengan silent click.',
        //     'status' => Product::STATUS_DRAFT, // 'draft'
        // ]);

        // Product::create([
        //     'category_id' => $catAksesoris->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Topi Baseball',
        //     'code' => 'TPI-01',
        //     'stock' => 150,
        //     'min_stock' => 15,
        //     'purchase_price' => 25000,
        //     'selling_price' => 45000,
        //     'description' => 'Topi baseball polos.',
        //     'status' => Product::STATUS_ACTIVE, // 'active'
        // ]);

        // Product::create([
        //     'category_id' => $catBaju->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeL->id,
        //     'supplier_id' => $supplierA->id,
        //     'name' => 'Kemeja Flanel Kotak',
        //     'code' => 'KFL-L',
        //     'stock' => 60,
        //     'min_stock' => 10,
        //     'purchase_price' => 85000,
        //     'selling_price' => 135000,
        //     'description' => 'Kemeja flanel lengan panjang.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catCelana->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeM->id,
        //     'supplier_id' => null,
        //     'name' => 'Celana Chino Cream',
        //     'code' => 'CHN-M',
        //     'stock' => 80,
        //     'min_stock' => 15,
        //     'purchase_price' => 90000,
        //     'selling_price' => 145000,
        //     'description' => 'Celana chino bahan katun twill.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catAksesoris->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Dompet Kulit Pria',
        //     'code' => 'DPT-01',
        //     'stock' => 120,
        //     'min_stock' => 20,
        //     'purchase_price' => 50000,
        //     'selling_price' => 95000,
        //     'description' => 'Dompet kulit sintetis premium.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catElektronik->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Headphone Bluetooth X1',
        //     'code' => 'HP-X1',
        //     'stock' => 45,
        //     'min_stock' => 10,
        //     'purchase_price' => 150000,
        //     'selling_price' => 225000,
        //     'description' => 'Headphone over-ear dengan noise cancelling.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catBaju->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeS->id,
        //     'supplier_id' => $supplierA->id,
        //     'name' => 'Kaos Polos Putih',
        //     'code' => 'KPH-S',
        //     'stock' => 110,
        //     'min_stock' => 10,
        //     'purchase_price' => 35000,
        //     'selling_price' => 55000,
        //     'description' => 'Kaos polos bahan katun 30s.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catAksesoris->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Ikat Pinggang Kanvas',
        //     'code' => 'IKP-01',
        //     'stock' => 200,
        //     'min_stock' => 25,
        //     'purchase_price' => 15000,
        //     'selling_price' => 30000,
        //     'description' => 'Ikat pinggang model tactical.',
        //     'status' => Product::STATUS_DRAFT,
        // ]);

        // Product::create([
        //     'category_id' => $catElektronik->id,
        //     'unit_id' => $unitBox->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Kabel USB-C 2M',
        //     'code' => 'KBL-C2',
        //     'stock' => 300,
        //     'min_stock' => 50,
        //     'purchase_price' => 20000,
        //     'selling_price' => 45000,
        //     'description' => 'Kabel data fast charging 2 meter.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catCelana->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeL->id,
        //     'supplier_id' => null,
        //     'name' => 'Celana Pendek Cargo',
        //     'code' => 'CRG-L',
        //     'stock' => 65,
        //     'min_stock' => 10,
        //     'purchase_price' => 70000,
        //     'selling_price' => 110000,
        //     'description' => 'Celana pendek kargo bahan ripstop.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catBaju->id,
        //     'unit_id' => $unitLusin->id,
        //     'size_id' => $sizeM->id,
        //     'supplier_id' => $supplierA->id,
        //     'name' => 'Kaos Kaki (Lusin)',
        //     'code' => 'KKL-M',
        //     'stock' => 40,
        //     'min_stock' => 5,
        //     'purchase_price' => 60000,
        //     'selling_price' => 90000,
        //     'description' => 'Kaos kaki putih (isi 12 pasang).',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);

        // Product::create([
        //     'category_id' => $catAksesoris->id,
        //     'unit_id' => $unitPcs->id,
        //     'size_id' => $sizeAll->id,
        //     'supplier_id' => null,
        //     'name' => 'Jam Tangan Digital',
        //     'code' => 'JTD-01',
        //     'stock' => 55,
        //     'min_stock' => 10,
        //     'purchase_price' => 90000,
        //     'selling_price' => 160000,
        //     'description' => 'Jam tangan digital anti air.',
        //     'status' => Product::STATUS_ACTIVE,
        // ]);
    }
}
