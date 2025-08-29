<?php

/**
 * Check available KuCoin futures symbols
 */

echo "=== KuCoin Available Futures Symbols ===\n\n";

function httpGet($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => 'User-Agent: PHP Script'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : false;
}

// Get all active futures contracts
echo "Fetching all available KuCoin futures symbols...\n\n";
$url = 'https://api-futures.kucoin.com/api/v1/contracts/active';
$data = httpGet($url);

if (!$data || !isset($data['data'])) {
    echo "❌ Failed to fetch futures symbols\n";
    exit(1);
}

echo "✅ Found " . count($data['data']) . " active futures contracts\n\n";

// Filter and display USDT-margined contracts
$usdtContracts = [];
foreach ($data['data'] as $contract) {
    if (strpos($contract['symbol'], 'USDT') !== false) {
        $usdtContracts[] = $contract;
    }
}

echo "USDT-margined futures contracts:\n";
echo str_repeat("-", 80) . "\n";
printf("%-20s %-15s %-15s %-10s\n", "Symbol", "Base", "Quote", "Status");
echo str_repeat("-", 80) . "\n";

foreach ($usdtContracts as $contract) {
    printf("%-20s %-15s %-15s %-10s\n", 
        $contract['symbol'], 
        $contract['baseCurrency'], 
        $contract['quoteCurrency'],
        $contract['status']
    );
}

echo str_repeat("-", 80) . "\n";
echo "Total USDT contracts: " . count($usdtContracts) . "\n\n";

// Check specifically for AIOZ
$aiozFound = false;
foreach ($usdtContracts as $contract) {
    if (strpos($contract['symbol'], 'AIOZ') !== false) {
        echo "✅ AIOZ futures contract found: " . $contract['symbol'] . "\n";
        echo "   Base: " . $contract['baseCurrency'] . "\n";
        echo "   Quote: " . $contract['quoteCurrency'] . "\n";
        echo "   Status: " . $contract['status'] . "\n";
        echo "   Type: " . $contract['type'] . "\n";
        $aiozFound = true;
        break;
    }
}

if (!$aiozFound) {
    echo "❌ AIOZ futures contract not found\n";
    echo "Available alternatives with 'AI' in the name:\n";
    
    foreach ($usdtContracts as $contract) {
        if (stripos($contract['symbol'], 'AI') !== false) {
            echo "   - " . $contract['symbol'] . " (" . $contract['baseCurrency'] . ")\n";
        }
    }
}

echo "\n=== Symbol Check Complete ===\n";
