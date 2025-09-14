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
     * Map a spot-style symbol (e.g., BTC-USDT) to KuCoin futures contract code (e.g., XBTUSDTM)
     */
    private function mapToKuCoinFuturesSymbol(string $symbol): string
    {
        // Handle common base renames BTC -> XBT for KuCoin futures
        $parts = explode('-', strtoupper($symbol));
        $base = $parts[0] ?? $symbol;
        $quote = $parts[1] ?? 'USDT';

        if ($base === 'BTC') {
            $base = 'XBT';
        }

        // Perpetual contracts typically end with 'M' for USDT margined on KuCoin Futures
        return $base . $quote . 'M';
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
     * Place futures order - MARKET ORDER ONLY
     */
    public function placeFuturesOrder($symbol, $side, $quantity, $leverage, $marginType, $stopLoss = null, $takeProfit = null, $orderType = 'market', $limitBuffer = 0)
    {
        try {
            Log::info("Placing futures market order: {$side} {$symbol} Qty: {$quantity}");
            
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinFuturesOrder($symbol, $side, $quantity, $leverage, $marginType, $stopLoss, $takeProfit);
                case 'binance':
                    return $this->placeBinanceFuturesOrder($symbol, $side, $quantity, $leverage, $marginType, $stopLoss, $takeProfit, 'market', 0);
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
        // Try futures ticker first using mapped futures symbol
        try {
            $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
            $futuresUrl = 'https://api-futures.kucoin.com/api/v1/ticker?symbol=' . urlencode($futuresSymbol);
            Log::info("Fetching KuCoin Futures ticker: {$futuresUrl}");
            $futuresResponse = Http::get($futuresUrl);
            if ($futuresResponse->successful()) {
                $data = $futuresResponse->json();
                if (isset($data['data']['price'])) {
                    return (float)$data['data']['price'];
                }
            }
            Log::warning("KuCoin Futures ticker failed for {$futuresSymbol}. Response: " . $futuresResponse->body());
        } catch (\Exception $e) {
            Log::warning("KuCoin Futures ticker exception: " . $e->getMessage());
        }

        // Fallback to spot level1 orderbook with original symbol
        $spotUrl = "https://api.kucoin.com/api/v1/market/orderbook/level1?symbol=" . urlencode($symbol);
        Log::info("Fetching KuCoin Spot price: {$spotUrl}");
        $response = Http::get($spotUrl);
        if ($response->successful()) {
            $data = $response->json();
            return (float) $data['data']['price'];
        }
        
        Log::error("Failed to fetch KuCoin price for {$symbol}. Response: " . $response->body());
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
        // First get server time to sync timestamp
        $serverTimeResponse = Http::get('https://api.binance.com/api/v3/time');
        $serverTime = 0;
        
        if ($serverTimeResponse->successful()) {
            $serverData = $serverTimeResponse->json();
            $serverTime = $serverData['serverTime'] ?? 0;
        }
        
        // Use server time if available, otherwise use local time with adjustment
        $timestamp = $serverTime > 0 ? $serverTime : (round(microtime(true) * 1000) - 2000);
        
        $endpoint = '/api/v3/account';
        
        $params = [
            'timestamp' => $timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Making Binance API request to: {$endpoint}");
        Log::info("API Key: " . substr($this->apiKey, 0, 10) . "...");
        Log::info("Timestamp: {$timestamp} (server time: {$serverTime})");
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get('https://api.binance.com' . $endpoint, $params);
        
        Log::info("Binance API response status: " . $response->status());
        Log::info("Binance API response body: " . $response->body());
        
        if ($response->successful()) {
            $data = $response->json();
            Log::info("Binance API response data: " . json_encode($data));
            
            if (isset($data['balances'])) {
                Log::info("Found " . count($data['balances']) . " balance entries");
                return $data['balances'];
            } else {
                Log::error("No 'balances' key found in Binance response");
                return [];
            }
        }
        
        Log::error("Binance API request failed: " . $response->body());
        return [];
    }

    /**
     * KuCoin candlestick data
     */
    private function getKuCoinCandles($symbol, $interval = '1h', $limit = 500)
    {
        // Prefer KuCoin Futures K-line when using futures symbols or futures-style intervals
        $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);

        // Map common intervals to numeric minutes required by futures endpoint
        $intervalMap = [
            '1m' => 1, '1min' => 1, '1minute' => 1,
            '5m' => 5, '5min' => 5, '5minute' => 5,
            '15m' => 15, '15min' => 15, '15minute' => 15,
            '30m' => 30, '30min' => 30, '30minute' => 30,
            '1h' => 60, '1hour' => 60,
            '4h' => 240, '4hour' => 240,
            '1d' => 1440, '1day' => 1440,
        ];

        $granularity = $intervalMap[$interval] ?? null;

        if ($granularity !== null) {
            $url = 'https://api-futures.kucoin.com/api/v1/kline/query';

            $to = (int) round(microtime(true) * 1000);
            $from = $to - ($limit * $granularity * 60 * 1000);

            $params = [
                'symbol' => $futuresSymbol,
                'granularity' => (int)$granularity,
                'from' => $from,
                'to' => $to,
            ];

            Log::info("Making KuCoin Futures K-line request to: {$url} with params: " . json_encode($params));

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

                Log::info("Successfully fetched " . count($candles) . " KuCoin Futures candlesticks for {$futuresSymbol} with granularity {$granularity}");
                return array_reverse($candles);
            }

            Log::warning("KuCoin Futures K-line request failed for {$futuresSymbol} (granularity {$granularity}). Falling back to spot endpoint. Status: {$response->status()}, Response: {$response->body()}");
            // Fall through to spot endpoint below as a fallback
        }

        // Fallback to spot candlesticks endpoint (expects type like 1hour/1day)
        $url = "https://api.kucoin.com/api/v1/market/candles";
        $params = [
            'symbol' => $symbol,
            'type' => $interval,
            'limit' => $limit
        ];

        Log::info("Making KuCoin Spot request to: {$url} with params: " . json_encode($params));

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

            Log::info("Successfully fetched " . count($candles) . " KuCoin Spot candlesticks for {$symbol} with interval {$interval}");
            return array_reverse($candles); // Return in chronological order
        }

        Log::error("Failed to fetch KuCoin Spot candles for {$symbol} with interval {$interval}. Status: {$response->status()}, Response: {$response->body()}");
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
     * Set leverage for Binance futures symbol
     */
    private function setBinanceFuturesLeverage($symbol, $leverage, $marginType = 'isolated')
    {
        try {
            // Get server time from Binance
            $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
            if ($serverTimeResponse->successful()) {
                $serverTime = $serverTimeResponse->json()['serverTime'];
                $timestamp = $serverTime;
            } else {
                $timestamp = round(microtime(true) * 1000);
            }
            
            $endpoint = '/fapi/v1/leverage';
            
            // Normalize symbol for Binance (remove dash)
            $binanceSymbol = str_replace('-', '', $symbol);
            
            $params = [
                'symbol' => $binanceSymbol,
                'leverage' => (int)$leverage,
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($params);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $params['signature'] = $signature;
            
            Log::info("Setting Binance futures leverage: " . json_encode($params));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
            
            Log::info("Binance leverage setting response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info("✅ Leverage set successfully for {$binanceSymbol}: {$leverage}x");
                return true;
            }
            
            Log::error("Failed to set leverage: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error setting Binance futures leverage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set margin type for Binance futures symbol
     */
    private function setBinanceFuturesMarginType($symbol, $marginType)
    {
        try {
            // Get server time from Binance
            $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
            if ($serverTimeResponse->successful()) {
                $serverTime = $serverTimeResponse->json()['serverTime'];
                $timestamp = $serverTime;
            } else {
                $timestamp = round(microtime(true) * 1000);
            }
            
            $endpoint = '/fapi/v1/marginType';
            
            // Normalize symbol for Binance (remove dash)
            $binanceSymbol = str_replace('-', '', $symbol);
            
            $params = [
                'symbol' => $binanceSymbol,
                'marginType' => strtoupper($marginType),
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($params);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $params['signature'] = $signature;
            
            Log::info("Setting Binance futures margin type: " . json_encode($params));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
            
            Log::info("Binance margin type setting response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info("✅ Margin type set successfully for {$binanceSymbol}: {$marginType}");
                return true;
            }
            
            Log::error("Failed to set margin type: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error setting Binance futures margin type: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Place KuCoin futures order
     */
    private function placeKuCoinFuturesOrder($symbol, $side, $quantity, $leverage, $marginType, $stopLoss = null, $takeProfit = null)
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
     * Place Binance futures order - MARKET ORDER ONLY
     */
    private function placeBinanceFuturesOrder($symbol, $side, $quantity, $leverage, $marginType, $stopLoss = null, $takeProfit = null, $orderType = 'market', $limitBuffer = 0)
    {
        try {
            // Get server time from Binance to ensure timestamp accuracy
            $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
            if ($serverTimeResponse->successful()) {
                $serverTime = $serverTimeResponse->json()['serverTime'];
                $timestamp = $serverTime;
            } else {
                $timestamp = round(microtime(true) * 1000);
            }
            
            // Normalize symbol for Binance (remove dash)
            $binanceSymbol = str_replace('-', '', $symbol);
            
            // Set leverage and margin type before placing order
            Log::info("Setting leverage to {$leverage}x and margin type to {$marginType} for {$binanceSymbol}");
            
            // Set margin type first
            $marginTypeSet = $this->setBinanceFuturesMarginType($binanceSymbol, $marginType);
            if (!$marginTypeSet) {
                Log::warning("Failed to set margin type, but continuing with order placement");
            }
            
            // Set leverage
            $leverageSet = $this->setBinanceFuturesLeverage($binanceSymbol, $leverage, $marginType);
            if (!$leverageSet) {
                Log::warning("Failed to set leverage, but continuing with order placement");
            }
            
            // Wait a moment for settings to take effect
            sleep(1);
            
            $endpoint = '/fapi/v1/order';
            
            // Get appropriate precision for this symbol
            $precision = $this->getFuturesQuantityPrecision($binanceSymbol);
            $roundedQuantity = round($quantity, $precision);
            
            Log::info("🔧 [ORDER DEBUG] Original quantity: {$quantity}, Precision: {$precision}, Rounded: {$roundedQuantity}");
            
            // Validate quantity before placing order
            if ($roundedQuantity <= 0) {
                Log::error("❌ [ORDER ERROR] Rounded quantity is zero or negative: {$roundedQuantity}");
                Log::error("❌ [ORDER ERROR] Original quantity: {$quantity}, Precision: {$precision}");
                
                // Try with higher precision if quantity is too small
                if ($quantity > 0) {
                    $higherPrecision = min(3, $precision + 1);
                    $roundedQuantity = round($quantity, $higherPrecision);
                    Log::info("🔧 [ORDER FIX] Trying higher precision {$higherPrecision}, new quantity: {$roundedQuantity}");
                    
                    if ($roundedQuantity <= 0) {
                        throw new \Exception("Quantity too small for any reasonable precision: {$quantity}");
                    }
                } else {
                    throw new \Exception("Invalid quantity after precision rounding: {$roundedQuantity}");
                }
            }
            
            // MARKET ORDER ONLY - simplified logic
            $orderParams = [
                'symbol' => $binanceSymbol,
                'side' => strtoupper($side),
                'type' => 'MARKET',
                'quantity' => $roundedQuantity,
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($orderParams);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $orderParams['signature'] = $signature;
            
            Log::info("Placing Binance futures order: " . json_encode($orderParams));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $orderParams);
            
            Log::info("Binance futures order response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                $mainOrderId = $data['orderId'];
                
                // Place stop loss and take profit orders if provided
                $stopLossOrderId = null;
                $takeProfitOrderId = null;
                
                if ($stopLoss !== null) {
                    $stopLossOrderId = $this->placeBinanceStopLossOrder($binanceSymbol, $side, $quantity, $stopLoss, $timestamp);
                }
                
                if ($takeProfit !== null) {
                    $takeProfitOrderId = $this->placeBinanceTakeProfitOrder($binanceSymbol, $side, $quantity, $takeProfit, $timestamp);
                }
                
                return [
                    'order_id' => $mainOrderId,
                    'symbol' => $symbol,
                    'side' => $side,
                    'quantity' => $quantity,
                    'price' => $data['avgPrice'] ?? $data['price'] ?? 0,
                    'status' => $data['status'],
                    'stop_loss_order_id' => $stopLossOrderId,
                    'take_profit_order_id' => $takeProfitOrderId,
                    'stop_loss_price' => $stopLoss,
                    'take_profit_price' => $takeProfit,
                    'exchange_response' => $data
                ];
            }
            
            Log::error("Binance futures order failed: " . $response->body());
            throw new \Exception("Binance futures order failed: " . $response->body());
            
        } catch (\Exception $e) {
            Log::error("Error placing Binance futures order: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Place Binance stop loss order
     */
    private function placeBinanceStopLossOrder($symbol, $side, $quantity, $stopLossPrice, $timestamp): ?string
    {
        try {
            $endpoint = '/fapi/v1/order';
            
            // For stop loss, we need to place a stop market order
            $orderParams = [
                'symbol' => $symbol,
                'side' => $side === 'buy' ? 'SELL' : 'BUY', // Opposite side for stop loss
                'type' => 'STOP_MARKET',
                'quantity' => $quantity,
                'stopPrice' => round($stopLossPrice, 4),
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($orderParams);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $orderParams['signature'] = $signature;
            
            Log::info("Placing Binance stop loss order: " . json_encode($orderParams));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $orderParams);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info("Stop loss order placed successfully: " . $data['orderId']);
                return $data['orderId'];
            }
            
            Log::error("Stop loss order failed: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error placing stop loss order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place Binance take profit order
     */
    private function placeBinanceTakeProfitOrder($symbol, $side, $quantity, $takeProfitPrice, $timestamp): ?string
    {
        try {
            $endpoint = '/fapi/v1/order';
            
            // For take profit, we need to place a limit order
            $orderParams = [
                'symbol' => $symbol,
                'side' => $side === 'buy' ? 'SELL' : 'BUY', // Opposite side for take profit
                'type' => 'LIMIT',
                'timeInForce' => 'GTC',
                'quantity' => $quantity,
                'price' => round($takeProfitPrice, 4),
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($orderParams);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $orderParams['signature'] = $signature;
            
            Log::info("Placing Binance take profit order: " . json_encode($orderParams));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $orderParams);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info("Take profit order placed successfully: " . $data['orderId']);
                return $data['orderId'];
            }
            
            Log::error("Take profit order failed: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error placing take profit order: " . $e->getMessage());
            return null;
        }
    }
    

    
    /**
     * Get futures quantity precision for a symbol
     */
    private function getFuturesQuantityPrecision($symbol): int
    {
        // Common precision settings for major crypto futures
        $precisionMap = [
            'SUIUSDT' => 1,  // SUI futures allow 1 decimal place
            'BTCUSDT' => 3,
            'ETHUSDT' => 2,
            'ADAUSDT' => 0,
            'DOGEUSDT' => 0,
            'SOLUSDT' => 1,  // SOL futures allow 1 decimal place
            'AVAXUSDT' => 1,
        ];
        
        $precision = $precisionMap[$symbol] ?? 1; // Default to 1 decimal place (was 0)
        Log::info("🔧 [PRECISION] Symbol: {$symbol}, Precision: {$precision}");
        return $precision;
    }
    
    /**
     * Place stop loss order
     */
    public function placeStopLossOrder($symbol, $side, $quantity, $stopLoss, $timestamp = null)
    {
        try {
            Log::info("Placing stop loss order: {$side} {$symbol} Qty: {$quantity} @ {$stopLoss}");
            
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinStopLossOrder($symbol, $side, $quantity, $stopLoss);
                case 'binance':
                    return $this->placeBinanceStopLossOrderDirect($symbol, $side, $quantity, $stopLoss, $timestamp);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing stop loss order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place KuCoin stop loss order
     */
    private function placeKuCoinStopLossOrder($symbol, $side, $quantity, $stopLoss)
    {
        try {
            // Convert symbol format for KuCoin
            $kucoinSymbol = str_replace('-', 'USDT', $symbol) . 'M'; // e.g., SOL-USDT -> SOLUSDTM
            
            // For stop loss, we need to reverse the side
            $slSide = $side === 'buy' ? 'sell' : 'buy';
            
            $endpoint = '/api/v1/orders';
            $timestamp = round(microtime(true) * 1000);
            
            $orderData = [
                'clientOid' => uniqid(),
                'side' => $slSide,
                'symbol' => $kucoinSymbol,
                'type' => 'market',
                'size' => (string)$quantity,
                'stop' => 'loss',
                'stopPrice' => (string)$stopLoss,
                'reduceOnly' => true
            ];
            
            $body = json_encode($orderData);
            $passphrase = base64_encode(hash_hmac('sha256', $this->passphrase, $this->secretKey, true));
            $signaturePayload = $timestamp . 'POST' . $endpoint . $body;
            $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $this->secretKey, true));
            
            Log::info("Placing KuCoin stop loss order: " . $body);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $passphrase,
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->post('https://api-futures.kucoin.com' . $endpoint, $orderData);
            
            Log::info("KuCoin stop loss order response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['orderId'])) {
                    Log::info("KuCoin stop loss order placed successfully: " . $data['data']['orderId']);
                    return $data['data']['orderId'];
                }
            }
            
            Log::error("KuCoin stop loss order failed: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error placing KuCoin stop loss order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place KuCoin take profit order
     */
    private function placeKuCoinTakeProfitOrder($symbol, $side, $quantity, $takeProfit)
    {
        try {
            // Convert symbol format for KuCoin
            $kucoinSymbol = str_replace('-', 'USDT', $symbol) . 'M'; // e.g., SOL-USDT -> SOLUSDTM
            
            // For take profit, we need to reverse the side
            $tpSide = $side === 'buy' ? 'sell' : 'buy';
            
            $endpoint = '/api/v1/orders';
            $timestamp = round(microtime(true) * 1000);
            
            $orderData = [
                'clientOid' => uniqid(),
                'side' => $tpSide,
                'symbol' => $kucoinSymbol,
                'type' => 'limit',
                'size' => (string)$quantity,
                'price' => (string)$takeProfit,
                'reduceOnly' => true
            ];
            
            $body = json_encode($orderData);
            $passphrase = base64_encode(hash_hmac('sha256', $this->passphrase, $this->secretKey, true));
            $signaturePayload = $timestamp . 'POST' . $endpoint . $body;
            $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $this->secretKey, true));
            
            Log::info("Placing KuCoin take profit order: " . $body);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $passphrase,
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->post('https://api-futures.kucoin.com' . $endpoint, $orderData);
            
            Log::info("KuCoin take profit order response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['orderId'])) {
                    Log::info("KuCoin take profit order placed successfully: " . $data['data']['orderId']);
                    return $data['data']['orderId'];
                }
            }
            
            Log::error("KuCoin take profit order failed: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error placing KuCoin take profit order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place Binance stop loss order
     */
    private function placeBinanceStopLossOrderDirect($symbol, $side, $quantity, $stopLoss, $timestamp = null)
    {
        // Generate timestamp if not provided
        if ($timestamp === null) {
            $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
            if ($serverTimeResponse->successful()) {
                $serverTime = $serverTimeResponse->json()['serverTime'];
                $timestamp = $serverTime;
            } else {
                $timestamp = round(microtime(true) * 1000);
            }
        }
        
        $endpoint = '/fapi/v1/order';
        
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        
        // For stop loss, we need to reverse the side
        $slSide = $side === 'BUY' ? 'SELL' : 'BUY';
        
        // Get appropriate precision for this symbol - use higher precision for SL/TP orders
        $precision = max(2, $this->getFuturesQuantityPrecision($binanceSymbol)); // Minimum 2 decimal places
        
        $roundedQuantity = round($quantity, $precision);
        Log::info("🔧 [SL ORDER] Quantity: {$quantity}, Precision: {$precision}, Rounded: {$roundedQuantity}");
        
        if ($roundedQuantity <= 0) {
            Log::error("❌ [SL ORDER] Rounded quantity is zero: {$roundedQuantity}");
            return null;
        }
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => $slSide,
            'type' => 'STOP_MARKET',
            'quantity' => $roundedQuantity,
            'stopPrice' => round($stopLoss, 4),
            'reduceOnly' => 'true',
            'timestamp' => (int)$timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Placing stop loss order: " . json_encode($params));
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            Log::info("Stop loss order placed successfully: " . $data['orderId']);
            return $data['orderId'];
        } else {
            Log::error("Stop loss order failed: " . $response->body());
            Log::error("Stop loss order params: " . json_encode($params));
            return null;
        }
    }
    
    /**
     * Place take profit order
     */
    public function placeTakeProfitOrder($symbol, $side, $quantity, $takeProfit, $timestamp = null)
    {
        try {
            Log::info("Placing take profit order: {$side} {$symbol} Qty: {$quantity} @ {$takeProfit}");
            
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->placeKuCoinTakeProfitOrder($symbol, $side, $quantity, $takeProfit);
                case 'binance':
                    return $this->placeBinanceTakeProfitOrderDirect($symbol, $side, $quantity, $takeProfit, $timestamp);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error placing take profit order: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Place Binance take profit order
     */
    private function placeBinanceTakeProfitOrderDirect($symbol, $side, $quantity, $takeProfit, $timestamp = null)
    {
        // Generate timestamp if not provided
        if ($timestamp === null) {
            $serverTimeResponse = Http::get('https://fapi.binance.com/fapi/v1/time');
            if ($serverTimeResponse->successful()) {
                $serverTime = $serverTimeResponse->json()['serverTime'];
                $timestamp = $serverTime;
            } else {
                $timestamp = round(microtime(true) * 1000);
            }
        }
        
        $endpoint = '/fapi/v1/order';
        
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        
        // For take profit, we need to reverse the side
        $tpSide = $side === 'BUY' ? 'SELL' : 'BUY';
        
        // Get appropriate precision for this symbol - use higher precision for SL/TP orders
        $precision = max(2, $this->getFuturesQuantityPrecision($binanceSymbol)); // Minimum 2 decimal places
        
        $roundedQuantity = round($quantity, $precision);
        Log::info("🔧 [TP ORDER] Quantity: {$quantity}, Precision: {$precision}, Rounded: {$roundedQuantity}");
        
        if ($roundedQuantity <= 0) {
            Log::error("❌ [TP ORDER] Rounded quantity is zero: {$roundedQuantity}");
            return null;
        }
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => $tpSide,
            'type' => 'TAKE_PROFIT_MARKET',
            'quantity' => $roundedQuantity,
            'stopPrice' => round($takeProfit, 4),
            'reduceOnly' => 'true',
            'timestamp' => (int)$timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Placing take profit order: " . json_encode($params));
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            Log::info("Take profit order placed successfully: " . $data['orderId']);
            return $data['orderId'];
        } else {
            Log::error("Take profit order failed: " . $response->body());
            Log::error("Take profit order params: " . json_encode($params));
            return null;
        }
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
     * Close Binance futures position - FIXED VERSION
     */
    private function closeBinanceFuturesPosition($symbol, $side, $quantity, $orderId = null)
    {
        try {
            // First, get the actual position size from exchange to ensure accuracy
            $actualPosition = $this->getActualPositionSize($symbol, $side);
            if ($actualPosition) {
                $quantity = $actualPosition['size'];
                Log::info("📊 [CLOSE] Using actual position size from exchange: {$quantity}");
            } else {
                Log::warning("⚠️ [CLOSE] Could not get actual position size, using provided: {$quantity}");
            }
            
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
            
            // Get appropriate precision for this symbol
            $precision = $this->getFuturesQuantityPrecision($binanceSymbol);
            
            $params = [
                'symbol' => $binanceSymbol,
                'side' => $closeSide,
                'type' => 'MARKET',
                'quantity' => round($quantity, $precision),
                'reduceOnly' => 'true', // CRITICAL: This tells Binance we're closing a position
                'timestamp' => (int)$timestamp
            ];
            
            $queryString = http_build_query($params);
            $signature = hash_hmac('sha256', $queryString, $this->secretKey);
            $params['signature'] = $signature;
            
            Log::info("🔴 [CLOSE] Closing Binance futures position: " . json_encode($params));
            
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->asForm()->post('https://fapi.binance.com' . $endpoint, $params);
            
            Log::info("📋 [CLOSE] Binance futures close position response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                Log::info("✅ [CLOSE] Position closed successfully: Order ID {$data['orderId']}");
                return [
                    'order_id' => $data['orderId'],
                    'symbol' => $symbol,
                    'side' => $closeSide,
                    'quantity' => $quantity,
                    'status' => $data['status']
                ];
            }
            
            $errorData = $response->json();
            Log::error("❌ [CLOSE] Binance futures close position failed: " . $response->body());
            
            // Handle specific error codes
            if (isset($errorData['code'])) {
                switch ($errorData['code']) {
                    case -2019:
                        throw new \Exception("Insufficient margin to close position. This usually means the position was already closed or doesn't exist.");
                    case -2021:
                        throw new \Exception("Order would immediately trigger. Check position side and quantity.");
                    case -1111:
                        throw new \Exception("Invalid quantity precision. Expected {$precision} decimal places.");
                    default:
                        throw new \Exception("Binance error {$errorData['code']}: {$errorData['msg']}");
                }
            }
            
            throw new \Exception("Binance futures close position failed: " . $response->body());
            
        } catch (\Exception $e) {
            Log::error("❌ [CLOSE] Exception in closeBinanceFuturesPosition: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get actual position size from exchange for accurate closing
     */
    private function getActualPositionSize($symbol, $side): ?array
    {
        try {
            $positions = $this->getOpenPositions($symbol);
            
            foreach ($positions as $position) {
                if ($position['side'] === $side && $position['quantity'] > 0) {
                    return [
                        'size' => $position['quantity'],
                        'unrealized_pnl' => $position['unrealized_pnl']
                    ];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning("⚠️ [POSITION SIZE] Could not get actual position size: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get order status from exchange
     */
    public function getOrderStatus($symbol, $orderId)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinOrderStatus($symbol, $orderId);
                case 'binance':
                    return $this->getBinanceOrderStatus($symbol, $orderId);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error getting order status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Binance order status
     */
    private function getBinanceOrderStatus($symbol, $orderId)
    {
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/fapi/v1/order';
        
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        
        $params = [
            'symbol' => $binanceSymbol,
            'orderId' => $orderId,
            'timestamp' => $timestamp
        ];
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Checking Binance order status for order ID: {$orderId}");
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get('https://fapi.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            Log::info("Binance order status response: " . json_encode($data));
            return $data;
        }
        
        Log::error("Failed to get Binance order status: " . $response->body());
        return null;
    }

    /**
     * Get KuCoin order status (mock implementation)
     */
    private function getKuCoinOrderStatus($symbol, $orderId)
    {
        // Mock implementation for now
        return [
            'orderId' => $orderId,
            'status' => 'FILLED',
            'symbol' => $symbol
        ];
    }

    /**
     * Get open positions from exchange
     */
    public function getOpenPositions($symbol = null)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->getKuCoinOpenPositions($symbol);
                case 'binance':
                    return $this->getBinanceOpenPositions($symbol);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error getting open positions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Binance open positions
     */
    private function getBinanceOpenPositions($symbol = null)
    {
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/fapi/v2/positionRisk';
        
        $params = [
            'timestamp' => $timestamp
        ];
        
        if ($symbol) {
            // Normalize symbol for Binance (remove dash)
            $binanceSymbol = str_replace('-', '', $symbol);
            $params['symbol'] = $binanceSymbol;
        }
        
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;
        
        Log::info("Fetching Binance open positions" . ($symbol ? " for {$symbol}" : ""));
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->get('https://fapi.binance.com' . $endpoint, $params);
        
        if ($response->successful()) {
            $data = $response->json();
            $positions = [];
            
            foreach ($data as $position) {
                $positionAmt = floatval($position['positionAmt']);
                if ($positionAmt != 0) { // Only include positions with non-zero amount
                    $positions[] = [
                        'symbol' => $position['symbol'],
                        'side' => $positionAmt > 0 ? 'long' : 'short',
                        'quantity' => abs($positionAmt),
                        'entry_price' => floatval($position['entryPrice']),
                        'unrealized_pnl' => floatval($position['unRealizedProfit']),
                        'leverage' => intval($position['leverage']),
                        'margin_type' => $position['marginType'],
                        'position_side' => $position['positionSide']
                    ];
                }
            }
            
            Log::info("Binance open positions: " . json_encode($positions));
            return $positions;
        }
        
        Log::error("Failed to fetch Binance open positions: " . $response->body());
        return [];
    }

    /**
     * Get KuCoin open positions (mock implementation)
     */
    private function getKuCoinOpenPositions($symbol = null)
    {
        // Mock implementation for now
        return [];
    }
    
    /**
     * Cancel a specific order by ID
     */
    public function cancelOrder($symbol, $orderId): bool
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->cancelKuCoinOrder($symbol, $orderId);
                case 'binance':
                    return $this->cancelBinanceOrder($symbol, $orderId);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error cancelling order {$orderId} for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel all open orders for a symbol
     */
    public function cancelAllOpenOrdersForSymbol($symbol): bool
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->cancelKuCoinAllOpenOrders($symbol);
                case 'binance':
                    return $this->cancelBinanceAllOpenOrders($symbol);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error cancelling all open orders for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a KuCoin futures order
     */
    private function cancelKuCoinOrder($symbol, $orderId): bool
    {
        try {
            $endpoint = "/api/v1/orders/{$orderId}";
            $timestamp = round(microtime(true) * 1000);
            
            // No body for DELETE request
            $passphrase = base64_encode(hash_hmac('sha256', $this->passphrase, $this->secretKey, true));
            $signaturePayload = $timestamp . 'DELETE' . $endpoint;
            $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $this->secretKey, true));
            
            Log::info("[CANCEL] Cancelling KuCoin order: {$orderId} for symbol: {$symbol}");
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $passphrase,
                'KC-API-KEY-VERSION' => '2',
            ])->delete('https://api-futures.kucoin.com' . $endpoint);
            
            Log::info("[CANCEL] KuCoin cancel response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['cancelledOrderIds']) && in_array($orderId, $data['data']['cancelledOrderIds'])) {
                    Log::info("[CANCEL] KuCoin order {$orderId} cancelled successfully");
                    return true;
                }
            }
            
            Log::error("[CANCEL] KuCoin order {$orderId} cancel failed: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("[CANCEL] Error cancelling KuCoin order {$orderId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel all KuCoin futures orders for a symbol
     */
    private function cancelKuCoinAllOpenOrders($symbol): bool
    {
        try {
            // Convert symbol format for KuCoin
            $kucoinSymbol = str_replace('-', 'USDT', $symbol) . 'M'; // e.g., SOL-USDT -> SOLUSDTM
            
            $endpoint = '/api/v1/orders';
            $timestamp = round(microtime(true) * 1000);
            
            // Query parameters for cancelling all orders for symbol
            $queryParams = [
                'symbol' => $kucoinSymbol
            ];
            $queryString = http_build_query($queryParams);
            $fullEndpoint = $endpoint . '?' . $queryString;
            
            $passphrase = base64_encode(hash_hmac('sha256', $this->passphrase, $this->secretKey, true));
            $signaturePayload = $timestamp . 'DELETE' . $fullEndpoint;
            $signature = base64_encode(hash_hmac('sha256', $signaturePayload, $this->secretKey, true));
            
            Log::info("[CANCEL ALL] Cancelling all KuCoin orders for symbol: {$kucoinSymbol}");
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $passphrase,
                'KC-API-KEY-VERSION' => '2',
            ])->delete('https://api-futures.kucoin.com' . $fullEndpoint);
            
            Log::info("[CANCEL ALL] KuCoin cancel all response: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['cancelledOrderIds'])) {
                    $cancelledCount = count($data['data']['cancelledOrderIds']);
                    Log::info("[CANCEL ALL] KuCoin cancelled {$cancelledCount} orders for {$kucoinSymbol}");
                    return true;
                }
            }
            
            Log::error("[CANCEL ALL] KuCoin cancel all failed for {$kucoinSymbol}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("[CANCEL ALL] Error cancelling all KuCoin orders for {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a Binance futures order
     */
    private function cancelBinanceOrder($symbol, $orderId): bool
    {
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/fapi/v1/order';

        $params = [
            'symbol' => $binanceSymbol,
            'orderId' => $orderId,
            'timestamp' => (int)$timestamp,
        ];

        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;

        Log::info("[CANCEL] Binance cancel order: " . json_encode($params));

        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->delete('https://fapi.binance.com' . $endpoint, $params);

        if ($response->successful()) {
            Log::info("[CANCEL] Binance order {$orderId} cancel success");
            return true;
        }

        Log::error("[CANCEL] Binance order {$orderId} cancel failed: " . $response->body());
        return false;
    }

    /**
     * Cancel all open Binance futures orders for a symbol
     */
    private function cancelBinanceAllOpenOrders($symbol): bool
    {
        // Normalize symbol for Binance (remove dash)
        $binanceSymbol = str_replace('-', '', $symbol);
        $timestamp = round(microtime(true) * 1000);
        $endpoint = '/fapi/v1/allOpenOrders';

        $params = [
            'symbol' => $binanceSymbol,
            'timestamp' => (int)$timestamp,
        ];

        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->secretKey);
        $params['signature'] = $signature;

        Log::info("[CANCEL ALL] Binance cancel all open orders: " . json_encode($params));

        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->apiKey
        ])->asForm()->delete('https://fapi.binance.com' . $endpoint, $params);

        if ($response->successful()) {
            Log::info("[CANCEL ALL] Binance cancel all open orders success for {$binanceSymbol}");
            return true;
        }

        Log::error("[CANCEL ALL] Binance cancel all open orders failed: " . $response->body());
        return false;
    }
    
}
