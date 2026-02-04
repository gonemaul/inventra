<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return Unit::orderBy('name')->get(['id', 'name']);
    }

    public function getCount()
    {
        return Unit::count();
    }

    public function get(array $params)
    {
        $query = Unit::query();
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
            'code' => 'required|string|max:20|unique:units,code', // 'unique' di tabel 'units', kolom 'code'
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_decimal' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Unit::create($validator->validated());
    }

    public function update($id, array $data)
    {
        // 1. Temukan data, jika tidak ada akan error (findOrFail)
        $Unit = Unit::findOrFail($id);

        // 2. Validasi data
        $validator = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:20',
                // Aturan 'unique' yang mengabaikan ID saat ini
                Rule::unique('units')->ignore($Unit->id),
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 3. Update data
        $Unit->update($validator->validated());

        return $Unit;
    }

    public function delete($id, array $params = [])
    {
        $Unit = Unit::withTrashed()->findOrFail($id);
        if (isset($params['permanen']) && $params['permanen']) {
            return $Unit->forceDelete();
        } else {
            return $Unit->delete();
        }
    }

    public function restore($id)
    {
        $Unit = Unit::withTrashed()->findOrFail($id);

        return $Unit->restore();
    }
}
