<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Supplier;
use App\Services\SalesRecapService;
use App\Services\StockService;
use App\Services\Analysis\Product\InventoryAnalyzer;
use App\Services\Analysis\Product\FinancialAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $salesService;
    protected $product;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            
            // Setup Dependencies
            $inventoryAnalyzer = new InventoryAnalyzer();
            $financialAnalyzer = new FinancialAnalyzer();
            $stockService = new StockService($inventoryAnalyzer, $financialAnalyzer);
            $this->salesService = new SalesRecapService($stockService, $inventoryAnalyzer);

            // Create Seed Data
            $unit = Unit::create(['name' => 'Pcs', 'code' => 'pcs']);
            // Check if slug is unique in migration? Usually yes.
            $category = \App\Models\Category::create(['name' => 'Test Cat', 'slug' => 'test-cat']);
            $brand = \App\Models\Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
            $type = \App\Models\ProductType::create([
                'name' => 'Test Type', 
                'slug' => 'test-type',
                'category_id' => $category->id
            ]);
            
            // Create Supplier (Fixed fields)
            $supplier = Supplier::create([
                'name' => 'Test Supplier',
                'phone' => '08123456789',
                'address' => 'Test Address',
                'status' => 'active',
                'type' => 'local'
            ]);
            
            $this->product = Product::create([
                'name' => 'Test Product',
                'slug' => 'test-product-slug', // Explicit Slug
                'code' => 'TST001',
                'base_price' => 10000,
                'purchase_price' => 10000,
                'selling_price' => 12000,
                'stock' => 100,
                'unit_id' => $unit->id,
                'category_id' => $category->id,
                'product_type_id' => $type->id,
                'brand_id' => $brand->id,
                'supplier_id' => $supplier->id,
                'description' => 'Test desc',
                'image_path' => 'test.jpg',
                'status' => 'active'
            ]);

            $this->actingAs(User::factory()->create());

        } catch (\Throwable $e) {
            dump('ERROR IN SETUP: ' . $e->getMessage());
            dump($e->getTraceAsString());
            throw $e;
        }
    }

    public function test_delete_sale_should_log_stock_return()
    {
        // 1. Create Sale (Stock reduced by 10)
        $saleData = [
            'report_date' => now(),
            'input_type' => Sale::TYPE_REKAP,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'selling_price' => 12000
                ]
            ]
        ];
        // Use try catch here too just in case
        try {
            $sale = $this->salesService->storeRecap($saleData);
        } catch (\Throwable $e) {
             dump('ERROR IN STORE RECAP: ' . $e->getMessage());
             throw $e;
        }

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 90]);
        
        // 2. Delete Sale (Stock should return to 100)
        $this->salesService->deleteRecap($sale);

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 100]);
        
        // Check Log
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => StockMovement::TYPE_RETURN_IN,
            'quantity' => 10
        ]);
    }

    public function test_update_sale_quantity_should_adjust_stock_correctly()
    {
        // 1. Create Sale (Qty: 10) -> Stock: 90
        $saleData = [
            'report_date' => now(),
            'input_type' => Sale::TYPE_REKAP,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'selling_price' => 12000
                ]
            ]
        ];
        $sale = $this->salesService->storeRecap($saleData);
        
        // 2. Update Sale (Change Qty to 15) -> Stock should be 85
        $updateData = [
            'report_date' => now(),
            'input_type' => Sale::TYPE_REKAP,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15, 
                    'selling_price' => 12000
                ]
            ]
        ];
        
        $this->salesService->updateRecap($sale, $updateData);

        $this->assertDatabaseHas('products', ['id' => $this->product->id, 'stock' => 85]);

        // Verify LOGS exist for both return and new sale
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => StockMovement::TYPE_RETURN_IN,
            'quantity' => 10 // The refund
        ]);
        
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity' => -15 
        ]); 
    }
}
