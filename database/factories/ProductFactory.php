<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Brand;
use App\Models\ProductType;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;
    protected function withFaker()
    {
        // Menginstruksikan Faker untuk menggunakan locale Indonesia
        return \Faker\Factory::create('id_ID');
    }
    public function definition(): array
    {
        // 1. Ambil ID Acak dari Master Data
        $categoryIds = Category::pluck('id')->toArray();
        $unitIds = Unit::pluck('id')->toArray();
        $sizeIds = Size::pluck('id')->toArray();
        $supplierIds = Supplier::pluck('id')->toArray();
        $brandIds = Brand::pluck('id')->toArray();
        $productTypeIds = ProductType::pluck('id')->toArray();

        // 2. Tentukan Harga & Stok
        $purchasePrice = $this->faker->numberBetween(10000, 250000);
        $sellingPrice = ceil($purchasePrice * $this->faker->randomFloat(2, 1.2, 1.6) / 500) * 500; // Harga Jual (20-60% margin, dibulatkan ke 500 terdekat)
        $stock = $this->faker->numberBetween(5, 200);

        // 3. Status
        $statuses = ['active', 'draft'];
        $name = $this->faker->sentence(4, true);
        return [
            // Relasi (Ambil ID Acak)
            'category_id' => $this->faker->randomElement($categoryIds),
            'unit_id' => $this->faker->randomElement($unitIds),
            'size_id' => $this->faker->randomElement($sizeIds),
            'supplier_id' => $this->faker->randomElement($supplierIds),
            'brand_id' => $this->faker->randomElement($brandIds),
            'product_type_id' => $this->faker->randomElement($productTypeIds),

            // Atribut Produk
            'name' => $name,
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'description' => $this->faker->paragraph(1),

            // Nilai
            'stock' => $stock,
            'min_stock' => $this->faker->numberBetween(5, 15),
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'image_path' => null,
            'status' => $this->faker->randomElement($statuses),
            // Wajib ada untuk kolom NOT NULL
        ];
    }
}
