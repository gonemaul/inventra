<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\SaleItem;
use App\Models\Size;
use App\Models\SmartInsight;
use App\Models\StockMovement;
use App\Services\Analysis\ProductDSSCalculator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductService
{
    protected $stockService;

    protected $imageService;

    protected $calculator;

    public function __construct(StockService $stockService, ImageService $imageService, ProductDSSCalculator $calculator)
    {
        $this->stockService = $stockService;
        $this->imageService = $imageService;
        $this->calculator = $calculator;
    }

    private function handleImageUpload($file, $existingPath = null)
    {
        $newPath = $this->imageService->upload(
            $file,
            'products',
            $existingPath
        );

        return $newPath;
    }

    /**
     * Mengambil data produk untuk datatable (server-side).
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get(array $params)
    {
        // Selalu ambil relasi (eager load) untuk efisiensi
        $query = Product::with(['category:id,name', 'unit:id,name', 'size:id,name', 'supplier:id,name', 'brand:id,name,code', 'productType:id,name', 'insights' => function ($q) {
            // Ambil insight yang masih aktif (belum dibaca/diselesaikan)
            $q->where('is_read', false);
        }])->withSum('saleItems as total_sold_all_time', 'quantity');

        // 1. SEARCH GLOBAL
        $query->when($paramSearch ?? null, function ($q, $search) {
            $keyword = Str::lower($search) ?? null;
            $q->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        });

        // 2. FILTER RELASI (Foreign Keys)
        $query->when($params['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v));
        $query->when($params['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v));
        $query->when($params['unit_id'] ?? null, fn ($q, $v) => $q->where('unit_id', $v));
        $query->when($params['size_id'] ?? null, fn ($q, $v) => $q->where('size_id', $v));
        $query->when($params['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v));
        $query->when($params['product_type_id'] ?? null, fn ($q, $v) => $q->where('product_type_id', $v));

        // 3. FILTER STATUS & TRASH
        $query->when($params['status'] ?? null, fn ($q, $v) => $q->where('status', $v));

        $query->when($params['trashed'] ?? null, function ($q, $trashed) {
            if ($trashed === 'with') {
                $q->withTrashed();
            }
            if ($trashed === 'only') {
                $q->onlyTrashed();
            }
        });

        // 4. FILTER RANGE (Min - Max)
        // Harga Jual
        $query->when($params['price_min'] ?? null, fn ($q, $v) => $q->where('selling_price', '>=', $v));
        $query->when($params['price_max'] ?? null, fn ($q, $v) => $q->where('selling_price', '<=', $v));
        // Harga Beli
        $query->when($params['cost_min'] ?? null, fn ($q, $v) => $q->where('purchase_price', '>=', $v));
        $query->when($params['cost_max'] ?? null, fn ($q, $v) => $q->where('purchase_price', '<=', $v));
        // Stok
        $query->when($params['stock_min'] ?? null, fn ($q, $v) => $q->where('stock', '>=', $v));
        $query->when($params['stock_max'] ?? null, fn ($q, $v) => $q->where('stock', '<=', $v));

        // 5. SORTING
        $sortField = $params['sort'] ?? 'created_at';
        $sortDirection = $params['order'] ?? 'desc';

        $allowedSorts = ['name', 'selling_price', 'purchase_price', 'stock', 'created_at', 'code', 'total_sold_all_time'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $params['per_page'] ?? 10;
        // Helper finansial Analis Pertumbuhan
        // 1. Tentukan Range Tanggal yang PRESISI (00:00:00 s/d 23:59:59)
        // Periode A: 30 Hari Terakhir (H-29 s/d Hari Ini = 30 Hari)
        $today = now()->endOfDay();
        $startThisMonth = now()->subDays(29)->startOfDay();
        // Periode B: 30 Hari Sebelumnya (H-59 s/d H-30 = 30 Hari)
        $endLastMonth = now()->subDays(30)->endOfDay();
        $startLastMonth = now()->subDays(59)->startOfDay();
        // --- A. Hitung Penjualan Bulan Ini (30 Hari Terakhir) ---
        // Hasilnya akan masuk ke atribut: 'qty_this_month'
        $query->withSum(['saleItems as qty_this_month' => function ($query) use ($startThisMonth, $today) {
            $query->whereHas('sale', function ($q) use ($startThisMonth, $today) {
                $q->whereBetween('transaction_date', [$startThisMonth, $today]);
            });
        }], 'quantity')

            // --- B. Hitung Penjualan Bulan Lalu (30 Hari Sebelumnya) ---
            // Hasilnya akan masuk ke atribut: 'qty_last_month'
            ->withSum(['saleItems as qty_last_month' => function ($query) use ($startLastMonth, $endLastMonth) {
                $query->whereHas('sale', function ($q) use ($startLastMonth, $endLastMonth) {
                    $q->whereBetween('transaction_date', [$startLastMonth, $endLastMonth]);
                });
            }], 'quantity'); // 1. Total Selamanya

        $products = $query->paginate($perPage)
            ->withQueryString();
        $products->getCollection()->transform(function ($product) {
            $product->financials = $this->calculator->calculateFinancialHealth($product);

            return $product;
        });

        return $products;
    }

    /**
     * Mendapatkan jumlah total produk yang aktif (tidak di-sampah).
     *
     * @return int
     */
    public function getCount()
    {
        // Hanya hitung produk yang tidak di-soft-delete
        return Product::count();
    }

    /**
     * MENGAMBIL SEMUA DATA DETAIL PRODUK + DSS (Untuk Halaman Show)
     */
    public function getProductDetails(Product $product)
    {
        $inventory = $this->calculator->calculateInventoryHealth($product);
        $financials = $this->calculator->calculateFinancialHealth($product);
        // 3. Ambil Insight DSS (Hasil Analisa)
        $insights = SmartInsight::where('product_id', $product->id)->get();

        // Struktur data DSS untuk Frontend
        $dssData = [
            'restock' => $insights->where('type', 'restock')->first(),
            'dead_stock' => $insights->where('type', 'dead_stock')->first(),
            'trend' => $insights->where('type', 'trend')->first(),
            'margin_alert' => $insights->where('type', 'margin_alert')->first(),
            'is_trending' => $insights->where('type', 'trend')->count() > 0,
            'is_dead_stock' => $insights->where('type', 'dead_stock')->count() > 0,
            'is_margin_low' => $insights->where('type', 'margin_alert')->count() > 0,
        ];

        // 4. Statistik Penjualan (Untuk Tab Ringkasan)
        $salesStats = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->where('transaction_date', '>=', now()->subDays(30)))
            ->sum('quantity');

        $dssData['sales_30_days'] = $salesStats;
        $dssData['avg_daily'] = $salesStats > 0 ? round($salesStats / 30, 1) : 0;

        // 5. Data Grafik (7 Hari Terakhir)
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $rawData = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->whereBetween('transaction_date', [$startDate, $endDate]))
            ->with('sale:id,transaction_date') // Eager load tanggalnya
            ->get();
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateCheck = now()->subDays($i)->format('Y-m-d');
            $displayDate = now()->subDays($i)->format('d/m'); // Format untuk Chart (16/12)

            // Filter collection yang sudah ditarik di atas
            $qty = $rawData->filter(function ($item) use ($dateCheck) {
                // Pastikan sale tidak null dan tanggalnya cocok
                return $item->sale && $item->sale->transaction_date->format('Y-m-d') === $dateCheck;
            })->sum('quantity');

            $chartData[] = ['day' => $displayDate, 'qty' => $qty];
        }

        // 6. Riwayat Stok (Gabungan Beli & Jual Terakhir)
        $stockHistory = $this->getStockHistory($product->id);
        // 7. Tren Harga (Logic Snapshot)
        $product->financials = $financials;
        $product->inventory = $inventory;

        return [
            'product' => $product,
            'dss' => $dssData,
            'chart_data' => $chartData,
            'stock_history' => $stockHistory,
            'price_trend' => $financials['price_trend'],
        ];
    }

    private function getStockHistory($id)
    {
        $movements = StockMovement::where('product_id', $id)
            ->orderBy('created_at', 'asc') // Urut kronologis (Baru -> Lama)
            ->limit(10)
            ->get();

        return $movements;
    }

    /**
     * Validasi dan simpan produk baru.
     *
     * @return Product
     *
     * @throws ValidationException
     */
    public function create(array $data)
    {
        $imageFile = $data['image'] ?? null;
        unset($data['image']);
        // Validasi berdasarkan migrasi final Anda
        $validator = Validator::make($data, [
            // Relasi
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')], // Sesuai migrasi (nullable)
            'size_id' => ['nullable', 'integer', Rule::exists('sizes', 'id')], // Sesuah migrasi (nullable)
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')], // Sesuai migrasi (nullable)
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')], // Sesuai migrasi (nullable)
            'product_type_id' => ['nullable', 'integer', Rule::exists('product_types', 'id')], // Sesuai migrasi (nullable)

            // Detail Produk
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:products,code', // 'code' dari migrasi Anda
            'description' => 'required|string',
            // 'image_path' => 'nullable|string|max:255', // 'image_path' dari migrasi Anda
            'status' => ['required', Rule::in(Product::STATUSES)], // Validasi string 'active'/'draft'

            // Harga & Stok
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0', // 'min_stock' dari migrasi Anda
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'target_margin_percent' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $generatedCode = $this->generateProductCode(
            $data['category_id'],
            $data['product_type_id'],
            $data['brand_id'],
            $data['size_id']
        );

        return DB::transaction(function () use ($validator, $imageFile, $generatedCode) {
            $validatedData = $validator->validated();
            if (! $validatedData['code']) {
                $validatedData['code'] = $generatedCode;
            }
            if ($imageFile) {
                $imageValidator = Validator::make(['image' => $imageFile], [
                    'image' => 'nullable|image|mimes:jpeg,png,webp|max:20480', // Maks 1MB
                ]);
                if ($imageValidator->fails()) {
                    throw new ValidationException($imageValidator);
                }

                // Simpan path ke data yang divalidasi
                $validatedData['image_path'] = $this->handleImageUpload($imageFile);
            }
            $product = Product::create($validatedData);
            // if ($validatedData['stock'] > 0) {
            $this->stockService->record(
                productId: $product->id,
                qty: $product->stock,
                type: StockMovement::TYPE_INITIAL,
                ref: 'INIT',
                desc: 'Stok Awal Produk Baru'
            );

            // }
            return true;
        });
    }

    /**
     * Validasi dan perbarui produk yang ada.
     *
     * @param  int|string  $id
     * @return Product
     *
     * @throws ValidationException|ModelNotFoundException
     */
    public function update($product, array $data)
    {
        $imageFile = $data['image'] ?? null;
        unset($data['image']);

        if ($data['type'] == 'stock') {
            if ($data['adjustment'] == 'add') {
                $this->stockService->record(
                    productId: $product->id,
                    qty: $data['qty'],
                    type: StockMovement::TYPE_ADJUSTMENT_IN,
                    desc: $data['note']
                );
            } elseif ($data['adjustment'] == 'reduce') {
                $this->stockService->record(
                    productId: $product->id,
                    qty: $data['qty'],
                    type: StockMovement::TYPE_ADJUSTMENT_OUT,
                    desc: $data['note']
                );
            } elseif ($data['adjustment'] == 'set') {
                $this->stockService->record(
                    productId: $product->id,
                    qty: $data['qty'],
                    type: StockMovement::TYPE_ADJUSTMENT_OPNAME,
                    desc: $data['note']
                );
            }
        } elseif ($data['type'] === 'price') {
            $product->updateWithSnapshot([
                'purchase_price' => $data['purchase_price'],
                'selling_price' => $data['selling_price'],
            ]);
            if ($product->is_margin_low) {
                $this->calculator->sendMarginAlert($product);
            }
        } else {
            // dd($data);
            if ($imageFile) {
                // Kirim path lama untuk dihapus
                $data['image_path'] = $this->handleImageUpload($imageFile, $product->image_path);
            }
            $product->update($data);
        }

        return $product;
    }

    /**
     * Hapus produk (Soft Delete atau Force Delete).
     *
     * @param  int|string  $id
     * @return bool
     *
     * @throws ModelNotFoundException
     */
    public function delete($id, array $params = [])
    {
        // 'withTrashed' penting untuk 'forceDelete'
        $product = Product::withTrashed()->findOrFail($id);

        if (isset($params['permanen']) && $params['permanen']) {
            return $product->forceDelete();
        } else {
            return $product->delete();
        }
    }

    /**
     * Pulihkan produk dari soft delete.
     *
     * @param  int|string  $id
     * @return bool
     *
     * @throws ModelNotFoundException
     */
    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        return $product->restore();
    }

    private function generateInitials(string $name, int $length = 3): string
    {
        // 1. Bersihkan string (Hapus simbol aneh)
        $clean = preg_replace('/[^A-Za-z0-9 ]/', '', $name);

        // 2. Pecah per kata
        $words = explode(' ', $clean);

        $initials = '';

        // Jika lebih dari 1 kata, ambil huruf depan setiap kata (Maksimal 3)
        // Contoh: "Tiga Roda" -> "TR", "Cat Minyak Kayu" -> "CMK"
        if (count($words) > 1) {
            foreach ($words as $word) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        } else {
            // Jika 1 kata, ambil 3 huruf pertama (Consonants preferred logic bisa ditambahkan jika mau rumit)
            // Contoh: "Semen" -> "SEM"
            $initials = strtoupper(substr($clean, 0, $length));
        }

        // Pastikan panjangnya pas (pad atau potong)
        return str_pad(substr($initials, 0, $length), $length, 'X');
    }

    public function generateProductCode($categoryId, $typeId, $brandId, $sizeId)
    {
        // 1. Ambil Data Master
        $category = Category::find($categoryId);
        $type = ProductType::find($typeId);
        $brand = Brand::find($brandId);
        $size = Size::find($sizeId);

        // 3. Susun Prefix
        // Format: KAT-TIP-BRD (Contoh: MAT-SEM-TR)
        $prefix = "{$category->code}-{$type->code}-{$brand->code}-{$size->code}";

        // 4. Cari Urutan Terakhir untuk Prefix ini
        // Kita cari produk yang kodenya diawali dengan "MAT-SEM-TR-"
        $lastProduct = Product::where('code', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(code) DESC') // Pastikan urutan panjang karakter benar
            ->orderBy('code', 'desc')
            ->first();

        $sequence = 1;

        if ($lastProduct) {
            // Format Existing: MAT-SEM-TR-001
            // Pecah berdasarkan strip
            $parts = explode('-', $lastProduct->code);
            $lastSeq = end($parts); // Ambil bagian paling belakang

            if (is_numeric($lastSeq)) {
                $sequence = (int) $lastSeq + 1;
            }
        }

        // 5. Gabungkan Hasil Akhir
        // Hasil: MAT-SEM-TR-001
        return $prefix.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
