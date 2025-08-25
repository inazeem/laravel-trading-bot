<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Resetting Bot Cooldown\n";
echo "========================\n\n";

// Get the first active futures bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "❌ No active futures bot found. Please create one first.\n";
    exit(1);
}

echo "✅ Found active bot: {$bot->name}\n";
echo "📊 Symbol: {$bot->symbol}\n\n";

// Check cooldown status
$lastClosedTrade = $bot->trades()
    ->where('status', 'closed')
    ->latest('closed_at')
    ->first();

if ($lastClosedTrade && $lastClosedTrade->closed_at) {
    $cooldownEnd = $lastClosedTrade->closed_at->addMinutes(30);
    $now = now();
    
    if ($now->lt($cooldownEnd)) {
        $remainingMinutes = $now->diffInMinutes($cooldownEnd);
        echo "⏰ Cooldown active: {$remainingMinutes} minutes remaining\n";
        echo "   Last trade closed at: {$lastClosedTrade->closed_at}\n";
        echo "   Cooldown ends at: {$cooldownEnd}\n\n";
        
        echo "🔄 Resetting cooldown by updating last trade timestamp...\n";
        
        // Reset cooldown by updating the closed_at timestamp to be older
        $resetTime = now()->subMinutes(35); // Make it 35 minutes ago
        $lastClosedTrade->update(['closed_at' => $resetTime]);
        
        echo "✅ Cooldown reset! Last trade now shows as closed " . $resetTime->diffForHumans() . "\n";
        echo "🎯 Bot should now be able to place new trades\n";
    } else {
        echo "✅ Cooldown period already expired\n";
    }
} else {
    echo "ℹ️ No recent closed trades found - no cooldown to reset\n";
}

echo "\n🎉 Bot cooldown reset completed!\n";
