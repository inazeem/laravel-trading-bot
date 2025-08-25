<?php

require_once 'vendor/autoload.php';

use App\Models\FuturesTradingBot;
use App\Services\FuturesTradingBotService;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Testing High Strength Requirement (90%+)\n";
echo "==========================================\n\n";

// Get the first active futures bot
$bot = FuturesTradingBot::where('is_active', true)->first();

if (!$bot) {
    echo "❌ No active futures bot found. Please create one first.\n";
    exit(1);
}

echo "✅ Found active bot: {$bot->name}\n";
echo "📊 Symbol: {$bot->symbol}\n";
echo "⚙️ Leverage: {$bot->leverage}x\n";
echo "💰 Margin Type: {$bot->margin_type}\n\n";

// Test the high strength requirement
echo "🔍 Testing High Strength Requirement...\n";

// Get the current configuration
$requiredStrength = config('micro_trading.signal_settings.high_strength_requirement', 0.90);
echo "🎯 Required Strength: " . ($requiredStrength * 100) . "%\n\n";

// Test different signal strengths
$testSignals = [
    ['strength' => 0.85, 'type' => 'OrderBlock_Support', 'direction' => 'bullish', 'timeframe' => '5m'],
    ['strength' => 0.92, 'type' => 'OrderBlock_Resistance', 'direction' => 'bearish', 'timeframe' => '15m'],
    ['strength' => 0.88, 'type' => 'BOS', 'direction' => 'bullish', 'timeframe' => '1m'],
    ['strength' => 0.95, 'type' => 'OrderBlock_Breakout', 'direction' => 'bullish', 'timeframe' => '5m'],
    ['strength' => 0.75, 'type' => 'CHoCH', 'direction' => 'bearish', 'timeframe' => '15m'],
];

echo "📊 Testing Signal Filtering:\n";
echo "============================\n";

foreach ($testSignals as $index => $signal) {
    $strength = $signal['strength'];
    $percentage = $strength * 100;
    $status = $strength >= $requiredStrength ? "✅ PASS" : "❌ REJECT";
    
    echo "Signal {$index}: {$percentage}% strength - {$status}\n";
    echo "   Type: {$signal['type']}, Direction: {$signal['direction']}, Timeframe: {$signal['timeframe']}\n";
    
    if ($strength >= $requiredStrength) {
        echo "   ✅ Would be accepted for trade placement\n";
    } else {
        echo "   ❌ Would be rejected - strength too low\n";
    }
    echo "\n";
}

// Count how many signals would pass
$passingSignals = array_filter($testSignals, function($signal) use ($requiredStrength) {
    return ($signal['strength'] ?? 0) >= $requiredStrength;
});

echo "📋 Summary:\n";
echo "===========\n";
echo "Total test signals: " . count($testSignals) . "\n";
echo "Signals that would pass: " . count($passingSignals) . "\n";
echo "Signals that would be rejected: " . (count($testSignals) - count($passingSignals)) . "\n";
echo "Pass rate: " . round((count($passingSignals) / count($testSignals)) * 100, 1) . "%\n\n";

if (count($passingSignals) > 0) {
    echo "🎯 Signals that would trigger trades:\n";
    foreach ($passingSignals as $index => $signal) {
        $percentage = $signal['strength'] * 100;
        echo "   - Signal {$index}: {$percentage}% ({$signal['type']} - {$signal['direction']})\n";
    }
} else {
    echo "⚠️ No signals would trigger trades with current strength requirement\n";
}

echo "\n🔧 Configuration Details:\n";
echo "=======================\n";
echo "Config file: config/micro_trading.php\n";
echo "Setting: micro_trading.signal_settings.high_strength_requirement\n";
echo "Current value: {$requiredStrength} (" . ($requiredStrength * 100) . "%)\n\n";

echo "💡 To change the requirement:\n";
echo "1. Edit config/micro_trading.php\n";
echo "2. Change 'high_strength_requirement' value\n";
echo "3. Clear config cache: php artisan config:clear\n\n";

echo "🎉 High strength requirement test completed!\n";
