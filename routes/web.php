<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AssetController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth', 'permission:access admin panel'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // User management
    Route::middleware('permission:view users')->group(function () {
        Route::resource('users', UserController::class);
    });
    
    // Role management
    Route::middleware('permission:view roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });
    
    // Permission management
    Route::middleware('permission:view permissions')->group(function () {
        Route::resource('permissions', PermissionController::class);
    });
});

// Trading Bot routes
Route::middleware(['auth'])->group(function () {
    Route::resource('trading-bots', TradingBotController::class);
    Route::post('trading-bots/{tradingBot}/run', [TradingBotController::class, 'run'])->name('trading-bots.run');
    Route::post('trading-bots/{tradingBot}/toggle-status', [TradingBotController::class, 'toggleStatus'])->name('trading-bots.toggle-status');
Route::get('trading-bots/{tradingBot}/trades', [TradingBotController::class, 'trades'])->name('trading-bots.trades');
Route::get('trading-bots/{tradingBot}/signals', [TradingBotController::class, 'signals'])->name('trading-bots.signals');
Route::get('trading-bots/{tradingBot}/logs', [TradingBotController::class, 'logs'])->name('trading-bots.logs');
    
    // API Key routes
    Route::resource('api-keys', ApiKeyController::class);
    Route::post('api-keys/{apiKey}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('api-keys.toggle-status');
    Route::post('api-keys/{apiKey}/test-connection', [ApiKeyController::class, 'testConnection'])->name('api-keys.test-connection');
    
    // Asset trading routes
    Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('assets/portfolio', [AssetController::class, 'portfolio'])->name('assets.portfolio');
    Route::post('assets/buy', [AssetController::class, 'buy'])->name('assets.buy');
    Route::post('assets/sell', [AssetController::class, 'sell'])->name('assets.sell');
    Route::get('assets/transactions', [AssetController::class, 'transactions'])->name('assets.transactions');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
    Route::post('assets/sync', [AssetController::class, 'syncAssets'])->name('assets.sync');
    Route::post('assets/update-prices', [AssetController::class, 'updatePrices'])->name('assets.update-prices');
    Route::post('assets/balance', [AssetController::class, 'getBalance'])->name('assets.balance');
});

require __DIR__.'/auth.php';
