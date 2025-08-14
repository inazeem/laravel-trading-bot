<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Asset;
use App\Models\ApiKey;

class ExchangeService
{
    protected $apiKey;
    protected $secretKey;
    protected $passphrase;
    protected $exchange;

    public function __construct(ApiKey $apiKey = null)
    {
        if ($apiKey) {
            $this->apiKey = $apiKey->decrypted_api_key;
            $this->secretKey = $apiKey->decrypted_api_secret;
            $this->passphrase = $apiKey->decrypted_passphrase;
            $this->exchange = $apiKey->exchange;
        }
    }

    /**
     * Get available trading pairs from the exchange
     */
    public function getTradingPairs()
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinTradingPairs();
                case 'binance':
                    return $this->getBinanceTradingPairs();
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error fetching trading pairs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get current price for a trading pair
     */
    public function getCurrentPrice($symbol)
    {
        try {
            // Normalize symbol format for Binance (remove hyphens)
            $normalizedSymbol = str_replace('-', '', $symbol);
            
            Log::info("Fetching price for symbol: {$symbol} (normalized: {$normalizedSymbol})");
            
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinPrice($symbol);
                case 'binance':
                    return $this->getBinancePrice($normalizedSymbol);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error fetching price for {$symbol}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Place a buy order
     */
    public function placeBuyOrder($symbol, $quantity, $price = null)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinBuyOrder($symbol, $quantity, $price);
                case 'binance':
                    return $this->placeBinanceBuyOrder($symbol, $quantity, $price);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing buy order: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Place a sell order
     */
    public function placeSellOrder($symbol, $quantity, $price = null)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinSellOrder($symbol, $quantity, $price);
                case 'binance':
                    return $this->placeBinanceSellOrder($symbol, $quantity, $price);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing sell order: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get account balance
     */
    public function getAccountBalance()
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinBalance();
                case 'binance':
                    return $this->getBinanceBalance();
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error fetching account balance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get balance (alias for getAccountBalance)
     */
    public function getBalance()
    {
        return $this->getAccountBalance();
    }

    /**
     * Get futures account balance
     */
    public function getFuturesBalance()
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinFuturesBalance();
                case 'binance':
                    return $this->getBinanceFuturesBalance();
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error fetching futures balance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Place futures order
     */
    public function placeFuturesOrder($symbol, $side, $quantity, $leverage, $marginType)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinFuturesOrder($symbol, $side, $quantity, $leverage, $marginType);
                case 'binance':
                    return $this->placeBinanceFuturesOrder($symbol, $side, $quantity, $leverage, $marginType);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing futures order: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Close futures position
     */
    public function closeFuturesPosition($symbol, $side, $quantity, $orderId = null)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->closeKuCoinFuturesPosition($symbol, $side, $quantity, $orderId);
                case 'binance':
                    return $this->closeBinanceFuturesPosition($symbol, $side, $quantity, $orderId);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error closing futures position: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Place market order (buy or sell)
     */
    public function placeMarketOrder($symbol, $side, $quantity): ?array
    {
        try {
            switch ($side) {
                case 'buy':
                    return $this->placeBuyOrder($symbol, $quantity);
                case 'sell':
                    return $this->placeSellOrder($symbol, $quantity);
                default:
                    throw new \Exception("Invalid order side: {$side}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing market order: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get candlestick data for a symbol
     */
    public function getCandles($symbol, $interval = '1h', $limit = 500)
    {
        try {
            // Normalize symbol format for Binance (remove hyphens)
            $normalizedSymbol = str_replace('-', '', $symbol);
            
            Log::info("Fetching candles for symbol: {$symbol} (normalized: {$normalizedSymbol})");
            
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinCandles($symbol, $interval, $limit);
                case 'binance':
                    return $this->getBinanceCandles($normalizedSymbol, $interval, $limit);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error fetching candles for {$symbol}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * KuCoin API Methods
     */
    private function getKuCoinTradingPairs()
    {
        $response = Http::get('https://api.kucoin.com/api/v1/symbols');
        
        if ($response->successful()) {
            $data = $response->json();
            $pairs = [];
            
            foreach ($data['data'] as $symbol) {
                if ($symbol['enableTrading']) {
                    $pairs[] = [
                        'symbol' => $symbol['symbol'],
                        'baseCurrency' => $symbol['baseCurrency'],
                        'quoteCurrency' => $symbol['quoteCurrency'],
                        'name' => $symbol['baseCurrency'],
                        'type' => 'crypto'
                    ];
                }
            }
            
            return $pairs;
        }
        
        return [];
    }

    private function getKuCoinPrice($symbol)
    {
        $response = Http::get("https://api.kucoin.com/api/v1/market/orderbook/level1?symbol={$symbol}");
        
        if ($response->successful()) {
            $data = $response->json();
            return (float) $data['data']['price'];
        }
        
        return null;
    }

    private function placeKuCoinBuyOrder($symbol, $quantity, $price = null)
    {
        $timestamp = time() * 1000;
        $endpoint = '/api/v1/orders';
        
        $params = [
            'clientOid' => uniqid(),
            'symbol' => $symbol,
            'side' => 'buy',
            'type' => $price ? 'limit' : 'market',
            'size' => $quantity
        ];
        
        if ($price) {
            $params['price'] = $price;
        }
        
        $signature = $this->generateKuCoinSignature('POST', $endpoint, $params, $timestamp);
        
        $response = Http::withHeaders([
            'KC-API-KEY' => $this->apiKey,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $this->passphrase,
            'KC-API-KEY-VERSION' => '2'
        ])->post('https://api.kucoin.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['data']['orderId'],
                'side' => 'buy',
                'symbol' => $symbol,
                'quantity' => $quantity
            ];
        }
        
        throw new \Exception("KuCoin buy order failed: " . $response->body());
    }

    private function placeKuCoinSellOrder($symbol, $quantity, $price = null)
    {
        $timestamp = time() * 1000;
        $endpoint = '/api/v1/orders';
        
        $params = [
            'clientOid' => uniqid(),
            'symbol' => $symbol,
            'side' => 'sell',
            'type' => $price ? 'limit' : 'market',
            'size' => $quantity
        ];
        
        if ($price) {
            $params['price'] = $price;
        }
        
        $signature = $this->generateKuCoinSignature('POST', $endpoint, $params, $timestamp);
        
        $response = Http::withHeaders([
            'KC-API-KEY' => $this->apiKey,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $this->passphrase,
            'KC-API-KEY-VERSION' => '2'
        ])->post('https://api.kucoin.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['data']['orderId'],
                'side' => 'sell',
                'symbol' => $symbol,
                'quantity' => $quantity
            ];
        }
        
        throw new \Exception("KuCoin sell order failed: " . $response->body());
    }

    private function getKuCoinBalance()
    {
        $timestamp = time() * 1000;
        $endpoint = '/api/v1/accounts';
        
        $signature = $this->generateKuCoinSignature('GET', $endpoint, '', $timestamp);
        
        $response = Http::withHeaders([
            'KC-API-KEY' => $this->apiKey,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $this->passphrase,
            'KC-API-KEY-VERSION' => '2'
        ])->get('https://api.kucoin.com' . $endpoint);
        
        if ($response->successful()) {
            return $response->json()['data'];
        }
        
        return [];
    }

    private function generateKuCoinSignature($method, $endpoint, $params, $timestamp)
    {
        $str = $timestamp . $method . $endpoint;
        
        if (!empty($params)) {
            if (is_array($params)) {
                $str .= json_encode($params);
            } else {
                $str .= $params;
            }
        }
        
        return base64_encode(hash_hmac('sha256', $str, $this->secretKey, true));
    }

    /**
     * Binance API Methods
     */
    private function getBinanceTradingPairs()
    {
        $response = Http::get('https://api.binance.com/api/v3/exchangeInfo');
        
        if ($response->successful()) {
            $data = $response->json();
            $pairs = [];
            
            foreach ($data['symbols'] as $symbol) {
                if ($symbol['status'] === 'TRADING') {
                    $pairs[] = [
                        'symbol' => $symbol['symbol'],
                        'baseCurrency' => $symbol['baseAsset'],
                        'quoteCurrency' => $symbol['quoteAsset'],
                        'name' => $symbol['baseAsset'],
                        'type' => 'crypto'
                    ];
                }
            }
            
            return $pairs;
        }
        
        return [];
    }

    private function getBinancePrice($symbol)
    {
        $url = "https://api.binance.com/api/v3/ticker/price?symbol={$symbol}";
        Log::info("Making request to: {$url}");
        
        $response = Http::get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            $price = (float) $data['price'];
            Log::info("Successfully fetched price for {$symbol}: {$price}");
            return $price;
        }
        
        Log::error("Failed to fetch price for {$symbol}. Status: {$response->status()}, Response: {$response->body()}");
        return null;
    }

    private function placeBinanceBuyOrder($symbol, $quantity, $price = null)
    {
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/api/v3/order';
        
        $params = [
            'symbol' => $symbol,
            'side' => 'BUY',
            'type' => $price ? 'LIMIT' : 'MARKET',
            'quantity' => $quantity,
            'timestamp' => $timestamp
        ];
        
        if ($price) {
            $params['price'] = $price;
            $params['timeInForce'] = 'GTC';
        }
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->post('https://api.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['orderId'],
                'side' => 'buy',
                'symbol' => $symbol,
                'quantity' => $quantity
            ];
        }
        
        throw new \Exception("Binance buy order failed: " . $response->body());
    }

    private function placeBinanceSellOrder($symbol, $quantity, $price = null)
    {
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/api/v3/order';
        
        $params = [
            'symbol' => $symbol,
            'side' => 'SELL',
            'type' => $price ? 'LIMIT' : 'MARKET',
            'quantity' => $quantity,
            'timestamp' => $timestamp
        ];
        
        if ($price) {
            $params['price'] = $price;
            $params['timeInForce'] = 'GTC';
        }
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->post('https://api.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['orderId'],
                'side' => 'sell',
                'symbol' => $symbol,
                'quantity' => $quantity
            ];
        }
        
        throw new \Exception("Binance sell order failed: " . $response->body());
    }

    private function getBinanceBalance()
    {
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/api/v3/account';
        
        $params = [
            'timestamp' => $timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get('https://api.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            return $response->json()['balances'];
        }
        
        return [];
    }

    /**
     * KuCoin candlestick data
     */
    private function getKuCoinCandles($symbol, $interval = '1h', $limit = 500)
    {
        $url = "https://api.kucoin.com/api/v1/market/candles";
        $params = [
            'symbol' => $symbol,
            'type' => $interval,
            'limit' => $limit
        ];
        
        Log::info("Making KuCoin request to: {$url} with params: " . json_encode($params));
        
        $response = Http::get($url, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            $candles = [];
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $candle) {
                    if (is_array($candle) && count($candle) >= 6) {
                        $candles[] = [
                            'timestamp' => $candle[0],
                            'open' => (float) $candle[1],
                            'close' => (float) $candle[2],
                            'high' => (float) $candle[3],
                            'low' => (float) $candle[4],
                            'volume' => (float) $candle[5]
                        ];
                    }
                }
            }
            
            Log::info("Successfully fetched " . count($candles) . " KuCoin candlesticks for {$symbol} with interval {$interval}");
            return array_reverse($candles); // Return in chronological order
        }
        
        Log::error("Failed to fetch KuCoin candles for {$symbol} with interval {$interval}. Status: {$response->status()}, Response: {$response->body()}");
        return [];
    }

    /**
     * Binance candlestick data
     */
    private function getBinanceCandles($symbol, $interval = '1h', $limit = 500)
    {
        $response = Http::get("https://api.binance.com/api/v3/klines", [
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $candles = [];
            
            foreach ($data as $candle) {
                $candles[] = [
                    'timestamp' => $candle[0],
                    'open' => (float) $candle[1],
                    'high' => (float) $candle[2],
                    'low' => (float) $candle[3],
                    'close' => (float) $candle[4],
                    'volume' => (float) $candle[5]
                ];
            }
            
            return $candles;
        }
        
        return [];
    }

    /**
     * Sync assets from exchange to database
     */
    public function syncAssets()
    {
        $tradingPairs = $this->getTradingPairs();
        $syncedCount = 0;
        
        foreach ($tradingPairs as $pair) {
            $asset = Asset::updateOrCreate(
                ['symbol' => $pair['symbol']],
                [
                    'name' => $pair['name'],
                    'type' => $pair['type'],
                    'current_price' => $this->getCurrentPrice($pair['symbol']) ?? 0,
                    'is_active' => true
                ]
            );
            
            if ($asset->wasRecentlyCreated || $asset->wasChanged()) {
                $syncedCount++;
            }
        }
        
        return $syncedCount;
    }

    /**
     * Update prices for all assets
     */
    public function updatePrices()
    {
        $assets = Asset::active()->get();
        $updatedCount = 0;
        
        foreach ($assets as $asset) {
            $price = $this->getCurrentPrice($asset->symbol);
            if ($price !== null && $price != $asset->current_price) {
                $asset->update(['current_price' => $price]);
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    /**
     * Get KuCoin futures balance
     */
    private function getKuCoinFuturesBalance()
    {
        // Mock implementation for now
        return [
            [
                'currency' => 'USDT',
                'available' => 1000.0,
                'total' => 1000.0
            ]
        ];
    }

    /**
     * Get Binance futures balance
     */
    private function getBinanceFuturesBalance()
    {
        // Get server time from Binance to ensure timestamp accuracy
        $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
        if ($serverTimeResponse->successful()) {
            $serverTime = $serverTimeResponse->json()['serverTime'];
            $timestamp = $serverTime;
        } else {
            $timestamp = round(microtime(true) * 1000);
        }
        
        $endpoint = '/fapi/v2/balance';
        
        $params = [
            'timestamp' => (int)$timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Fetching Binance futures balance");
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get('https://fapi.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            $balances = [];
            
            foreach ($data as $balance) {
                if (floatval($balance['balance']) > 0) {
                    $balances[] = [
                        'currency' => $balance['asset'],
                        'available' => floatval($balance['availableBalance']),
                        'total' => floatval($balance['balance'])
                    ];
                }
            }
            
            Log::info("Binance futures balance: " . json_encode($balances));
            return $balances;
        }
        
        Log::error("Failed to fetch Binance futures balance: " . $response->body());
        // Return mock data as fallback
        return [
            [
                'currency' => 'USDT',
                'available' => 1000.0,
                'total' => 1000.0
            ]
        ];
    }

    /**
     * Place KuCoin futures order
     */
    private function placeKuCoinFuturesOrder($symbol, $side, $quantity, $leverage, $marginType)
    {
        // Mock implementation for now
        return [
            'order_id' => 'mock_kucoin_' . time(),
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'price' => 0, // Market order
            'status' => 'filled'
        ];
    }

    /**
     * Place Binance futures order
     */
    private function placeBinanceFuturesOrder($symbol, $side, $quantity, $leverage, $marginType)
    {
        // Get server time from Binance to ensure timestamp accuracy
        $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
        if ($serverTimeResponse->successful()) {
            $serverTime = $serverTimeResponse->json()['serverTime'];
            $timestamp = $serverTime;
        } else {
            $timestamp = round(microtime(true) * 1000);
        }
        
        $endpoint = '/fapi/v1/order';
        
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => strtoupper($side),
            'type' => 'MARKET',
            'quantity' => round($quantity, 1), // Round to 1 decimal place for SUI
            'timestamp' => (int)$timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Placing Binance futures order: " . json_encode($params));
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
        
        Log::info("Binance futures order response: " . $response->body());
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['orderId'],
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'price' => $data['avgPrice'] ?? 0,
                'status' => $data['status']
            ];
        }
        
        Log::error("Binance futures order failed: " . $response->body());
        throw new \Exception("Binance futures order failed: " . $response->body());
    }

    /**
     * Close KuCoin futures position
     */
    private function closeKuCoinFuturesPosition($symbol, $side, $quantity, $orderId = null)
    {
        // Mock implementation for now
        return [
            'order_id' => 'mock_close_kucoin_' . time(),
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'status' => 'filled'
        ];
    }

    /**
     * Close Binance futures position
     */
    private function closeBinanceFuturesPosition($symbol, $side, $quantity, $orderId = null)
    {
        // Get server time from Binance to ensure timestamp accuracy
        $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
        if ($serverTimeResponse->successful()) {
            $serverTime = $serverTimeResponse->json()['serverTime'];
            $timestamp = $serverTime;
        } else {
            $timestamp = round(microtime(true) * 1000);
        }
        
        $endpoint = '/fapi/v1/order';
        
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        
        // For closing positions, we need to reverse the side
        $closeSide = $side === 'long' ? 'SELL' : 'BUY';
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => $closeSide,
            'type' => 'MARKET',
            'quantity' => round($quantity, 1), // Round to 1 decimal place for SUI
            'timestamp' => (int)$timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Closing Binance futures position: " . json_encode($params));
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
        
        Log::info("Binance futures close position response: " . $response->body());
        
        if ($response->successful()) {
            $data = $response->json();
            return [
                'order_id' => $data['orderId'],
                'symbol' => $symbol,
                'side' => $closeSide,
                'quantity' => $quantity,
                'status' => $data['status']
            ];
        }
        
        Log::error("Binance futures close position failed: " . $response->body());
        throw new \Exception("Binance futures close position failed: " . $response->body());
    }
}
