<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Updating Bot Configuration...\n\n";

// You can specify which bot to update
$botId = 8; // KuCoin SOL-USDT bot
$newMaxPositionSize = 30;

$bot = FuturesTradingBot::find($botId);

if (!$bot) {
    echo "❌ Bot with ID {$botId} not found\n";
    exit(1);
}

echo "📊 Current Bot Configuration:\n";
echo "  Name: {$bot->name}\n";
echo "  Symbol: {$bot->symbol}\n";
echo "  Exchange: " . ($bot->apiKey->exchange ?? 'N/A') . "\n";
echo "  Current Max Position Size: {$bot->max_position_size}\n\n";

echo "🔄 Updating max_position_size to {$newMaxPositionSize}...\n";

$bot->update([
    'max_position_size' => $newMaxPositionSize
]);

echo "✅ Successfully updated!\n\n";

echo "📊 New Bot Configuration:\n";
$bot->refresh();
echo "  Name: {$bot->name}\n";
echo "  Symbol: {$bot->symbol}\n";
echo "  Max Position Size: {$bot->max_position_size}\n";

// Calculate minimum position size with new config
$testPrice = 200; // Assume SOL at $200
$minNotionalValue = ($bot->min_order_value ?? 5) + 0.5;
$requiredMinPosition = $minNotionalValue / $testPrice;
$maxPositionNotional = $bot->max_position_size * $testPrice;

echo "\n🧮 Configuration Test (at $200 SOL price):\n";
echo "  Max Position Notional: \${$maxPositionNotional} USDT\n";
echo "  Min Required Notional: \${$minNotionalValue} USDT\n";
echo "  Min Position Size: {$requiredMinPosition}\n";
echo "  Status: " . ($maxPositionNotional >= $minNotionalValue ? "✅ Valid" : "❌ Too Small") . "\n";

echo "\nBot configuration updated successfully!\n";