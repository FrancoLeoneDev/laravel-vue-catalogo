<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

/*
 * Public catalog.
 */
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/productos/{product}', [CatalogController::class, 'show'])->name('catalog.show');

/*
 * Inventory management panel.
 */
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    Route::get('movements', [StockMovementController::class, 'index'])->name('movements.index');
    Route::post('movements', [StockMovementController::class, 'store'])->name('movements.store');
});

/*
 * The starter kit's authenticated layout links to a `dashboard` route by name.
 */
Route::middleware(['auth', 'verified'])->get('/dashboard', fn () => to_route('admin.dashboard'))
    ->name('dashboard');

require __DIR__.'/settings.php';
