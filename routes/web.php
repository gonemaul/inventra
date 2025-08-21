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
    });

    Route::get('products-detail', function () {
        return Inertia::render('Products/detail');
    })->name('products-detail');
    Route::resource('products', \App\Http\Controllers\ProductController::class);
    Route::resource('purchases', \App\Http\Controllers\PurchaseController::class);
    Route::resource('sellings', \App\Http\Controllers\SellingController::class);

    Route::resource('reports',  \App\Http\Controllers\ReportController::class);
    Route::controller(\App\Http\Controllers\SettingController::class)->group(function () {
        Route::get('/settings', 'index')->name('settings');
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
