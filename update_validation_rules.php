<?php

// Read the controller file
$controllerPath = 'app/Http/Controllers/FuturesTradingBotController.php';
$content = file_get_contents($controllerPath);

// Update the validation rules
$content = str_replace("'timeframes.*' => 'in:15m,30m,1h,4h'", "'timeframes.*' => 'in:15m,30m,1h'", $content);

// Write back to the file
file_put_contents($controllerPath, $content);

echo "✅ Validation rules updated successfully!\n";
echo "📊 Timeframes validation changed from 'in:15m,30m,1h,4h' to 'in:15m,30m,1h'\n";
echo "🎯 Admin panel will now only allow 15m, 30m, 1h timeframes for micro trading\n";

