<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Checking Bot Configuration...\n\n";

$bots = FuturesTradingBot::where('is_active', true)->get();

foreach ($bots as $bot) {
    echo "📊 Bot: {$bot->name} (ID: {$bot->id})\n";
    echo "  Symbol: {$bot->symbol}\n";
    echo "  Exchange: " . ($bot->apiKey->exchange ?? 'N/A') . "\n";
    echo "  Max Position Size: {$bot->max_position_size}\n";
    echo "  Risk Percentage: {$bot->risk_percentage}%\n";
    echo "  Leverage: {$bot->leverage}x\n";
    echo "  Margin Type: {$bot->margin_type}\n";
    echo "  Status: " . ($bot->is_active ? 'Active' : 'Inactive') . "\n";
    echo "  Min Order Value: " . ($bot->min_order_value ?? 'Default (5)') . "\n";
    
    // Check what the code would use
    $configValue = config('micro_trading.risk_management.max_position_size', 1);
    $actualValue = $bot->max_position_size ?? $configValue;
    echo "  Config File Value: {$configValue}\n";
    echo "  Actual Used Value: {$actualValue}\n";
    echo "\n";
}

echo "Micro Trading Config: " . config('micro_trading.risk_management.max_position_size') . "\n";
echo "Total Active Bots: " . $bots->count() . "\n";
