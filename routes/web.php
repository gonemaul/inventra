<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(\App\Http\Controllers\DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
    });

    Route::controller(\App\Http\Controllers\PaymentController::class)->group(function () {
        Route::get('/payments', 'index')->name('payments');
        Route::get('/payments-detail', 'detail')->name('detail');
    });

    Route::get('checking', function () {
        return Inertia::render('Purchases/detail', [
            'type' => 'checking',
        ]);
    })->name('checking');
    Route::get('checking-detail', function () {
        return Inertia::render('Purchases/checking');
    })->name('checking-detail');

    Route::delete('products/{id}', [\App\Http\Controllers\ProductController::class, 'destroy'])
        ->name('products.destroy');
    Route::put('products/restore/{id}', [\App\Http\Controllers\ProductController::class, 'restoreProduct'])->name('products.restoreProduct');

    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['destroy']);;
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class);
    Route::resource('sellings', \App\Http\Controllers\SellingController::class);

    Route::resource('reports',  \App\Http\Controllers\ReportController::class);

    // Setting Route
    Route::controller(\App\Http\Controllers\SettingController::class)->group(function () {
        Route::get('settings', 'index')->name('settings');
        Route::prefix('settings')->name("api.settings.")->group(function () {
            // CRUD API Category Settings
            Route::get('category', 'getCategories')->name('getCategory');
            Route::post('category', 'storeCategory')->name('storeCategory');
            Route::put('category/{id}/restore', 'restoreCategory')->name('restoreCategory');
            Route::put('category/{id}', 'updateCategory')->name('updateCategory');
            Route::delete('category/{id}', 'deleteCategory')->name('deleteCategory');
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
