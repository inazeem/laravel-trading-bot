<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);

echo "=== BOT CONFIGURATION ===\n";
echo "Bot ID: {$bot->id}\n";
echo "Name: {$bot->name}\n";
echo "Symbol: {$bot->symbol}\n";
echo "Timeframes: " . json_encode($bot->timeframes) . "\n";
echo "Status: {$bot->status}\n";

echo "\n=== SIGNAL REQUIREMENTS ===\n";
echo "Min confluence config: " . config('micro_trading.signal_settings.min_confluence') . "\n";
echo "Strength requirement: " . config('micro_trading.signal_settings.high_strength_requirement') . "\n";
echo "Min strength threshold: " . config('micro_trading.signal_settings.min_strength_threshold') . "\n";

echo "\n=== PROBLEM ANALYSIS ===\n";
$timeframes = $bot->timeframes;
$minConfluence = config('micro_trading.signal_settings.min_confluence', 2);

echo "Bot has " . count($timeframes) . " timeframe(s): " . implode(', ', $timeframes) . "\n";
echo "Required confluence: {$minConfluence}\n";

if (count($timeframes) < $minConfluence) {
    echo "❌ PROBLEM: Bot cannot achieve required confluence with only " . count($timeframes) . " timeframe(s)\n";
    echo "   Confluence requires signals from multiple timeframes\n";
    echo "   With only 1 timeframe, max confluence = 0\n";
} else {
    echo "✅ Confluence requirement can be met\n";
}
