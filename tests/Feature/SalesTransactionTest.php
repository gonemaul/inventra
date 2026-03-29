<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $category = Category::create([
            'name' => 'Sparepart',
            'slug' => 'sparepart',
            'code' => 'CAT-SP',
        ]);
        $unit = Unit::create([
            'name' => 'Pcs',
            'code' => 'PCS',
            'is_decimal' => false,
        ]);
        $brand = Brand::create([
            'name' => 'Honda',
            'code' => 'HND',
        ]);
        $supplier = \App\Models\Supplier::create([
            'name' => 'PT Test Supplier',
            'phone' => '081234567',
            'address' => 'Jl. Test',
            'status' => 'active',
            'type' => 'general',
        ]);

        $this->product = Product::create([
            'code' => 'SP-001',
            'name' => 'Kampas Rem',
            'slug' => 'kampas-rem',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'brand_id' => $brand->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 100,
            'status' => 'active',
        ]);
    }

    /**
     * Helper: Payload minimal yang valid untuk endpoint store.
     */
    private function makePayload(array $overrides = []): array
    {
        return array_merge([
            'input_type' => 'realtime',
            'type' => 'retail',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'selling_price' => 25000,
                ],
            ],
        ], $overrides);
    }

    // ─── FORMAT INVOICE ───────────────────────────────────────

    /** @test */
    public function it_generates_retail_invoice_with_correct_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload(['type' => 'retail']));

        $sale = Sale::latest('id')->first();
        $dateCode = now()->format('ymd');

        $this->assertMatchesRegularExpression(
            "/^POS\/{$dateCode}\/\d{3}$/",
            $sale->reference_no,
            "Format retail harus: POS/YYMMDD/XXX"
        );
        $this->assertEquals('retail', $sale->type);
    }

    /** @test */
    public function it_generates_bengkel_invoice_with_correct_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload(['type' => 'bengkel']));

        $sale = Sale::latest('id')->first();
        $dateCode = now()->format('ymd');

        $this->assertMatchesRegularExpression(
            "/^POS\/B\/{$dateCode}\/\d{3}$/",
            $sale->reference_no,
            "Format bengkel harus: POS/B/YYMMDD/XXX"
        );
        $this->assertEquals('bengkel', $sale->type);
    }

    /** @test */
    public function it_generates_rekap_invoice_with_correct_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'input_type' => 'recap',
                'type' => 'retail',
                'transaction_date' => now()->format('Y-m-d'),
            ]));

        $sale = Sale::latest('id')->first();
        $dateCode = now()->format('ymd');

        $this->assertMatchesRegularExpression(
            "/^REKAP\/{$dateCode}\/\d{3}$/",
            $sale->reference_no,
            "Format rekap harus: REKAP/YYMMDD/XXX"
        );
    }

    // ─── UNIFIED COUNTER ──────────────────────────────────────

    /** @test */
    public function it_uses_unified_sequential_counter_across_all_types(): void
    {
        // Buat 3 transaksi berbeda tipe di hari yang sama
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload(['type' => 'retail']));

        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload(['type' => 'bengkel']));

        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'input_type' => 'recap',
                'type' => 'retail',
                'transaction_date' => now()->format('Y-m-d'),
            ]));

        $sales = Sale::orderBy('id')->get();
        $dateCode = now()->format('ymd');

        // Nomor berurutan: 001, 002, 003 meskipun tipe berbeda
        $this->assertEquals("POS/{$dateCode}/001", $sales[0]->reference_no);
        $this->assertEquals("POS/B/{$dateCode}/002", $sales[1]->reference_no);
        $this->assertEquals("REKAP/{$dateCode}/003", $sales[2]->reference_no);
    }

    // ─── DATE BUG FIX ─────────────────────────────────────────

    /** @test */
    public function it_ignores_frontend_date_for_realtime_transactions(): void
    {
        // Simulasi: Frontend kirim tanggal kemarin (tab idle), tapi server harus pakai now()
        $yesterday = now()->subDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'input_type' => 'realtime',
                'transaction_date' => $yesterday, // Frontend kirim tanggal basi
            ]));

        $sale = Sale::latest('id')->first();

        // Server HARUS pakai tanggal hari ini, bukan kemarin
        $this->assertEquals(now()->format('Y-m-d'), $sale->transaction_date->format('Y-m-d'));
    }

    /** @test */
    public function it_rejects_created_at_from_frontend(): void
    {
        // created_at tidak boleh ada di validated data (sudah dihapus dari rules)
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'created_at' => '2025-01-01 00:00:00',
            ]));

        $sale = Sale::latest('id')->first();

        // created_at harus tanggal hari ini (auto-filled oleh Eloquent), bukan yang dikirim frontend
        $this->assertEquals(now()->format('Y-m-d'), $sale->created_at->format('Y-m-d'));
    }

    // ─── VALIDASI ─────────────────────────────────────────────

    /** @test */
    public function it_rejects_invalid_sale_type(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'type' => 'invalid_type',
            ]));

        $response->assertSessionHasErrors('type');
    }

    /** @test */
    public function it_defaults_to_retail_when_type_not_provided(): void
    {
        $payload = $this->makePayload();
        unset($payload['type']); // Frontend lama mungkin tidak kirim type

        $this->actingAs($this->user)
            ->post(route('sales.store'), $payload);

        $sale = Sale::latest('id')->first();
        $this->assertEquals('retail', $sale->type);
    }

    // ─── FASE 3: UPDATE TRANSACTION ───────────────────────────

    /** @test */
    public function it_reverts_stock_and_redecuts_on_update(): void
    {
        // Arrange: Buat transaksi awal (qty=3)
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'items' => [['product_id' => $this->product->id, 'quantity' => 3, 'selling_price' => 25000]],
            ]));

        $sale = Sale::latest('id')->first();
        $this->product->refresh();
        $this->assertEquals(97, $this->product->stock); // 100 - 3

        // Act: Update transaksi (ubah qty menjadi 5)
        $this->actingAs($this->user)
            ->put(route('sales.update', $sale->id), [
                'input_type' => 'realtime',
                'type' => 'retail',
                'payment_method' => 'cash',
                'items' => [['product_id' => $this->product->id, 'quantity' => 5, 'selling_price' => 25000]],
            ]);

        // Assert: Stok = 100 - 5 = 95 (bukan 97 - 5 = 92!)
        // Karena stok lama (3) dikembalikan dulu, baru dipotong ulang (5)
        $this->product->refresh();
        $this->assertEquals(95, $this->product->stock);

        // Assert: Total revenue di-recalculate
        $sale->refresh();
        $this->assertEquals(125000, $sale->total_revenue); // 5 * 25000
    }

    /** @test */
    public function it_preserves_reference_no_on_update(): void
    {
        // Buat transaksi
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload());

        $sale = Sale::latest('id')->first();
        $originalRef = $sale->reference_no;

        // Update transaksi
        $this->actingAs($this->user)
            ->put(route('sales.update', $sale->id), [
                'input_type' => 'realtime',
                'type' => 'retail',
                'payment_method' => 'cash',
                'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'selling_price' => 25000]],
            ]);

        // Reference_no TIDAK boleh berubah saat edit
        $sale->refresh();
        $this->assertEquals($originalRef, $sale->reference_no);
    }

    /** @test */
    public function it_prevents_editing_voided_transactions(): void
    {
        // Buat transaksi lalu soft-delete (void)
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload());

        $sale = Sale::latest('id')->first();
        $sale->delete(); // Soft delete

        // Coba update transaksi yang sudah void — 404 karena route model binding
        // tidak resolve trashed models secara default
        $response = $this->actingAs($this->user)
            ->put(route('sales.update', $sale->id), [
                'input_type' => 'realtime',
                'type' => 'retail',
                'payment_method' => 'cash',
                'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'selling_price' => 25000]],
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_recalculates_discount_on_update(): void
    {
        // Buat transaksi dengan diskon 10%
        $this->actingAs($this->user)
            ->post(route('sales.store'), $this->makePayload([
                'discount_type' => 'percent',
                'discount_value' => 10,
                'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'selling_price' => 25000]],
            ]));

        $sale = Sale::latest('id')->first();
        $this->assertEquals(45000, $sale->total_revenue); // 50000 - 10% = 45000

        // Update: ubah qty 4, diskon fixed 5000
        $this->actingAs($this->user)
            ->put(route('sales.update', $sale->id), [
                'input_type' => 'realtime',
                'type' => 'retail',
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_value' => 5000,
                'items' => [['product_id' => $this->product->id, 'quantity' => 4, 'selling_price' => 25000]],
            ]);

        $sale->refresh();
        $this->assertEquals(95000, $sale->total_revenue); // 100000 - 5000
    }
}

