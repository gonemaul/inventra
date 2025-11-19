<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Brand;
use App\Models\ProductType;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory(3500)->create();
    }
}
