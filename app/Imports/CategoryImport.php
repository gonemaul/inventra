<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Baris 1 adalah Header
use Maatwebsite\Excel\Concerns\WithValidation; // Validasi Baris

class CategoryImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
        return new Category([
            'name' => $row['nama_kategori'],
            'code' => $row['kode_kategori'],
            'description' => $row['deskripsi'] ?? null,
        ]);
    }

    public function rules(): array
    {
        // Validasi per baris excel
        return [
            'kode_kategori' => 'required|string|max:20|unique:categories,code', // 'unique' di tabel 'categories', kolom 'code'
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
        ];
    }
}
