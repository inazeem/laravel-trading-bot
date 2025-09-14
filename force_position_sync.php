<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);
$exchangeService = new \App\Services\ExchangeService($bot->apiKey);

echo "=== FORCING POSITION SYNC ===\n";

// Get positions from Binance
$positions = $exchangeService->getOpenPositions($bot->symbol);
echo "Found " . count($positions) . " positions on Binance\n";

foreach($positions as $position) {
    echo "\nProcessing position:\n";
    echo "  Symbol: {$position['symbol']}\n";
    echo "  Side: {$position['side']}\n";
    
    // Normalize symbol for database
    $dbSymbol = str_replace('USDT', '-USDT', $position['symbol']);
    echo "  DB Symbol: {$dbSymbol}\n";
    
    // Check if we already have this trade in database
    $existingTrade = \App\Models\FuturesTrade::where('futures_trading_bot_id', $bot->id)
        ->where('symbol', $dbSymbol)
        ->where('side', $position['side'])
        ->where('status', 'open')
        ->first();
    
    if ($existingTrade) {
        echo "  ✅ Found existing trade ID: {$existingTrade->id}\n";
        
        // Update the existing trade
        $existingTrade->update([
            'quantity' => $position['quantity'],
            'entry_price' => $position['entry_price'],
            'unrealized_pnl' => $position['unrealized_pnl'],
            'leverage' => $position['leverage'],
            'margin_type' => $position['margin_type']
        ]);
        
        echo "  📝 Updated existing trade with current data\n";
    } else {
        echo "  ❌ No existing trade found, creating new one\n";
        
        // Create new trade record
        $newTrade = \App\Models\FuturesTrade::create([
            'futures_trading_bot_id' => $bot->id,
            'symbol' => $dbSymbol,
            'side' => $position['side'],
            'quantity' => $position['quantity'],
            'entry_price' => $position['entry_price'],
            'unrealized_pnl' => $position['unrealized_pnl'],
            'leverage' => $position['leverage'],
            'margin_type' => $position['margin_type'],
            'status' => 'open',
            'opened_at' => now(),
        ]);
        
        echo "  ✅ Created new trade ID: {$newTrade->id}\n";
    }
}

echo "\n=== VERIFICATION ===\n";
$openTrades = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->get();

echo "Open trades in database: " . $openTrades->count() . "\n";
foreach($openTrades as $trade) {
    echo "  Trade ID: {$trade->id}, Symbol: {$trade->symbol}, Side: {$trade->side}, Quantity: {$trade->quantity}, Entry: {$trade->entry_price}\n";
}
