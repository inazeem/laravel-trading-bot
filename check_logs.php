<?php

/**
 * Check Laravel Logs
 * 
 * Check the Laravel logs for Binance API responses
 */

$logFile = 'storage/logs/laravel.log';

if (file_exists($logFile)) {
    echo "📋 Recent Laravel Logs (last 50 lines):\n";
    echo "=====================================\n\n";
    
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50);
    
    foreach ($recentLines as $line) {
        if (strpos($line, 'Binance') !== false || strpos($line, 'API') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "❌ Log file not found: {$logFile}\n";
}
