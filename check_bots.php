<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FUTURES BOTS ===\n";
$futuresBots = \App\Models\FuturesTradingBot::all();
foreach($futuresBots as $bot) {
    echo "ID: {$bot->id}, Name: {$bot->name}, Symbol: {$bot->symbol}, Status: {$bot->status}\n";
}

echo "\n=== SPOT BOTS ===\n";
$spotBots = \App\Models\TradingBot::all();
foreach($spotBots as $bot) {
    echo "ID: {$bot->id}, Name: {$bot->name}, Symbol: {$bot->symbol}, Status: {$bot->status}\n";
}
