<?php

namespace App\Services;

use App\Models\Size;
use Illuminate\Support\Facades\Validator; // <-- Impor Validator
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SizeService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return Size::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Mengambil semua data kategori.
     */
    public function getCount()
    {
        return Size::count();
    }

    public function get(array $params)
    {
        $query = Size::query();
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
            'code' => 'required|string|max:20|unique:sizes,code', // 'unique' di tabel 'size', kolom 'code'
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Size::create($validator->validated());
    }

    public function update($id, array $data)
    {
        // 1. Temukan data, jika tidak ada akan error (findOrFail)
        $size = Size::findOrFail($id);

        // 2. Validasi data
        $validator = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:20',
                // Aturan 'unique' yang mengabaikan ID saat ini
                Rule::unique('sizes')->ignore($size->id),
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 3. Update data
        $size->update($validator->validated());

        return $size;
    }

    public function delete($id, array $params = [])
    {
        $size = Size::withTrashed()->findOrFail($id);
        if (isset($params['permanen']) && $params['permanen']) {
            return $size->forceDelete();
        } else {
            return $size->delete();
        }
    }

    public function restore($id)
    {
        $size = Size::withTrashed()->findOrFail($id);

        return $size->restore();
    }
}
