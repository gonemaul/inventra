<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\SizeService;
use App\Services\UnitService;
use App\Services\CategoryService;
use App\Services\SupplierService;
use Illuminate\Support\Facades\Redirect;


class SettingController extends Controller
{
    protected $categoryService;
    protected $unitService;
    protected $sizeService;
    protected $supplierService;

    public function __construct(
        CategoryService $categoryService,
        UnitService $unitService,
        SizeService $sizeService,
        SupplierService $supplierService
    ) {
        $this->categoryService = $categoryService;
        $this->unitService = $unitService;
        $this->sizeService = $sizeService;
        $this->supplierService = $supplierService;
    }

    public function index()
    {
        return Inertia::render('Settings/index', [
            'categoryCount' => $this->categoryService->getCount(),
            'unitCount' => $this->unitService->getCount(),
            'sizeCount' => $this->sizeService->getCount(),
            'supplierCount' => $this->supplierService->getCount(),
        ]);
    }

    // ================== Category Methods ==================
    public function getCategories()
    {
        if (request()->ajax()) {
            $categories = $this->categoryService->get(request()->all());
            return $categories;
        }
    }

    public function storeCategory(Request $request)
    {
        $category = $this->categoryService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function updateCategory(Request $request, $id)
    {
        try {
            $category = $this->categoryService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Kategori berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteCategory(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', false);
        $this->categoryService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Kategori berhasil dihapus permanen!'
            : 'Kategori berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreCategory($id)
    {
        $this->categoryService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Kategori berhasil dipulihkan!');
    }

    // ================== Unit Methods ==================
    public function getUnit()
    {
        if (request()->ajax()) {
            $units = $this->unitService->get(request()->all());
            return $units;
        }
    }

    public function storeUnit(Request $request)
    {
        $unit = $this->unitService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Satuan berhasil ditambahkan!');
    }
    public function updateUnit(Request $request, $id)
    {
        try {
            $unit = $this->unitService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Satuan berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteUnit(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', false);
        $this->unitService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Satuan berhasil dihapus permanen!'
            : 'Satuan berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreUnit($id)
    {
        $this->unitService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Satuan berhasil dipulihkan!');
    }

    // ================== Size Methods ==================
    public function getSize()
    {
        if (request()->ajax()) {
            $sizes = $this->sizeService->get(request()->all());
            return $sizes;
        }
    }

    public function storeSize(Request $request)
    {
        $size = $this->sizeService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Ukuran berhasil ditambahkan!');
    }
    public function updateSize(Request $request, $id)
    {
        try {
            $size = $this->sizeService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Ukuran berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteSize(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', false);
        $this->sizeService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Ukuran berhasil dihapus permanen!'
            : 'Ukuran berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreSize($id)
    {
        $this->sizeService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Ukuran berhasil dipulihkan!');
    }
    // ================== Supplier Methods ==================
    public function getSupplier()
    {
        if (request()->ajax()) {
            $sizes = $this->supplierService->get(request()->all());
            return $sizes;
        }
    }

    public function storeSupplier(Request $request)
    {
        $size = $this->supplierService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Supplier berhasil ditambahkan!');
    }
    public function updateSupplier(Request $request, $id)
    {
        try {
            $size = $this->supplierService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Supplier berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteSupplier(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', false);
        $this->supplierService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Supplier berhasil dihapus permanen!'
            : 'Supplier berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreSupplier($id)
    {
        $this->supplierService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Supplier berhasil dipulihkan!');
    }
}
