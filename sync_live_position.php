<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Models\FuturesTrade;
use App\Services\ExchangeService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔄 Syncing Live KuCoin Position to Database...\n\n";

try {
    // Find the KuCoin bot for SOL-USDT
    $bot = FuturesTradingBot::where('symbol', 'SOL-USDT')
        ->whereHas('apiKey', function($query) {
            $query->where('exchange', 'kucoin');
        })
        ->where('is_active', true)
        ->first();
    
    if (!$bot) {
        echo "❌ No active KuCoin bot found for SOL-USDT\n";
        exit(1);
    }
    
    echo "✅ Found bot: {$bot->name} (ID: {$bot->id})\n";
    
    $exchangeService = new ExchangeService($bot->apiKey);
    
    // Get positions from exchange
    $positions = $exchangeService->getOpenPositions();
    echo "📊 Found " . count($positions) . " position(s) on exchange\n\n";
    
    foreach ($positions as $position) {
        echo "Position Details:\n";
        echo "  Symbol: {$position['symbol']}\n";
        echo "  Side: {$position['side']}\n";
        echo "  Quantity: {$position['quantity']}\n";
        echo "  Entry Price: {$position['entry_price']}\n";
        echo "  Unrealized PnL: {$position['unrealized_pnl']}\n";
        echo "  Leverage: {$position['leverage']}\n";
        echo "  Margin Type: {$position['margin_type']}\n";
        
        // Convert symbol to database format
        $dbSymbol = $position['symbol'];
        if (str_ends_with($dbSymbol, 'USDTM')) {
            $dbSymbol = str_replace('USDTM', '', $dbSymbol) . '-USDT';
        }
        
        echo "  DB Symbol: {$dbSymbol}\n\n";
        
        // Check if this matches our bot's symbol
        if ($dbSymbol === $bot->symbol) {
            echo "✅ Position matches bot symbol - creating trade record...\n";
            
            // Create a new trade record for this position
            $trade = FuturesTrade::create([
                'futures_trading_bot_id' => $bot->id,
                'symbol' => $dbSymbol,
                'side' => $position['side'],
                'quantity' => $position['quantity'],
                'entry_price' => $position['entry_price'],
                'unrealized_pnl' => $position['unrealized_pnl'],
                'leverage' => $position['leverage'],
                'margin_type' => $position['margin_type'],
                'status' => 'open',
                'order_id' => 'imported_' . time(),
                'opened_at' => now(),
                'created_at' => now(),
            ]);
            
            echo "✅ Created trade record (ID: {$trade->id})\n";
            echo "✅ Position is now tracked in the bot system\n";
            echo "✅ P&L will update every 2 minutes via scheduled sync\n";
        } else {
            echo "ℹ️ Position symbol {$dbSymbol} doesn't match bot symbol {$bot->symbol}\n";
        }
    }
    
    echo "\n🎯 Sync Complete!\n";
    echo "Your KuCoin position is now properly tracked and P&L will update automatically.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
