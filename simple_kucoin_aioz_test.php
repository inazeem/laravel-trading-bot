<?php

/**
 * Simple KuCoin AIOZ-USDT API Test
 * Tests the KuCoin futures API without Laravel dependencies
 */

echo "=== KuCoin AIOZ-USDT API Test ===\n\n";

// Configuration - Replace with your actual KuCoin API credentials
$apiKey = 'YOUR_KUCOIN_API_KEY';
$apiSecret = 'YOUR_KUCOIN_API_SECRET';
$passphrase = 'YOUR_KUCOIN_PASSPHRASE';

if ($apiKey === 'YOUR_KUCOIN_API_KEY') {
    echo "❌ Error: Please update the script with your KuCoin API credentials:\n";
    echo "   \$apiKey = 'your_actual_api_key';\n";
    echo "   \$apiSecret = 'your_actual_api_secret';\n";
    echo "   \$passphrase = 'your_actual_passphrase';\n\n";
    exit(1);
}

// Helper functions
function httpGet($url, $headers = []) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => implode("\r\n", $headers)
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : false;
}

function httpPost($url, $data, $headers = []) {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 30,
            'header' => implode("\r\n", $headers),
            'content' => json_encode($data)
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : false;
}

function createKuCoinSignature($method, $endpoint, $body, $timestamp, $secret) {
    $what = $timestamp . $method . $endpoint . $body;
    return base64_encode(hash_hmac('sha256', $what, $secret, true));
}

function createKuCoinPassphraseSignature($passphrase, $secret) {
    return base64_encode(hash_hmac('sha256', $passphrase, $secret, true));
}

function mapToKuCoinFuturesSymbol($symbol) {
    $parts = explode('-', strtoupper($symbol));
    $base = $parts[0] ?? $symbol;
    $quote = $parts[1] ?? 'USDT';

    if ($base === 'BTC') {
        $base = 'XBT';
    }

    return $base . $quote . 'M';
}

// Test 1: Check if AIOZ futures symbol exists
echo "1. Checking AIOZ futures symbol availability...\n";
$futuresSymbol = mapToKuCoinFuturesSymbol('AIOZ-USDT');
echo "   AIOZ-USDT -> {$futuresSymbol}\n";

// Test 2: Get current price
echo "\n2. Getting current price for {$futuresSymbol}...\n";
$tickerUrl = 'https://api-futures.kucoin.com/api/v1/ticker?symbol=' . urlencode($futuresSymbol);
$tickerData = httpGet($tickerUrl);

if (!$tickerData || !isset($tickerData['data']['price'])) {
    echo "❌ Error: Could not get price for {$futuresSymbol}\n";
    echo "Response: " . json_encode($tickerData) . "\n";
    echo "\nThis symbol might not be available on KuCoin futures.\n";
    echo "Try checking available symbols at: https://api-futures.kucoin.com/api/v1/contracts/active\n\n";
    exit(1);
}

$currentPrice = (float)$tickerData['data']['price'];
echo "✅ Current {$futuresSymbol} price: $" . number_format($currentPrice, 6) . "\n";

// Test 3: Get account balance (requires API credentials)
echo "\n3. Testing authenticated API - Getting futures balance...\n";

$timestamp = time() * 1000;
$endpoint = '/api/v1/account-overview';
$signature = createKuCoinSignature('GET', $endpoint, '', $timestamp, $apiSecret);
$passphraseSignature = createKuCoinPassphraseSignature($passphrase, $apiSecret);

$headers = [
    'KC-API-KEY: ' . $apiKey,
    'KC-API-SIGN: ' . $signature,
    'KC-API-TIMESTAMP: ' . $timestamp,
    'KC-API-PASSPHRASE: ' . $passphraseSignature,
    'KC-API-KEY-VERSION: 2',
    'Content-Type: application/json'
];

$balanceUrl = 'https://api-futures.kucoin.com' . $endpoint;
$balanceData = httpGet($balanceUrl, $headers);

if (!$balanceData || !isset($balanceData['data'])) {
    echo "❌ Error: Could not get account balance\n";
    echo "Response: " . json_encode($balanceData) . "\n";
    echo "Please check your API credentials and permissions\n\n";
    exit(1);
}

$usdtBalance = $balanceData['data']['availableBalance'] ?? 0;
echo "✅ Available USDT balance: $" . number_format($usdtBalance, 2) . "\n";

if ($usdtBalance < 10) {
    echo "❌ Error: Insufficient USDT balance for test order (minimum $10 recommended)\n\n";
    exit(1);
}

// Test 4: Prepare test order
echo "\n4. Preparing test order parameters...\n";

$testOrderValue = 10; // $10 test order
$leverage = 1; // 1x leverage for safety
$quantity = $testOrderValue / $currentPrice;

echo "   Order Value: $" . $testOrderValue . "\n";
echo "   Leverage: {$leverage}x\n";
echo "   Quantity: " . number_format($quantity, 3) . " contracts\n";
echo "   Side: buy (long)\n";

// Test 5: Ask for confirmation before placing real order
echo "\n⚠️  This will place a REAL order on KuCoin futures!\n";
echo "⚠️  Make sure you have set leverage to 1x in the KuCoin interface!\n";
echo "Do you want to proceed? (type 'yes' to confirm): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ Order cancelled by user\n\n";
    exit(0);
}

// Test 6: Place test order
echo "\n5. Placing test order on KuCoin futures...\n";

$timestamp = time() * 1000;
$orderData = [
    'clientOid' => 'test_' . $timestamp . '_' . rand(1000, 9999),
    'symbol' => $futuresSymbol,
    'type' => 'market',
    'side' => 'buy',
    'size' => $quantity,
    'timeInForce' => 'IOC',
];

$requestBody = json_encode($orderData);
$endpoint = '/api/v1/orders';
$signature = createKuCoinSignature('POST', $endpoint, $requestBody, $timestamp, $apiSecret);
$passphraseSignature = createKuCoinPassphraseSignature($passphrase, $apiSecret);

$headers = [
    'KC-API-KEY: ' . $apiKey,
    'KC-API-SIGN: ' . $signature,
    'KC-API-TIMESTAMP: ' . $timestamp,
    'KC-API-PASSPHRASE: ' . $passphraseSignature,
    'KC-API-KEY-VERSION: 2',
    'Content-Type: application/json'
];

$orderUrl = 'https://api-futures.kucoin.com' . $endpoint;
$orderResult = httpPost($orderUrl, $orderData, $headers);

if ($orderResult && isset($orderResult['data']['orderId'])) {
    echo "✅ SUCCESS! Test order placed:\n";
    echo "   Order ID: " . $orderResult['data']['orderId'] . "\n";
    echo "   Client OID: " . $orderData['clientOid'] . "\n";
    echo "   Symbol: " . $futuresSymbol . "\n";
    echo "   Side: buy\n";
    echo "   Quantity: " . $quantity . "\n";
    echo "   Type: market\n";
    
    echo "\n🎉 KuCoin futures integration is working!\n";
    echo "📋 Full response: " . json_encode($orderResult) . "\n";
    
} else {
    echo "❌ FAILED! Order was not placed\n";
    echo "Response: " . json_encode($orderResult) . "\n";
    
    if (isset($orderResult['msg'])) {
        echo "Error message: " . $orderResult['msg'] . "\n";
    }
}

echo "\n=== Test Complete ===\n";
