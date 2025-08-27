<?php

// Read the controller file
$controllerPath = 'app/Http/Controllers/FuturesTradingBotController.php';
$content = file_get_contents($controllerPath);

// Update the timeframes arrays
$content = str_replace("['1m', '5m', '15m']", "['15m', '30m', '1h', '4h']", $content);

// Update the validation rules
$content = str_replace("'timeframes.*' => 'in:1m,5m,15m'", "'timeframes.*' => 'in:15m,30m,1h,4h'", $content);

// Write back to the file
file_put_contents($controllerPath, $content);

echo "✅ Controller updated successfully!\n";
echo "📊 Timeframes changed from ['1m', '5m', '15m'] to ['15m', '30m', '1h', '4h']\n";
echo "🔧 Validation rules updated to allow 15m, 30m, 1h, 4h\n";
echo "🎯 Analysis Timeframes in admin panel will now show: 15m, 30m, 1h, 4h\n";

