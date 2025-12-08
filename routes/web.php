<?php

use Inertia\Inertia;
use App\Services\InsightService;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesRecapController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/test-dss', function (InsightService $service) {

        // Jalankan Analisa
        $service->runAnalysis();

        return "Analisa DSS Selesai! Cek tabel smart_insights di database.";
    })->name('test.dss');
    Route::controller(\App\Http\Controllers\DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
    });

    Route::delete('products/{id}', [\App\Http\Controllers\ProductController::class, 'destroy'])
        ->name('products.destroy');
    Route::put('products/restore/{id}', [\App\Http\Controllers\ProductController::class, 'restoreProduct'])->name('products.restoreProduct');

    // Pemmbelian
    Route::resource('purchases', PurchaseController::class)->except([
        'show',
        'edit',
        'update' // Kita akan buat rute ini manual/spesifik nanti
    ]);
    Route::controller(PurchaseController::class)->group(function () {
        // =============== RUTE PURCHASE =====================
        // Rute khusus untuk Aksi Cepat (Update Status)
        Route::put('purchases/{purchase}/status', 'updateStatus')
            ->name('purchases.update-status');
        // Rute detail
        Route::get('purchases/{purchase}', 'checking')
            ->name('purchases.show');
        // Rute untuk Halaman Checking/Validasi (Flow Khusus)
        Route::get('purchases/{purchase}/checking', 'checking')
            ->name('purchases.checking');
        Route::get('purchases/{id}/print', 'print')->name('purchases.print');

        // RUTE INVOICE
        // POST Rute untuk Upload Invoice
        Route::post('purchases/{purchase}/store-invoice', [PurchaseController::class, 'storeInvoice'])
            ->name('purchases.storeInvoice');
        // Rute Detail Update Delete Invoice
        Route::prefix('purchases/{purchase}')->group(function () {
            // TAMPILAN DETAIL INVOICE
            Route::get('invoices/{invoice}', 'linkItemsView')->name('purchases.linkInvoiceItems');
            // TAUTKAN PRODUK KE INVOICE
            Route::post('invoices/{invoice}/link', 'linkItems')->name('purchases.linkItems');
            // LEPASKAN PRODUK DARI INVOICE
            Route::put('invoices/{invoice}/unlink-items', 'unlinkItems')->name('purchases.unlinkItems');
            // MEMPERBARUI QTY DAN HARGA PRODUK YANG DITAUTKAN
            Route::put('{invoice}/update-linked-item-details', [PurchaseController::class, 'updateLinkedItemDetails'])
                ->name('purchases.updateLinkedItemDetails');
            // MEMPERBARUI INVOICE
            Route::put('invoices/{invoice}',  'updateInvoice')->name('purchases.updateInvoice');
            // MENGHAPUS INVOCIE
            Route::delete('invoices/{invoice}',  'destroyInvoice')->name('purchases.destroyInvoice');
            // VALIDASI INVOICE
            Route::put('invoices/{invoice}/validate', 'validateInvoice')->name('purchases.validateInvoice');
        });

        // POST Rute untuk Selesaikan validasi
        Route::put('purchases/{purchase}/finalize', [PurchaseController::class, 'finalize'])
            ->name('purchases.finalize');

        // Rute untuk Edit (Hanya jika status memungkinkan)
        Route::get('purchases/{purchase}/edit', 'edit')
            ->name('purchases.edit');
        Route::put('purchases/{purchase}', 'update')
            ->name('purchases.update');
        // Rute get rekomendasi
        Route::get('purchases/recommendations/{supplierId}', 'getRecommendations')
            ->name('purchases.recommendations');
    });

    // Penjualan
    Route::controller(SalesRecapController::class)->group(function () {
        Route::get('/sales/search-product', 'searchProduct')
            ->name('sales.search-product');
    });

    // Keuangan
    Route::controller(PaymentController::class)->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('detail');
        Route::post('/{id}/pay', 'store')->name('store');
    });

    // Laporan
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        // PILAR 1 INVENTORY
        Route::get('/stock-card', 'stockCard')->name('stock-card');
        Route::get('/stock-value',  'stockValue')->name('stock-value');
        Route::get('/dead-stock', 'deadStock')->name('dead-stock');
        // PILAR 2 SALES
        Route::get('/sales-revenue', [ReportController::class, 'salesRevenue'])->name('sales-revenue');
        Route::get('/top-products', [ReportController::class, 'topProducts'])->name('top-products');
        Route::get('/gross-profit', [ReportController::class, 'grossProfit'])->name('gross-profit');
    });

    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['destroy']);;
    Route::resource('sales', SalesRecapController::class);
    // Route::resource('reports',  \App\Http\Controllers\ReportController::class);

    // Setting Route DATA MASTER
    Route::controller(\App\Http\Controllers\SettingController::class)->group(function () {
        Route::get('settings', 'index')->name('settings');
        Route::prefix('settings')->name("api.settings.")->group(function () {
            // CRUD API Category Settings
            Route::get('category', 'getCategories')->name('getCategory');
            Route::post('category', 'storeCategory')->name('storeCategory');
            Route::put('category/{id}/restore', 'restoreCategory')->name('restoreCategory');
            Route::put('category/{id}', 'updateCategory')->name('updateCategory');
            Route::delete('category/{id}', 'deleteCategory')->name('deleteCategory');
            // CRUD API Type Produk Settings
            Route::get('type', 'getType')->name('getType');
            Route::post('type', 'storeType')->name('storeType');
            Route::put('type/{id}/restore', 'restoreType')->name('restoreType');
            Route::put('type/{id}', 'updateType')->name('updateType');
            Route::delete('type/{id}', 'deleteType')->name('deleteType');
            // CRUD API Unit Settings
            Route::get('unit', 'getUnit')->name('getUnit');
            Route::post('unit', 'storeUnit')->name('storeUnit');
            Route::put('unit/{id}/restore', 'restoreUnit')->name('restoreUnit');
            Route::put('unit/{id}', 'updateUnit')->name('updateUnit');
            Route::delete('unit/{id}', 'deleteUnit')->name('deleteUnit');
            // CRUD API Size Settings
            Route::get('size', 'getSize')->name('getSize');
            Route::post('size', 'storeSize')->name('storeSize');
            Route::put('size/{id}/restore', 'restoreSize')->name('restoreSize');
            Route::put('size/{id}', 'updateSize')->name('updateSize');
            Route::delete('size/{id}', 'deleteSize')->name('deleteSize');
            // CRUD API Size Settings
            Route::get('brand', 'getBrand')->name('getBrand');
            Route::post('brand', 'storeBrand')->name('storeBrand');
            Route::put('brand/{id}/restore', 'restoreBrand')->name('restoreBrand');
            Route::put('brand/{id}', 'updateBrand')->name('updateBrand');
            Route::delete('brand/{id}', 'deleteBrand')->name('deleteBrand');
            // CRUD API Supplier Settings
            Route::get('supplier', 'getSupplier')->name('getSupplier');
            Route::post('supplier', 'storeSupplier')->name('storeSupplier');
            Route::put('supplier/{id}/restore', 'restoreSupplier')->name('restoreSupplier');
            Route::put('supplier/{id}', 'updateSupplier')->name('updateSupplier');
            Route::delete('supplier/{id}', 'deleteSupplier')->name('deleteSupplier');
        });
    });
});


Route::get('/user', function () {
    return Inertia::render('user');
})->middleware(['auth', 'verified'])->name('user');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
