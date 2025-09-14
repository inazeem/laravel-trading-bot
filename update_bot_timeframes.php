<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bot = \App\Models\FuturesTradingBot::find(7);

echo "=== UPDATING BOT TIMEFRAMES ===\n";
echo "Current timeframes: " . json_encode($bot->timeframes) . "\n";

// Update to multiple timeframes for better signal generation
$bot->timeframes = ['15m', '30m', '1h'];
$bot->save();

echo "New timeframes: " . json_encode($bot->timeframes) . "\n";
echo "✅ Bot timeframes updated successfully!\n";