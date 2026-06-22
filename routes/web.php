<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MasterData\BrandController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\MasterData\CustomerController;
use App\Http\Controllers\MasterData\EmployeeController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\MasterData\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/ui-kit', fn () => Inertia::render('UiKit'))->name('ui-kit');

    /*
    |--------------------------------------------------------------------------
    | Master Data — read-only reference views (Sprint 4)
    |--------------------------------------------------------------------------
    */
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses');
        Route::get('/products',   [ProductController::class,   'index'])->name('products');
        Route::get('/customers',  [CustomerController::class,  'index'])->name('customers');
        Route::get('/employees',  [EmployeeController::class,  'index'])->name('employees');
        Route::get('/categories', [CategoryController::class,  'index'])->name('categories');
        Route::get('/brands',     [BrandController::class,     'index'])->name('brands');
        Route::get('/units',      [UnitController::class,      'index'])->name('units');
    });
});
