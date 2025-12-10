<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\SizeService;
use App\Services\TypeService;
use App\Services\UnitService;
use App\Services\BrandService;
use App\Services\CategoryService;

use App\Services\SupplierService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;


class SettingController extends Controller
{
    protected $categoryService;
    protected $typeService;
    protected $unitService;
    protected $sizeService;
    protected $brandService;
    protected $supplierService;

    public function __construct(
        CategoryService $categoryService,
        TypeService $typeService,
        UnitService $unitService,
        SizeService $sizeService,
        BrandService $brandService,
        SupplierService $supplierService
    ) {
        $this->categoryService = $categoryService;
        $this->typeService = $typeService;
        $this->unitService = $unitService;
        $this->sizeService = $sizeService;
        $this->brandService = $brandService;
        $this->supplierService = $supplierService;
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }
    public function index()
    {
        $appName = config('backup.backup.name');
        $disk = Storage::disk('public');
        $files = $disk->exists($appName) ? $disk->files($appName) : [];
        $settingInfo = \App\Models\Setting::where('key', 'enable_auto_backup')->first();
        $isAutoBackup = $settingInfo && $settingInfo->value === 'true';
        $backups = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => $this->formatSize($disk->size($file)), // Pastikan ada helper formatSize
                    'date' => Carbon::createFromTimestamp($disk->lastModified($file))->diffForHumans(),
                    'timestamp' => $disk->lastModified($file) // Untuk sorting
                ];
            }
        }

        // Urutkan terbaru
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return Inertia::render('Settings/index', [
            'categoryCount' => $this->categoryService->getCount(),
            'unitCount' => $this->unitService->getCount(),
            'sizeCount' => $this->sizeService->getCount(),
            'brandCount' => $this->brandService->getCount(),
            'productTypeCount' => $this->typeService->getCount(),
            'supplierCount' => $this->supplierService->getCount(),
            'categories' => $this->categoryService->getAll(),
            'backups' => $backups,
            'autoBackupEnabled' => $isAutoBackup,
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
        $isPermanent = $request->input('permanen', default: false);
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

    // ================== Type Methods ==================
    public function getType()
    {
        if (request()->ajax()) {
            $type = $this->typeService->get(request()->all());
            return $type;
        }
    }

    public function storeType(Request $request)
    {
        $type = $this->typeService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Tipe Produk berhasil ditambahkan!');
    }

    public function updateType(Request $request, $id)
    {
        try {
            $type = $this->typeService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Tipe Produk berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteType(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', default: false);
        $this->typeService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Tipe Produk berhasil dihapus permanen!'
            : 'Tipe Produk berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreType($id)
    {
        $this->typeService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Tipe Produk berhasil dipulihkan!');
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

    // ================== Brand Methods ==================
    public function getBrand()
    {
        if (request()->ajax()) {
            $brands = $this->brandService->get(request()->all());
            return $brands;
        }
    }

    public function storeBrand(Request $request)
    {
        $brand = $this->brandService->create($request->all());

        return Redirect::route('settings')
            ->with('success', 'Merk berhasil ditambahkan!');
    }

    public function updateBrand(Request $request, $id)
    {
        try {
            $brand = $this->brandService->update($id, $request->all());

            return Redirect::route('settings')
                ->with('success', 'Merk berhasil diperbarui!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('settings')
                ->with('error', $e->errors());
        } catch (\Exception $e) {
            return Redirect::route('settings')
                ->with('error', $e->getMessage());
        }
    }

    public function deleteBrand(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', default: false);
        $this->brandService->delete($id, $request->all());
        $message = $isPermanent
            ? 'Merk berhasil dihapus permanen!'
            : 'Merk berhasil dipindahkan ke sampah!';
        return Redirect::route('settings')
            ->with('success', $message);
    }

    public function restoreBrand($id)
    {
        $this->brandService->restore($id);

        return Redirect::route('settings')
            ->with('success', 'Merk berhasil dipulihkan!');
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
