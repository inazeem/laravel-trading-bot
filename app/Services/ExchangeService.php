<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeService
{
    private string $exchange;
    private string $apiKey;
    private string $apiSecret;
    private ?string $passphrase;
    private string $baseUrl;

    public function __construct(string $exchange, string $apiKey, string $apiSecret, ?string $passphrase = null)
    {
        $this->exchange = $exchange;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->passphrase = $passphrase;
        
        $this->baseUrl = $this->getBaseUrl();
    }

    private function getBaseUrl(): string
    {
        return match($this->exchange) {
            'kucoin' => 'https://api.kucoin.com',
            'binance' => 'https://api.binance.com',
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    /**
     * Get candlestick data
     */
    public function getCandles(string $symbol, string $interval, int $limit = 500): array
    {
        $endpoint = $this->getCandlesEndpoint($symbol, $interval, $limit);
        
        Log::info("📡 [EXCHANGE] Fetching candles from {$this->exchange} for {$symbol} ({$interval})");
        Log::info("🔗 [EXCHANGE] Endpoint: {$endpoint}");
        
        try {
            $response = Http::get($endpoint);
            
            if ($response->successful()) {
                $candles = $this->formatCandles($response->json());
                Log::info("✅ [EXCHANGE] Successfully fetched " . count($candles) . " candles from {$this->exchange}");
                return $candles;
            }
            
            Log::error("❌ [EXCHANGE] Failed to get candles from {$this->exchange}", [
                'symbol' => $symbol,
                'interval' => $interval,
                'response' => $response->body()
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error("❌ [EXCHANGE] Exception getting candles from {$this->exchange}", [
                'symbol' => $symbol,
                'interval' => $interval,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    private function getCandlesEndpoint(string $symbol, string $interval, int $limit): string
    {
        return match($this->exchange) {
            'kucoin' => "{$this->baseUrl}/api/v1/market/candles?type={$interval}&symbol={$symbol}&limit={$limit}",
            'binance' => "{$this->baseUrl}/api/v3/klines?symbol={$symbol}&interval={$interval}&limit={$limit}",
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function formatCandles(array $data): array
    {
        return match($this->exchange) {
            'kucoin' => $this->formatKucoinCandles($data),
            'binance' => $this->formatBinanceCandles($data),
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function formatKucoinCandles(array $data): array
    {
        $candles = [];
        
        foreach ($data['data'] as $candle) {
            $candles[] = [
                'time' => $candle[0],
                'open' => (float) $candle[1],
                'close' => (float) $candle[2],
                'high' => (float) $candle[3],
                'low' => (float) $candle[4],
                'volume' => (float) $candle[5],
                'amount' => (float) $candle[6]
            ];
        }
        
        return array_reverse($candles); // Reverse to get chronological order
    }

    private function formatBinanceCandles(array $data): array
    {
        $candles = [];
        
        foreach ($data as $candle) {
            $candles[] = [
                'time' => $candle[0],
                'open' => (float) $candle[1],
                'high' => (float) $candle[2],
                'low' => (float) $candle[3],
                'close' => (float) $candle[4],
                'volume' => (float) $candle[5],
                'close_time' => $candle[6],
                'quote_volume' => (float) $candle[7],
                'trades' => (int) $candle[8],
                'taker_buy_base' => (float) $candle[9],
                'taker_buy_quote' => (float) $candle[10]
            ];
        }
        
        return $candles;
    }

    /**
     * Get current price
     */
    public function getCurrentPrice(string $symbol): ?float
    {
        $endpoint = $this->getPriceEndpoint($symbol);
        
        Log::info("💰 [PRICE] Fetching current price from {$this->exchange} for {$symbol}");
        Log::info("🔗 [PRICE] Endpoint: {$endpoint}");
        
        try {
            $response = Http::get($endpoint);
            
            if ($response->successful()) {
                $price = $this->extractPrice($response->json());
                Log::info("✅ [PRICE] Current price for {$symbol}: $price");
                return $price;
            }
            
            Log::error("❌ [PRICE] Failed to get price from {$this->exchange}", [
                'symbol' => $symbol,
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("❌ [PRICE] Exception getting price from {$this->exchange}", [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    private function getPriceEndpoint(string $symbol): string
    {
        return match($this->exchange) {
            'kucoin' => "{$this->baseUrl}/api/v1/market/orderbook/level1?symbol={$symbol}",
            'binance' => "{$this->baseUrl}/api/v3/ticker/price?symbol={$symbol}",
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function extractPrice(array $data): float
    {
        return match($this->exchange) {
            'kucoin' => (float) $data['data']['price'],
            'binance' => (float) $data['price'],
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    /**
     * Place a market order
     */
    public function placeMarketOrder(string $symbol, string $side, float $quantity): ?array
    {
        $endpoint = $this->getOrderEndpoint();
        $params = $this->buildOrderParams($symbol, $side, $quantity);
        
        try {
            $response = Http::withHeaders($this->getAuthHeaders($params))
                ->post($endpoint, $params);
            
            if ($response->successful()) {
                return $this->formatOrderResponse($response->json());
            }
            
            Log::error("Failed to place order on {$this->exchange}", [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("Exception placing order on {$this->exchange}", [
                'symbol' => $symbol,
                'side' => $side,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    private function getOrderEndpoint(): string
    {
        return match($this->exchange) {
            'kucoin' => "{$this->baseUrl}/api/v1/orders",
            'binance' => "{$this->baseUrl}/api/v3/order",
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function buildOrderParams(string $symbol, string $side, float $quantity): array
    {
        return match($this->exchange) {
            'kucoin' => [
                'clientOid' => uniqid(),
                'symbol' => $symbol,
                'side' => $side,
                'type' => 'market',
                'size' => $quantity
            ],
            'binance' => [
                'symbol' => $symbol,
                'side' => strtoupper($side),
                'type' => 'MARKET',
                'quantity' => $quantity,
                'timestamp' => round(microtime(true) * 1000)
            ],
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function getAuthHeaders(array $params): array
    {
        return match($this->exchange) {
            'kucoin' => $this->getKucoinAuthHeaders($params),
            'binance' => $this->getBinanceAuthHeaders($params),
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function getKucoinAuthHeaders(array $params): array
    {
        $timestamp = time() * 1000;
        $signature = $this->generateKucoinSignature($timestamp, $params);
        
        return [
            'KC-API-KEY' => $this->apiKey,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $this->passphrase,
            'KC-API-KEY-VERSION' => '2'
        ];
    }

    private function getBinanceAuthHeaders(array $params): array
    {
        $signature = $this->generateBinanceSignature($params);
        $params['signature'] = $signature;
        
        return [
            'X-MBX-APIKEY' => $this->apiKey
        ];
    }

    private function generateKucoinSignature(int $timestamp, array $params): string
    {
        $str = $timestamp . 'POST' . '/api/v1/orders' . json_encode($params);
        return base64_encode(hash_hmac('sha256', $str, $this->apiSecret, true));
    }

    private function generateBinanceSignature(array $params): string
    {
        $queryString = http_build_query($params);
        return hash_hmac('sha256', $queryString, $this->apiSecret);
    }

    private function formatOrderResponse(array $data): array
    {
        return match($this->exchange) {
            'kucoin' => [
                'order_id' => $data['data']['orderId'],
                'symbol' => $data['data']['symbol'],
                'side' => $data['data']['side'],
                'quantity' => $data['data']['size'],
                'price' => $data['data']['price'],
                'status' => $data['data']['status']
            ],
            'binance' => [
                'order_id' => $data['orderId'],
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'quantity' => $data['origQty'],
                'price' => $data['price'],
                'status' => $data['status']
            ],
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    /**
     * Get account balance
     */
    public function getBalance(string $currency = null): array
    {
        $endpoint = $this->getBalanceEndpoint();
        
        try {
            $response = Http::withHeaders($this->getAuthHeaders([]))
                ->get($endpoint);
            
            if ($response->successful()) {
                return $this->formatBalance($response->json(), $currency);
            }
            
            Log::error("Failed to get balance from {$this->exchange}", [
                'currency' => $currency,
                'response' => $response->body()
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error("Exception getting balance from {$this->exchange}", [
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    private function getBalanceEndpoint(): string
    {
        return match($this->exchange) {
            'kucoin' => "{$this->baseUrl}/api/v1/accounts",
            'binance' => "{$this->baseUrl}/api/v3/account",
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function formatBalance(array $data, ?string $currency): array
    {
        return match($this->exchange) {
            'kucoin' => $this->formatKucoinBalance($data, $currency),
            'binance' => $this->formatBinanceBalance($data, $currency),
            default => throw new \InvalidArgumentException('Unsupported exchange')
        };
    }

    private function formatKucoinBalance(array $data, ?string $currency): array
    {
        $balances = [];
        
        foreach ($data['data'] as $account) {
            if ($currency && $account['currency'] !== $currency) {
                continue;
            }
            
            if ((float) $account['balance'] > 0) {
                $balances[] = [
                    'currency' => $account['currency'],
                    'balance' => (float) $account['balance'],
                    'available' => (float) $account['available'],
                    'holds' => (float) $account['holds']
                ];
            }
        }
        
        return $balances;
    }

    private function formatBinanceBalance(array $data, ?string $currency): array
    {
        $balances = [];
        
        foreach ($data['balances'] as $balance) {
            if ($currency && $balance['asset'] !== $currency) {
                continue;
            }
            
            if ((float) $balance['free'] > 0 || (float) $balance['locked'] > 0) {
                $balances[] = [
                    'currency' => $balance['asset'],
                    'balance' => (float) $balance['free'] + (float) $balance['locked'],
                    'available' => (float) $balance['free'],
                    'holds' => (float) $balance['locked']
                ];
            }
        }
        
        return $balances;
    }
}
