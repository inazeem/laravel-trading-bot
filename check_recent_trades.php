<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTrade;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Checking Recent SOL-USDT Trades...\n\n";

$trades = FuturesTrade::where('symbol', 'SOL-USDT')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get(['id', 'symbol', 'side', 'status', 'quantity', 'entry_price', 'unrealized_pnl', 'created_at', 'closed_at']);

echo "Recent trades:\n";
foreach ($trades as $trade) {
    echo "ID: {$trade->id} | {$trade->symbol} | {$trade->side} | Status: {$trade->status}\n";
    echo "  Qty: {$trade->quantity} | Entry: {$trade->entry_price} | PnL: {$trade->unrealized_pnl}\n";
    echo "  Created: {$trade->created_at} | Closed: {$trade->closed_at}\n\n";
}

echo "Total SOL-USDT trades: " . FuturesTrade::where('symbol', 'SOL-USDT')->count() . "\n";
echo "Open SOL-USDT trades: " . FuturesTrade::where('symbol', 'SOL-USDT')->where('status', 'open')->count() . "\n";
echo "Closed SOL-USDT trades: " . FuturesTrade::where('symbol', 'SOL-USDT')->where('status', 'closed')->count() . "\n";
