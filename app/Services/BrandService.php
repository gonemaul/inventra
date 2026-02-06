<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Support\Facades\Validator; // <-- Impor Validator
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return Brand::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Mengambil semua data brand.
     */
    public function getCount()
    {
        return Brand::count();
    }

    public function get(array $params)
    {
        $query = Brand::query()->withCount('products');
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
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'code' => 'required|string|max:20|unique:brands,code', // 'unique' di tabel 'brands', kolom 'code'
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Brand::create($validator->validated());
    }

    public function update($id, array $data)
    {
        // 1. Temukan data, jika tidak ada akan error (findOrFail)
        $brand = Brand::findOrFail($id);

        // 2. Validasi data
        $validator = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:20',
                // Aturan 'unique' yang mengabaikan ID saat ini
                Rule::unique('brands')->ignore($brand->id),
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
        $brand = Brand::withTrashed()->findOrFail($id);
        if (isset($params['permanen']) && $params['permanen']) {
            return $brand->forceDelete();
        } else {
            return $brand->delete();
        }
    }

    public function restore($id)
    {
        $brand = Brand::withTrashed()->findOrFail($id);

        return $brand->restore();
    }
}
