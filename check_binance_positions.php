<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);
$exchangeService = new \App\Services\ExchangeService($bot->apiKey);

echo "=== CHECKING BINANCE POSITIONS ===\n";

// Check Binance positions directly
$positions = $exchangeService->getOpenPositions($bot->symbol);
echo "Open positions on Binance: " . count($positions) . "\n";

foreach($positions as $position) {
    echo "Position:\n";
    echo "  Symbol: {$position['symbol']}\n";
    echo "  Side: {$position['side']}\n";
    echo "  Quantity: {$position['quantity']}\n";
    echo "  Entry Price: {$position['entry_price']}\n";
    echo "  Unrealized PnL: {$position['unrealized_pnl']}\n";
    echo "  Leverage: {$position['leverage']}\n";
    echo "  Margin Type: {$position['margin_type']}\n\n";
}

echo "=== CHECKING LOCAL DATABASE ===\n";
$openTrades = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->where('status', 'open')
    ->get();

echo "Open trades in database: " . $openTrades->count() . "\n";

foreach($openTrades as $trade) {
    echo "Trade ID: {$trade->id}\n";
    echo "  Symbol: {$trade->symbol}\n";
    echo "  Side: {$trade->side}\n";
    echo "  Quantity: {$trade->quantity}\n";
    echo "  Entry Price: {$trade->entry_price}\n";
    echo "  Status: {$trade->status}\n";
    echo "  Opened At: {$trade->opened_at}\n\n";
}

echo "=== RECENT TRADES ===\n";
$recentTrades = \App\Models\FuturesTrade::where('futures_trading_bot_id', 7)
    ->latest()
    ->take(5)
    ->get();

foreach($recentTrades as $trade) {
    echo "Trade ID: {$trade->id}, Symbol: {$trade->symbol}, Side: {$trade->side}, Status: {$trade->status}, Opened: {$trade->opened_at}\n";
}
