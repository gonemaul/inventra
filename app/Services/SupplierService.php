<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Service untuk mengelola logika bisnis Supplier.
 */
class SupplierService
{
    public function getAll()
    {
        // Ambil ID dan Nama, urutkan berdasarkan Nama
        return Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Mengambil data supplier untuk datatable (server-side).
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get(array $params)
    {
        $query = Supplier::query();

        // 1. Cek apakah 'trashed' (sampah) diminta
        if (isset($params['trashed']) && $params['trashed']) {
            $query->onlyTrashed();
        }

        // 2. Logika pencarian (lebih kompleks untuk supplier)
        if (isset($params['search']) && $params['search']) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        // 3. Sorting & Pagination
        $sortBy = $params['sort'] ?? 'created_at';
        $sortDirection = $params['order'] ?? 'desc';
        $perPage = $params['per_page'] ?? 10;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Mendapatkan jumlah total supplier yang aktif (tidak di-sampah).
     *
     * @return int
     */
    public function getCount()
    {
        return Supplier::count();
    }

    /**
     * Validasi dan simpan supplier baru.
     *
     * @return Supplier
     *
     * @throws ValidationException
     */
    public function create(array $data)
    {
        // Tentukan aturan validasi untuk bidang baru
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:200',
            'status' => ['required', Rule::in(Supplier::STATUSES)], // Asumsi 1 (true) = Aktif, 0 (false) = Tidak Aktif
            'type' => ['required', Rule::in(Supplier::TYPES)], // Misal: 'Distributor', 'Grosir', 'Importir'
            'description' => 'nullable|string|max:250',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Supplier::create($validator->validated());
    }

    /**
     * Validasi dan perbarui supplier yang ada.
     *
     * @param  int|string  $id
     * @return Supplier
     *
     * @throws ValidationException|ModelNotFoundException
     */
    public function update($id, array $data)
    {
        $supplier = Supplier::findOrFail($id);

        // Aturan validasi (abaikan unik untuk dirinya sendiri)
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:suppliers,name,'.$id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:200',
            'status' => ['required', Rule::in(Supplier::STATUSES)],
            'type' => ['required', Rule::in(Supplier::TYPES)],
            'description' => 'nullable|string|max:250',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $supplier->update($validator->validated());

        return $supplier;
    }

    /**
     * Hapus supplier (Soft Delete atau Force Delete).
     *
     * @param  int|string  $id
     * @return bool
     *
     * @throws ModelNotFoundException
     */
    public function delete($id, array $params = [])
    {
        // Gunakan withTrashed() agar bisa hapus permanen data di sampah
        $supplier = Supplier::withTrashed()->findOrFail($id);

        if (isset($params['permanen']) && $params['permanen']) {
            // Hapus permanen
            return $supplier->forceDelete();
        } else {
            // Soft delete
            $supplier->update(['status' => Supplier::STATUS_INACTIVE]);

            return $supplier->delete();
        }
    }

    /**
     * Pulihkan supplier dari soft delete.
     *
     * @param  int|string  $id
     * @return bool
     *
     * @throws ModelNotFoundException
     */
    public function restore($id)
    {
        // Cari HANYA di data sampah
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->update(['status' => Supplier::STATUS_ACTIVE]);

        return $supplier->restore();
    }
}
