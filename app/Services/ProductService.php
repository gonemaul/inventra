<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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
        $query = Product::with(['category', 'unit', 'size', 'supplier']);

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

            // Detail Produk
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code', // 'code' dari migrasi Anda
            'description' => 'nullable|string',
            // 'image_path' => 'nullable|string|max:255', // 'image_path' dari migrasi Anda
            'status' => ['required', Rule::in(Product::STATUSES)], // Validasi string 'active'/'draft'

            // Harga & Stok
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0', // 'min_stock' dari migrasi Anda
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $validatedData = $validator->validated();
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
}
