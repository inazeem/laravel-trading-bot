<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FuturesTradingBot;

echo "=== UPDATING BOT TIMEFRAMES ===\n\n";

// Get the active bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "No active bot found!\n";
    exit;
}

echo "Current bot: {$bot->name} ({$bot->symbol})\n";
echo "Current timeframes: " . implode(', ', $bot->timeframes) . "\n\n";

// Update to multiple timeframes
$newTimeframes = ['5m', '15m', '1h'];
$bot->timeframes = $newTimeframes;
$bot->save();

echo "✅ Updated timeframes to: " . implode(', ', $bot->timeframes) . "\n\n";

echo "=== BENEFITS OF MULTIPLE TIMEFRAMES ===\n";
echo "• 5m: Captures short-term momentum and quick reversals\n";
echo "• 15m: Medium-term structure and trend changes\n";
echo "• 1h: Higher timeframe context and major support/resistance\n";
echo "\nThis will provide better signal diversity and catch both bullish and bearish opportunities!\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Run the bot: php artisan futures:run --all\n";
echo "2. Monitor for more balanced signals\n";
echo "3. Check signal diversity across timeframes\n\n";

echo "=== UPDATE COMPLETED ===\n";
