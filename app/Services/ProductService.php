<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Size;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\SaleItem;
use App\Models\ProductType;
use App\Models\PurchaseItem;
use App\Models\SmartInsight;
use App\Models\StockMovement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService
{
    protected $insightService;
    protected $stockService;
    protected $imageService;
    public function __construct(InsightService $insightService, StockService $stockService, ImageService $imageService)
    {
        $this->insightService = $insightService;
        $this->stockService = $stockService;
        $this->imageService = $imageService;
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
     * LOGIC PUSAT: MENGHITUNG METRIK KEUANGAN
     * Mengembalikan array lengkap (Nominal & Persen)
     */
    private function calculateFinancials($product)
    {
        // 1. DATA SAAT INI
        $buy = (float) $product->purchase_price;
        $sell = (float) $product->selling_price;

        // 2. DATA SNAPSHOT (Histori)
        // Default ke harga sekarang jika snapshot kosong (artinya tidak ada perubahan)
        $oldBuy = (float) ($product->snapshot['purchase_price'] ?? $buy);
        $oldSell = (float) ($product->snapshot['selling_price'] ?? $sell);

        // --- A. ANALISA MARGIN (KEUNTUNGAN) ---
        $marginRp = $sell - $buy;
        // Hindari division by zero
        $marginPercent = $buy > 0 ? round(($marginRp / $buy) * 100, 1) : 0;

        // --- B. ANALISA TREN HARGA MODAL (PURCHASE) ---
        $diffBuy = $buy - $oldBuy;
        $trendBuyPercent = $oldBuy > 0 ? round(($diffBuy / $oldBuy) * 100, 1) : 0;

        $trendBuyDirection = 'flat';
        if ($diffBuy > 0) $trendBuyDirection = 'up';     // Modal Naik (Buruk/Warning)
        if ($diffBuy < 0) $trendBuyDirection = 'down';   // Modal Turun (Bagus)

        // --- C. ANALISA TREN HARGA JUAL (SELLING) ---
        $diffSell = $sell - $oldSell;
        $trendSellPercent = $oldSell > 0 ? round(($diffSell / $oldSell) * 100, 1) : 0;

        $trendSellDirection = 'flat';
        if ($diffSell > 0) $trendSellDirection = 'up';   // Jual Naik (Bagus/Cuan)
        if ($diffSell < 0) $trendSellDirection = 'down'; // Jual Turun (Diskon)

        // RETURN PAKET LENGKAP
        return [
            'margin' => [
                'rp' => $marginRp,
                'percent' => $marginPercent
            ],
            'purchase_trend' => [
                'direction' => $trendBuyDirection,
                'diff_rp' => abs($diffBuy),      // Kita kirim angka positif untuk tampilan
                'percent' => abs($trendBuyPercent),
                'old_price' => $oldBuy
            ],
            'selling_trend' => [
                'direction' => $trendSellDirection,
                'diff_rp' => abs($diffSell),
                'percent' => abs($trendSellPercent),
                'old_price' => $oldSell
            ]
        ];
    }

    /**
     * LOGIC PUSAT: ANALISA STOK & FORECASTING
     */
    private function calculateInventoryMetrics($product)
    {
        // 1. DATA DASAR
        $stock = $product->stock;
        $minStock = $product->min_stock ?? 0;

        // 2. HITUNG VELOCITY (KECEPATAN JUAL)
        // Ambil data penjualan 30 hari terakhir
        $sales30Days = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', now()->subDays(30)))
            ->sum('quantity');

        // Rata-rata jual per hari (Daily Burn Rate)
        $avgDaily = $sales30Days > 0 ? round($sales30Days / 30, 1) : 0;

        // 3. FORECASTING (PREDIKSI HABIS)
        $daysLeft = 999; // Default aman
        $stockoutDate = null;
        $status = 'safe'; // safe, warning, critical, overstock

        if ($avgDaily > 0) {
            $daysLeft = floor($stock / $avgDaily);
            $stockoutDate = now()->addDays($daysLeft)->isoFormat('D MMMM Y'); // Contoh: 25 November 2024

            // Tentukan Status berdasarkan sisa hari
            if ($daysLeft <= 3) $status = 'critical'; // Habis < 3 hari
            elseif ($daysLeft <= 7) $status = 'warning'; // Habis seminggu lagi
        } elseif ($stock <= $minStock) {
            // Jika tidak ada data penjualan tapi stok dibawah min
            $status = 'warning';
        }

        // 4. SARAN RESTOCK (SUGGESTION)
        // Rumus sederhana: Targetkan stok aman untuk 14 hari kedepan (bisa disesuaikan)
        $targetDays = 14;
        $suggestedQty = 0;

        if ($status !== 'safe' || $stock <= $minStock) {
            $idealStock = $avgDaily * $targetDays;
            $suggestedQty = ceil($idealStock - $stock);
            // Pastikan saran minimal sebesar min_stock jika stok 0
            if ($suggestedQty <= 0 && $stock < $minStock) {
                $suggestedQty = $minStock - $stock;
            }
        }

        // 5. ANALISA DEAD STOCK (LOGIC SIMPLE)
        // Cek transaksi terakhir
        $deadStockInsight = $product->insights->where('type', 'dead_stock')->first();
        $lastSaleDate = null;
        $isDeadStock = false;
        $daysInactive = 0;

        if ($deadStockInsight) {
            // Jika ada insight, BERARTI DIA DEAD STOCK. Ikuti data insight.
            $isDeadStock = true;
            $daysInactive = $deadStockInsight->payload['days_inactive'] ?? 0;
            // Override status visual jika perlu
            $status = 'dead';
        } else {
            $lastItem = SaleItem::with('sale')
                ->where('product_id', $product->id)
                ->latest()
                ->first();

            if ($lastItem) {
                $lastSaleDate = $lastItem->sale->transaction_date;
                // Jika tidak laku > 60 hari dan stok masih ada
                $daysInactive = round(Carbon::parse($lastSaleDate)->diffInDays(now()));
                if ($stock > 0 && $daysInactive > 60) {
                    $isDeadStock = true;
                    $status = 'dead';
                }
            }
        }

        return [
            'stock_status' => $status,      // critical, warning, safe, dead
            'avg_daily'    => $avgDaily,    // Rata-rata laku per hari
            'days_left'    => $daysLeft,    // Sisa hari
            'stockout_date' => $stockoutDate, // Tanggal estimasi habis
            'sales_30_days' => $sales30Days, // Total laku sebulan
            'suggested_qty' => $suggestedQty, // Saran beli
            'is_dead_stock' => $isDeadStock,
            'days_inactive' => $daysInactive,
            'last_sale'    => $lastSaleDate ? Carbon::parse($lastSaleDate)->diffForHumans() : 'Belum pernah terjual'
        ];
    }
    /**
     * Mengambil data produk untuk datatable (server-side).
     *
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get(array $params)
    {
        $this->insightService->runAnalysis();
        // Selalu ambil relasi (eager load) untuk efisiensi
        $query = Product::with(['category', 'unit', 'size', 'supplier', 'brand', 'productType', 'insights' => function ($q) {
            // Ambil insight yang masih aktif (belum dibaca/diselesaikan)
            $q->where('is_read', false);
        }]);

        // 1. SEARCH GLOBAL
        $query->when($params['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        });

        // 2. FILTER RELASI (Foreign Keys)
        $query->when($params['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v));
        $query->when($params['brand_id'] ?? null, fn($q, $v) => $q->where('brand_id', $v));
        $query->when($params['unit_id'] ?? null, fn($q, $v) => $q->where('unit_id', $v));
        $query->when($params['size_id'] ?? null, fn($q, $v) => $q->where('size_id', $v));
        $query->when($params['supplier_id'] ?? null, fn($q, $v) => $q->where('supplier_id', $v));
        $query->when($params['product_type_id'] ?? null, fn($q, $v) => $q->where('product_type_id', $v));

        // 3. FILTER STATUS & TRASH
        $query->when($params['status'] ?? null, fn($q, $v) => $q->where('status', $v));

        $query->when($params['trashed'] ?? null, function ($q, $trashed) {
            if ($trashed === 'with') $q->withTrashed();
            if ($trashed === 'only') $q->onlyTrashed();
        });

        // 4. FILTER RANGE (Min - Max)
        // Harga Jual
        $query->when($params['price_min'] ?? null, fn($q, $v) => $q->where('selling_price', '>=', $v));
        $query->when($params['price_max'] ?? null, fn($q, $v) => $q->where('selling_price', '<=', $v));
        // Harga Beli
        $query->when($params['cost_min'] ?? null, fn($q, $v) => $q->where('purchase_price', '>=', $v));
        $query->when($params['cost_max'] ?? null, fn($q, $v) => $q->where('purchase_price', '<=', $v));
        // Stok
        $query->when($params['stock_min'] ?? null, fn($q, $v) => $q->where('stock', '>=', $v));
        $query->when($params['stock_max'] ?? null, fn($q, $v) => $q->where('stock', '<=', $v));

        // 5. SORTING
        $sortField = $params['sort'] ?? 'created_at';
        $sortDirection = $params['order'] ?? 'desc';

        $allowedSorts = ['name', 'selling_price', 'purchase_price', 'stock', 'created_at', 'code'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $params['per_page'] ?? 10;
        $products = $query->paginate($perPage)
            ->withQueryString();

        $products->getCollection()->transform(function ($product) {
            $product->financials = $this->calculateFinancials($product);
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
    public function getProductDetails($id)
    {
        // 1. Jalankan Analisa DSS (Agar data selalu fresh saat dibuka)
        $this->insightService->runAnalysis();
        // 2. Ambil Data Produk & Relasi
        $product = Product::with(['category', 'unit', 'size', 'supplier', 'brand', 'productType'])
            ->withTrashed() // Handle jika produk soft deleted
            ->findOrFail($id);

        $inventory = $this->calculateInventoryMetrics($product);
        $financials = $this->calculateFinancials($product);
        // 3. Ambil Insight DSS (Hasil Analisa)
        $insights = SmartInsight::where('product_id', $id)->get();

        // Struktur data DSS untuk Frontend
        $dssData = [
            'restock'       => $insights->where('type', 'restock')->first(),
            'dead_stock'    => $insights->where('type', 'dead_stock')->first(),
            'trend'         => $insights->where('type', 'trend')->first(),
            'margin_alert'  => $insights->where('type', 'margin_alert')->first(),
            'is_trending'   => $insights->where('type', 'trend')->count() > 0,
            'is_dead_stock' => $insights->where('type', 'dead_stock')->count() > 0,
            'is_margin_low' => $insights->where('type', 'margin_alert')->count() > 0,
        ];


        // 4. Statistik Penjualan (Untuk Tab Ringkasan)
        $salesStats = SaleItem::where('product_id', $id)
            ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', now()->subDays(30)))
            ->sum('quantity');

        $dssData['sales_30_days'] = $salesStats;
        $dssData['avg_daily'] = $salesStats > 0 ? round($salesStats / 30, 1) : 0;

        // 5. Data Grafik (7 Hari Terakhir)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $qty = SaleItem::where('product_id', $id)
                ->whereHas('sale', fn($q) => $q->whereDate('transaction_date', $date))
                ->sum('quantity');
            $chartData[] = ['day' => $date->format('d/m'), 'qty' => $qty];
        }

        // 6. Riwayat Stok (Gabungan Beli & Jual Terakhir)
        $stockHistory = $this->getStockHistory($id);

        // 7. Tren Harga (Logic Snapshot)
        $priceTrend = $this->calculatePriceTrend($product);
        $product->financials = $financials;
        $product->inventory = $inventory;
        return [
            'product' => $product,
            'dss' => $dssData,
            'chart_data' => $chartData,
            'stock_history' => $stockHistory,
            'price_trend' => $priceTrend
        ];
    }
    private function getStockHistory($id)
    {
        $lastSales = SaleItem::with('sale')->where('product_id', $id)->latest()->limit(5)->get()
            ->map(fn($item) => [
                'type' => 'out',
                'qty' => $item->quantity,
                'date' => $item->sale->transaction_date ?? $item->created_at,
                'note' => 'Terjual'
            ]);

        $lastPurchases = PurchaseItem::with('purchase')->where('product_id', $id)->latest()->limit(5)->get()
            ->map(fn($item) => [
                'type' => 'in',
                'qty' => $item->quantity,
                'date' => $item->purchase->transaction_date ?? $item->created_at,
                'note' => 'Restock'
            ]);

        return collect($lastSales)->merge($lastPurchases)->sortByDesc('date')->values()->all();
    }

    private function calculatePriceTrend($product)
    {
        // Default
        $trend = ['direction' => 'flat', 'diff' => 0, 'percent' => 0, 'old_price' => 0];

        if (!empty($product->snapshot) && isset($product->snapshot['purchase_price'])) {
            $current = $product->purchase_price;
            $old = $product->snapshot['purchase_price'];
            $diff = $current - $old;
            $percent = $old > 0 ? ($diff / $old) * 100 : 0;

            if ($diff > 0) {
                $trend = ['direction' => 'up', 'diff' => $diff, 'percent' => round(abs($percent), 1), 'old_price' => $old];
            } elseif ($diff < 0) {
                $trend = ['direction' => 'down', 'diff' => abs($diff), 'percent' => round(abs($percent), 1), 'old_price' => $old];
            }
        }
        return $trend;
    }
    /**
     * Validasi dan simpan produk baru.
     *
     * @param array $data
     * @return Product
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
            // 'code' => 'string|max:255|unique:products,code', // 'code' dari migrasi Anda
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
            $validatedData['code'] = $generatedCode;
            if ($imageFile) {
                $imageValidator = Validator::make(['image' => $imageFile], [
                    'image' => 'nullable|image|mimes:jpeg,png,webp|max:20480' // Maks 1MB
                ]);
                if ($imageValidator->fails()) throw new ValidationException($imageValidator);

                // Simpan path ke data yang divalidasi
                $validatedData['image_path'] = $this->handleImageUpload($imageFile);
            }
            $product = Product::create($validatedData);
            if ($validatedData['stock'] > 0) {
                $this->stockService->record(
                    productId: $product->id,
                    qty: $product->stock,
                    type: StockMovement::TYPE_INITIAL,
                    ref: 'INIT',
                    desc: 'Stok Awal Produk Baru'
                );
            }
            return true;
        });
    }

    /**
     * Validasi dan perbarui produk yang ada.
     *
     * @param int|string $id
     * @param array $data
     * @return Product
     * @throws ValidationException|ModelNotFoundException
     */
    public function update($id, array $data)
    {
        $product = Product::findOrFail($id);
        $imageFile = $data['image'] ?? null;
        unset($data['image']);
        $validator = Validator::make($data, [
            // Relasi
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'size_id' => ['nullable', 'integer', Rule::exists('sizes', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],

            // Detail Produk (ubah aturan 'unique')
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('products')->ignore($id)],
            'description' => 'nullable|string',
            // 'image_path' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(Product::STATUSES)],

            // Harga & Stok
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $validatedData = $validator->validated();

        // 3. Validasi dan proses gambar HANYA JIKA ada
        if ($imageFile) {
            $imageValidator = Validator::make(['image' => $imageFile], [
                'image' => 'nullable|image|mimes:jpeg,png,webp|max:20480'
            ]);
            if ($imageValidator->fails()) throw new ValidationException($imageValidator);

            // Kirim path lama untuk dihapus
            $validatedData['image_path'] = $this->handleImageUpload($imageFile, $product->image_path);
        }
        $product->update($validatedData);
        return $product;
    }

    /**
     * Hapus produk (Soft Delete atau Force Delete).
     *
     * @param int|string $id
     * @param array $params
     * @return bool
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
     * @param int|string $id
     * @return bool
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
        $type     = ProductType::find($typeId);
        $brand    = Brand::find($brandId);
        $size    = Size::find($sizeId);

        // 3. Susun Prefix
        // Format: KAT-TIP-BRD (Contoh: MAT-SEM-TR)
        $prefix = "{$category->code}-{$type->code}-{$brand->code}-{$size->code}";

        // 4. Cari Urutan Terakhir untuk Prefix ini
        // Kita cari produk yang kodenya diawali dengan "MAT-SEM-TR-"
        $lastProduct = Product::where('code', 'like', $prefix . '%')
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
                $sequence = (int)$lastSeq + 1;
            }
        }

        // 5. Gabungkan Hasil Akhir
        // Hasil: MAT-SEM-TR-001
        return $prefix . '-' . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }
}
