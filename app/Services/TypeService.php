<?php

namespace App\Services;

use App\Models\ProductType;
use Illuminate\Support\Facades\Validator; // <-- Impor Validator
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TypeService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return ProductType::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Mengambil semua data brand.
     */
    public function getCount()
    {
        return ProductType::count();
    }

    public function get(array $params)
    {
        $query = ProductType::query();
        if (isset($params['trashed']) && $params['trashed']) {
            $query->onlyTrashed();
        }

        if (isset($params['search']) && $params['search']) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }
        $sortBy = $params['sort'] ?? 'created_at';
        $sortDirection = $params['order'] ?? 'desc';
        $perPage = $params['per_page'] ?? 10;

        return $query
            ->with('category')
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'code' => 'required|string|max:20|unique:product_types,code', // 'unique' di tabel 'product_types', kolom 'code'
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return ProductType::create($validator->validated());
    }

    public function update($id, array $data)
    {
        // 1. Temukan data, jika tidak ada akan error (findOrFail)
        $brand = ProductType::findOrFail($id);

        // 2. Validasi data
        $validator = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:20',
                // Aturan 'unique' yang mengabaikan ID saat ini
                Rule::unique('product_types')->ignore($brand->id),
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 3. Update data
        $brand->update($validator->validated());

        return $brand;
    }

    public function delete($id, array $params = [])
    {
        $brand = ProductType::withTrashed()->findOrFail($id);
        if (isset($params['permanen']) && $params['permanen']) {
            return $brand->forceDelete();
        } else {
            return $brand->delete();
        }
    }

    public function restore($id)
    {
        $brand = ProductType::withTrashed()->findOrFail($id);

        return $brand->restore();
    }
}
