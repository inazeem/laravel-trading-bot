<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradingBotController;
use App\Http\Controllers\FuturesTradingBotController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AssetController;

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

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
    Route::post('trading-bots/{tradingBot}/clear-logs', [TradingBotController::class, 'clearLogs'])->name('trading-bots.clear-logs');
    
    // Futures Trading Bot routes
    Route::resource('futures-bots', FuturesTradingBotController::class);
    Route::post('futures-bots/{futuresBot}/run', [FuturesTradingBotController::class, 'run'])->name('futures-bots.run');
    Route::post('futures-bots/{futuresBot}/toggle', [FuturesTradingBotController::class, 'toggle'])->name('futures-bots.toggle');
    Route::get('futures-bots/{futuresBot}/trades', [FuturesTradingBotController::class, 'trades'])->name('futures-bots.trades');
    Route::get('futures-bots/{futuresBot}/signals', [FuturesTradingBotController::class, 'signals'])->name('futures-bots.signals');
    Route::get('futures-bots/{futuresBot}/logs', [FuturesTradingBotController::class, 'logs'])->name('futures-bots.logs');
    Route::post('futures-bots/{futuresBot}/clear-logs', [FuturesTradingBotController::class, 'clearLogs'])->name('futures-bots.clear-logs');
    Route::post('futures-bots/{futuresBot}/close-position', [FuturesTradingBotController::class, 'closePosition'])->name('futures-bots.close-position');
    
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

// Custom endpoint for external cron services to trigger trading bot
Route::get('cron/trading-bot', function () {
    try {
        // Run the trading bot command
        \Artisan::call('trading:run', ['--all' => true]);
        
        $output = \Artisan::output();
        
        return response()->json([
            'success' => true,
            'message' => 'Trading bot executed successfully',
            'output' => $output,
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error executing trading bot',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->name('cron.trading-bot');

// Health check endpoint for uptime monitors
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'trading_bot_status' => 'active'
    ]);
})->name('health');

require __DIR__.'/auth.php';
