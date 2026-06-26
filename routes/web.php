<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardThreeController;
use App\Http\Controllers\DashboardTwoController;
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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard-two')->name('dashboard-two.')->group(function () {
        Route::get('/',                  [DashboardTwoController::class, 'index'])->name('index');
        Route::get('/retail-customers',  [DashboardTwoController::class, 'retailCustomers'])->name('retail-customers');
        Route::get('/wholesale-customers',[DashboardTwoController::class, 'wholesaleCustomers'])->name('wholesale-customers');
    });

    Route::prefix('dashboard-three')->name('dashboard-three.')->group(function () {
        Route::get('/', [DashboardThreeController::class, 'index'])->name('index');

        // Main
        Route::get('/api/summary',      [DashboardThreeController::class, 'mainSummary']);
        Route::get('/api/thai-chart',   [DashboardThreeController::class, 'thaiChart']);
        Route::get('/api/export-chart', [DashboardThreeController::class, 'exportChart']);
        Route::get('/api/thai-top10',   [DashboardThreeController::class, 'thaiTop10']);
        Route::get('/api/export-top10', [DashboardThreeController::class, 'exportTop10']);
        Route::get('/api/thai-all',     [DashboardThreeController::class, 'thaiAll']);
        Route::get('/api/export-all',   [DashboardThreeController::class, 'exportAll']);
        Route::get('/api/slice-detail', [DashboardThreeController::class, 'sliceDetail']);

        // Behavior
        Route::get('/behavior/api/summary',        [DashboardThreeController::class, 'behaviorSummary']);
        Route::get('/behavior/api/charts',         [DashboardThreeController::class, 'behaviorCharts']);
        Route::get('/behavior/api/tier-customers', [DashboardThreeController::class, 'behaviorTierCustomers']);

        // Financial
        Route::get('/financial/api/summary', [DashboardThreeController::class, 'financialSummary']);
        Route::get('/financial/api/charts',  [DashboardThreeController::class, 'financialCharts']);
        Route::get('/financial/api/top',     [DashboardThreeController::class, 'financialTopCustomers']);
        Route::get('/financial/api/payment', [DashboardThreeController::class, 'financialPaymentAnalysis']);

        // Operation
        Route::get('/operation/api/summary',                         [DashboardThreeController::class, 'operationSummary']);
        Route::get('/operation/api/charts',                          [DashboardThreeController::class, 'operationCharts']);
        Route::get('/operation/api/timeline',                        [DashboardThreeController::class, 'operationTimeline']);
        Route::get('/operation/api/followup',                        [DashboardThreeController::class, 'operationFollowups']);
        Route::post('/operation/api/followup',                       [DashboardThreeController::class, 'operationFollowupStore']);
        Route::post('/operation/api/followup/{id}/done',             [DashboardThreeController::class, 'operationFollowupDone']);
        Route::delete('/operation/api/followup/{id}',                [DashboardThreeController::class, 'operationFollowupDelete']);
        Route::get('/operation/api/pipeline',                        [DashboardThreeController::class, 'operationPipeline']);
        Route::get('/operation/api/pipeline-deals',                  [DashboardThreeController::class, 'operationPipelineDeals']);
        Route::post('/operation/api/pipeline-deals',                 [DashboardThreeController::class, 'operationPipelineDealStore']);
        Route::post('/operation/api/pipeline-deals/{id}',            [DashboardThreeController::class, 'operationPipelineDealUpdate']);
        Route::delete('/operation/api/pipeline-deals/{id}',          [DashboardThreeController::class, 'operationPipelineDealDelete']);

        // Product
        Route::get('/product/api/summary',            [DashboardThreeController::class, 'productSummary']);
        Route::get('/product/api/charts',             [DashboardThreeController::class, 'productCharts']);
        Route::get('/product/api/slow-movers',        [DashboardThreeController::class, 'productSlowMovers']);
        Route::get('/product/api/health-scores',      [DashboardThreeController::class, 'productHealthScores']);
        Route::get('/product/api/customer-search',    [DashboardThreeController::class, 'productCustomerSearch']);
        Route::get('/product/api/product-search',     [DashboardThreeController::class, 'productProductSearch']);
        Route::get('/product/api/competitive',        [DashboardThreeController::class, 'competitiveList']);
        Route::post('/product/api/competitive',       [DashboardThreeController::class, 'competitiveStore']);
        Route::delete('/product/api/competitive/{id}',[DashboardThreeController::class, 'competitiveDelete']);
        Route::get('/product/api/competitive-summary',[DashboardThreeController::class, 'competitiveSummary']);
        Route::get('/product/api/feedback',           [DashboardThreeController::class, 'feedbackList']);
        Route::post('/product/api/feedback',          [DashboardThreeController::class, 'feedbackStore']);
        Route::delete('/product/api/feedback/{id}',   [DashboardThreeController::class, 'feedbackDelete']);
        Route::get('/product/api/feedback-overview',  [DashboardThreeController::class, 'feedbackOverview']);
        Route::get('/product/api/competitors',        [DashboardThreeController::class, 'competitorList']);
        Route::post('/product/api/competitors',       [DashboardThreeController::class, 'competitorStore']);
        Route::delete('/product/api/competitors/{id}',[DashboardThreeController::class, 'competitorDelete']);
    });

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
