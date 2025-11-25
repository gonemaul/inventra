<?php

namespace App\Services;

use App\Models\Size;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService
{
    private function handleImageUpload($file, $existingPath = null)
    {
        // 1. Hapus gambar lama jika ada file baru
        if ($existingPath && $file) {
            Storage::disk('public')->delete($existingPath);
        }

        // 2. Jika ada file baru, simpan
        if ($file) {
            // Simpan di storage/app/public/products
            return $file->store('products', 'public');
        }

        // 3. Jika tidak ada file baru, kembalikan path lama
        return $existingPath;
    }
    /**
     * Mengambil data produk untuk datatable (server-side).
     *
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get(array $params)
    {
        // Selalu ambil relasi (eager load) untuk efisiensi
        $query = Product::with(['category', 'unit', 'size', 'supplier', 'brand', 'productType']);

        // 1. Filter 'trashed' (Sampah)
        if (isset($params['trashed']) && $params['trashed']) {
            $query->onlyTrashed();
        }

        // 2. Filter 'search' (Nama atau Kode)
        if (isset($params['search']) && $params['search']) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }
        if (isset($params['status']) && $params['status']) {
            $query->where('status', $params['status']);
        }
        if (isset($params['category_id']) && $params['category_id']) {
            $query->where('category_id', $params['category_id']);
        }
        if (isset($params['supplier_id']) && $params['supplier_id']) {
            $query->where('supplier_id', $params['supplier_id']);
        }
        if (isset($params['sizes']) && is_array($params['sizes']) && count($params['sizes']) > 0) {
            $query->whereIn('size_id', $params['sizes']);
        }
        if (isset($params['min_stock']) && is_numeric($params['min_stock'])) {
            $query->where('stock', '>=', $params['min_stock']);
        }
        if (isset($params['max_stock']) && is_numeric($params['max_stock'])) {
            $query->where('stock', '<=', $params['max_stock']);
        }
        if (isset($params['min_price']) && is_numeric($params['min_price'])) {
            $query->where('selling_price', '>=', $params['min_price']);
        }
        if (isset($params['max_price']) && is_numeric($params['max_price'])) {
            $query->where('selling_price', '<=', $params['max_price']);
        }
        $sortBy = $params['sort'] ?? 'created_at';
        $sortDirection = $params['order'] ?? 'desc';

        // Whitelist untuk keamanan
        $sortableColumns = ['created_at', 'name', 'selling_price', 'stock'];
        if (!in_array($sortBy, $sortableColumns)) {
            $sortBy = 'created_at';
        }
        $perPage = $params['per_page'] ?? 10;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString(); // <-- Ini PENTING
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
        $validatedData = $validator->validated();
        $validatedData['code'] = $generatedCode;
        if ($imageFile) {
            $imageValidator = Validator::make(['image' => $imageFile], [
                'image' => 'nullable|image|mimes:jpeg,png,webp|max:1024' // Maks 1MB
            ]);
            if ($imageValidator->fails()) throw new ValidationException($imageValidator);

            // Simpan path ke data yang divalidasi
            $validatedData['image_path'] = $this->handleImageUpload($imageFile);
        }
        return Product::create($validatedData);
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
                'image' => 'nullable|image|mimes:jpeg,png,webp|max:1024'
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
