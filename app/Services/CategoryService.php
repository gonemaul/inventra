<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // <-- Impor Validator
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CategoryService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return Category::with('productTypes:id,category_id,name')->orderBy('name')->get(['id', 'name']);
    }
    /**
     * Mengambil semua data kategori.
     */
    public function getCount()
    {
        return Category::count();
    }
    public function get(array $params)
    {
        $query = Category::query();
        if (isset($params['trashed']) && $params['trashed']) {
            $query->onlyTrashed();
        }

        if (isset($params['search']) && $params['search']) {
            $query->where('name', 'like', '%' . $params['search'] . '%');
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
            'code' => 'required|string|max:20|unique:categories,code', // 'unique' di tabel 'categories', kolom 'code'
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Category::create($validator->validated());
    }

    public function update($id, array $data)
    {
        // 1. Temukan data, jika tidak ada akan error (findOrFail)
        $category = Category::findOrFail($id);

        // 2. Validasi data
        $validator = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:20',
                // Aturan 'unique' yang mengabaikan ID saat ini
                Rule::unique('categories')->ignore($category->id)
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 3. Update data
        $category->update($validator->validated());

        return $category;
    }

    public function delete($id, array $params = [])
    {
        $category = Category::withTrashed()->findOrFail($id);
        if (isset($params['permanen']) && $params['permanen']) {
            return $category->forceDelete();
        } else {
            return $category->delete();
        }
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        return $category->restore();
    }
}
