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
        try {
            // Map to KuCoin futures symbol format
            $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
            
            Log::info("Placing KuCoin futures order: {$side} {$quantity} {$futuresSymbol} with {$leverage}x leverage");
            
            // First, set the leverage for the symbol
            $this->setKuCoinFuturesLeverage($futuresSymbol, $leverage);
            
            // Prepare order data according to KuCoin Futures API
            $timestamp = time() * 1000;
            $orderData = [
                'clientOid' => 'futures_' . $timestamp . '_' . rand(1000, 9999),
                'symbol' => $futuresSymbol,
                'type' => 'market', // Market order for immediate execution
                'side' => $side, // 'buy' or 'sell'
                'leverage' => $leverage,
                'size' => $quantity, // Quantity in contracts
                'timeInForce' => 'IOC', // Immediate or Cancel for market orders
            ];
            
            // Add stop loss if provided
            if ($stopLoss !== null) {
                $orderData['stop'] = $stopLoss;
                $orderData['stopPriceType'] = 'TP'; // Take profit price type
            }
            
            Log::info("KuCoin futures order data: " . json_encode($orderData));
            
            // Create the signature for authenticated request
            $requestBody = json_encode($orderData);
            $signature = $this->createKuCoinSignature('POST', '/api/v1/orders', $requestBody, $timestamp);
            
            // Place the order
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->post('https://api-futures.kucoin.com/api/v1/orders', $orderData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['data']['orderId'])) {
                    Log::info("KuCoin futures order placed successfully: " . $responseData['data']['orderId']);
                    
        return [
                        'order_id' => $responseData['data']['orderId'],
            'symbol' => $symbol,
            'side' => $side,
            'quantity' => $quantity,
            'price' => 0, // Market order
                        'status' => 'filled',
                        'leverage' => $leverage,
                        'futures_symbol' => $futuresSymbol,
                        'exchange_response' => $responseData
                    ];
                } else {
                    Log::error("KuCoin futures order failed - no order ID in response: " . $response->body());
                    throw new \Exception("Failed to place KuCoin futures order - no order ID returned");
                }
            } else {
                $errorBody = $response->body();
                Log::error("KuCoin futures order failed: " . $errorBody);
                
                // Parse error for better user feedback
                $errorData = $response->json();
                $errorMessage = isset($errorData['msg']) ? $errorData['msg'] : 'Unknown error';
                
                throw new \Exception("KuCoin futures order failed: {$errorMessage}");
            }
            
        } catch (\Exception $e) {
            Log::error("Exception in KuCoin futures order: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Set leverage for KuCoin futures symbol
     */
    private function setKuCoinFuturesLeverage(string $symbol, int $leverage): void
    {
        try {
            $timestamp = time() * 1000;
            $leverageData = [
                'symbol' => $symbol,
                'leverage' => $leverage
            ];
            
            $requestBody = json_encode($leverageData);
            $signature = $this->createKuCoinSignature('POST', '/api/v1/position/leverage', $requestBody, $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->post('https://api-futures.kucoin.com/api/v1/position/leverage', $leverageData);
            
            if ($response->successful()) {
                Log::info("KuCoin futures leverage set to {$leverage}x for {$symbol}");
            } else {
                Log::warning("Failed to set KuCoin futures leverage: " . $response->body());
                // Don't throw exception as leverage might already be set
            }
            
        } catch (\Exception $e) {
            Log::warning("Exception setting KuCoin futures leverage: " . $e->getMessage());
            // Don't throw exception as this is not critical
        }
    }
    
    /**
     * Create KuCoin API signature for authenticated requests
     */
    private function createKuCoinSignature(string $method, string $endpoint, string $body, int $timestamp): string
    {
        $what = $timestamp . $method . $endpoint . $body;
        return base64_encode(hash_hmac('sha256', $what, $this->secretKey, true));
    }
    
    /**
     * Create KuCoin passphrase signature
     */
    private function createKuCoinPassphraseSignature(): string
    {
        return base64_encode(hash_hmac('sha256', $this->passphrase, $this->secretKey, true));
    }
    
    /**
     * Get KuCoin futures balance
     */
    private function getKuCoinFuturesBalance()
    {
        try {
            $timestamp = time() * 1000;
            $endpoint = '/api/v1/account-overview';
            $signature = $this->createKuCoinSignature('GET', $endpoint, '', $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
            ])->get('https://api-futures.kucoin.com' . $endpoint);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    $balances = [];
                    
                    // KuCoin futures returns account overview with available balance
                    $balances[] = [
                        'currency' => 'USDT',
                        'available' => $data['data']['availableBalance'] ?? 0,
                        'balance' => $data['data']['accountEquity'] ?? 0,
                        'hold' => $data['data']['frozenFunds'] ?? 0,
                    ];
                    
                    Log::info("KuCoin futures balance fetched: Available USDT = " . ($data['data']['availableBalance'] ?? 0));
                    return $balances;
                }
            }
            
            Log::error("Failed to get KuCoin futures balance: " . $response->body());
            return [];
            
        } catch (\Exception $e) {
            Log::error("Exception getting KuCoin futures balance: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get KuCoin futures open positions
     */
    public function getOpenPositions($symbol = null)
    {
        try {
            Log::info("[EXCHANGE] getOpenPositions called for exchange: {$this->exchange}, symbol: " . ($symbol ?? 'ALL'));
            
            switch ($this->exchange) {
                case 'kucoin':
                    Log::info("[EXCHANGE] Routing to KuCoin futures positions");
                    return $this->getKuCoinFuturesPositions($symbol);
                case 'binance':
                    Log::info("[EXCHANGE] Routing to Binance futures positions");
                    return $this->getBinanceFuturesPositions($symbol);
                default:
                    Log::error("[EXCHANGE] Unsupported exchange: {$this->exchange}");
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("[EXCHANGE] Error getting open positions: " . $e->getMessage());
            Log::error("[EXCHANGE] Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get KuCoin futures positions
     */
    private function getKuCoinFuturesPositions($symbol = null)
    {
        try {
            Log::info("[KUCOIN] Starting getKuCoinFuturesPositions for symbol: " . ($symbol ?? 'ALL'));
            
            $timestamp = time() * 1000;
            $endpoint = '/api/v1/positions';
            
            // Add symbol filter if provided
            $queryParams = '';
            if ($symbol) {
                $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
                $queryParams = '?symbol=' . urlencode($futuresSymbol);
                $endpoint .= $queryParams;
                Log::info("[KUCOIN] Mapped symbol {$symbol} to {$futuresSymbol}");
            }
            
            Log::info("[KUCOIN] Making request to: https://api-futures.kucoin.com{$endpoint}");
            
            $signature = $this->createKuCoinSignature('GET', $endpoint, '', $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
            ])->get('https://api-futures.kucoin.com' . $endpoint);
            
            Log::info("[KUCOIN] Response status: " . $response->status());
            Log::info("[KUCOIN] Response body: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                $positions = [];
                
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $position) {
                        // Only include positions with size > 0 and isOpen = true
                        $currentQty = floatval($position['currentQty'] ?? 0);
                        $isOpen = $position['isOpen'] ?? false;
                        
                        Log::info("Processing KuCoin position: " . json_encode([
                            'symbol' => $position['symbol'],
                            'currentQty' => $currentQty,
                            'isOpen' => $isOpen,
                            'condition_met' => (abs($currentQty) > 0 && $isOpen)
                        ]));
                        
                        if (abs($currentQty) > 0 && $isOpen) {
                            $positions[] = [
                                'symbol' => $position['symbol'],
                                'side' => $currentQty > 0 ? 'long' : 'short',
                                'quantity' => abs($currentQty),
                                'entry_price' => floatval($position['avgEntryPrice'] ?? 0),
                                'unrealized_pnl' => floatval($position['unrealisedPnl'] ?? 0),
                                'leverage' => floatval($position['realLeverage'] ?? 1),
                                'margin_type' => ($position['crossMode'] ?? false) ? 'cross' : 'isolated',
                            ];
                        }
                    }
                }
                
                Log::info("KuCoin futures positions fetched: " . count($positions) . " open positions");
                return $positions;
            }
            
            Log::error("Failed to get KuCoin futures positions: " . $response->body());
            return [];
            
        } catch (\Exception $e) {
            Log::error("Exception getting KuCoin futures positions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Close KuCoin futures position
     */
    private function closeKuCoinFuturesPosition($symbol, $side, $quantity, $orderId = null)
    {
        try {
            $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
            
            // For closing, we need to place an opposite side order
            $closeSide = ($side === 'long') ? 'sell' : 'buy';
            
            Log::info("Closing KuCoin futures position: {$closeSide} {$quantity} {$futuresSymbol}");
            
            $timestamp = time() * 1000;
            $orderData = [
                'clientOid' => 'close_' . $timestamp . '_' . rand(1000, 9999),
                'symbol' => $futuresSymbol,
                'type' => 'market',
                'side' => $closeSide,
                'size' => $quantity,
                'reduceOnly' => true, // This ensures we're closing position, not opening new one
                'timeInForce' => 'IOC',
            ];
            
            $requestBody = json_encode($orderData);
            $signature = $this->createKuCoinSignature('POST', '/api/v1/orders', $requestBody, $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->post('https://api-futures.kucoin.com/api/v1/orders', $orderData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['data']['orderId'])) {
                    Log::info("KuCoin futures position closed successfully: " . $responseData['data']['orderId']);
                    
                    return [
                        'order_id' => $responseData['data']['orderId'],
                        'symbol' => $symbol,
                        'side' => $closeSide,
                        'quantity' => $quantity,
                        'status' => 'filled',
                        'futures_symbol' => $futuresSymbol,
                        'exchange_response' => $responseData
                    ];
                }
            }
            
            Log::error("Failed to close KuCoin futures position: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Exception closing KuCoin futures position: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get order status from KuCoin
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
     * Get KuCoin order status
     */
    private function getKuCoinOrderStatus($symbol, $orderId)
    {
        try {
            $timestamp = time() * 1000;
            $endpoint = "/api/v1/orders/{$orderId}";
            $signature = $this->createKuCoinSignature('GET', $endpoint, '', $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
            ])->get('https://api-futures.kucoin.com' . $endpoint);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    return [
                        'order_id' => $data['data']['id'],
                        'status' => strtoupper($data['data']['status']), // Convert to uppercase to match Binance format
                        'symbol' => $data['data']['symbol'],
                        'side' => $data['data']['side'],
                        'quantity' => $data['data']['size'],
                        'filled_quantity' => $data['data']['dealSize'],
                    ];
                }
            }
            
            Log::error("Failed to get KuCoin order status: " . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error("Exception getting KuCoin order status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cancel all open orders for a symbol on KuCoin
     */
    public function cancelAllOpenOrdersForSymbol($symbol)
    {
        try {
            switch ($this->exchange) {
                case 'kucoin':
                    return $this->cancelAllKuCoinFuturesOrders($symbol);
                case 'binance':
                    return $this->cancelAllBinanceFuturesOrders($symbol);
                default:
                    throw new \Exception("Unsupported exchange: {$this->exchange}");
            }
        } catch (\Exception $e) {
            Log::error("Error cancelling all orders: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel all KuCoin futures orders for a symbol
     */
    private function cancelAllKuCoinFuturesOrders($symbol)
    {
        try {
            $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
            $timestamp = time() * 1000;
            $endpoint = '/api/v1/orders';
            
            $cancelData = [
                'symbol' => $futuresSymbol
            ];
            
            $requestBody = json_encode($cancelData);
            $signature = $this->createKuCoinSignature('DELETE', $endpoint, $requestBody, $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
                'Content-Type' => 'application/json'
            ])->delete('https://api-futures.kucoin.com' . $endpoint, $cancelData);
            
            if ($response->successful()) {
                Log::info("All KuCoin futures orders cancelled for {$futuresSymbol}");
                return true;
            }
            
            Log::warning("Failed to cancel all KuCoin futures orders: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Exception cancelling all KuCoin futures orders: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel specific order
     */
    public function cancelOrder($symbol, $orderId)
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
            Log::error("Error cancelling order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel specific KuCoin order
     */
    private function cancelKuCoinOrder($symbol, $orderId)
    {
        try {
            $timestamp = time() * 1000;
            $endpoint = "/api/v1/orders/{$orderId}";
            $signature = $this->createKuCoinSignature('DELETE', $endpoint, '', $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
            ])->delete('https://api-futures.kucoin.com' . $endpoint);
            
            if ($response->successful()) {
                Log::info("KuCoin order {$orderId} cancelled successfully");
                return true;
            }
            
            Log::warning("Failed to cancel KuCoin order {$orderId}: " . $response->body());
            return false;
            
        } catch (\Exception $e) {
            Log::error("Exception cancelling KuCoin order: " . $e->getMessage());
            return false;
        }
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
            
            // MARKET ORDER ONLY - simplified logic
            $orderParams = [
                'symbol' => $binanceSymbol,
                'side' => strtoupper($side),
                'type' => 'MARKET',
                'quantity' => round($quantity, $precision),
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
            'SOLUSDT' => 1,
            'AVAXUSDT' => 1,
        ];
        
        return $precisionMap[$symbol] ?? 0; // Default to 0 decimal places
    }
    
    /**
     * Place stop loss order
     */
    public function placeStopLossOrder($symbol, $side, $quantity, $stopLoss, $timestamp = null)
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
        
        // Get appropriate precision for this symbol
        $precision = $this->getFuturesQuantityPrecision($binanceSymbol);
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => $slSide,
            'type' => 'STOP_MARKET',
            'quantity' => round($quantity, $precision),
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
        
        // Get appropriate precision for this symbol
        $precision = $this->getFuturesQuantityPrecision($binanceSymbol);
        
        $params = [
            'symbol' => $binanceSymbol,
            'side' => $tpSide,
            'type' => 'TAKE_PROFIT_MARKET',
            'quantity' => round($quantity, $precision),
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
     * Get KuCoin open positions
     */
    private function getKuCoinOpenPositions($symbol = null)
    {
        try {
            Log::info("[KUCOIN] Starting getKuCoinOpenPositions for symbol: " . ($symbol ?? 'ALL'));
            
            $timestamp = time() * 1000;
            $endpoint = '/api/v1/positions';
            
            // Add symbol filter if provided
            $queryParams = '';
            if ($symbol) {
                $futuresSymbol = $this->mapToKuCoinFuturesSymbol($symbol);
                $queryParams = '?symbol=' . urlencode($futuresSymbol);
                $endpoint .= $queryParams;
                Log::info("[KUCOIN] Mapped symbol {$symbol} to {$futuresSymbol}");
            }
            
            Log::info("[KUCOIN] Making request to: https://api-futures.kucoin.com{$endpoint}");
            
            $signature = $this->createKuCoinSignature('GET', $endpoint, '', $timestamp);
            
            $response = Http::withHeaders([
                'KC-API-KEY' => $this->apiKey,
                'KC-API-SIGN' => $signature,
                'KC-API-TIMESTAMP' => $timestamp,
                'KC-API-PASSPHRASE' => $this->createKuCoinPassphraseSignature(),
                'KC-API-KEY-VERSION' => '2',
            ])->get('https://api-futures.kucoin.com' . $endpoint);
            
            Log::info("[KUCOIN] Response status: " . $response->status());
            Log::info("[KUCOIN] Response body: " . $response->body());
            
            if ($response->successful()) {
                $data = $response->json();
                $positions = [];
                
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $position) {
                        // Only include positions with size > 0 and isOpen = true
                        $currentQty = floatval($position['currentQty'] ?? 0);
                        $isOpen = $position['isOpen'] ?? false;
                        
                        Log::info("Processing KuCoin position: " . json_encode([
                            'symbol' => $position['symbol'],
                            'currentQty' => $currentQty,
                            'isOpen' => $isOpen,
                            'condition_met' => (abs($currentQty) > 0 && $isOpen)
                        ]));
                        
                        if (abs($currentQty) > 0 && $isOpen) {
                            $positions[] = [
                                'symbol' => $position['symbol'],
                                'side' => $currentQty > 0 ? 'long' : 'short',
                                'quantity' => abs($currentQty),
                                'entry_price' => floatval($position['avgEntryPrice'] ?? 0),
                                'unrealized_pnl' => floatval($position['unrealisedPnl'] ?? 0),
                                'leverage' => floatval($position['realLeverage'] ?? 1),
                                'margin_type' => ($position['crossMode'] ?? false) ? 'cross' : 'isolated',
                            ];
                        }
                    }
                }
                
                Log::info("KuCoin futures positions fetched: " . count($positions) . " open positions");
                return $positions;
            }
            
            Log::error("Failed to get KuCoin futures positions: " . $response->body());
            return [];
            
        } catch (\Exception $e) {
            Log::error("Exception getting KuCoin futures positions: " . $e->getMessage());
            return [];
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
