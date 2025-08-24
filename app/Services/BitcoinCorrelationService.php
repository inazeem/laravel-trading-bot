<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BitcoinCorrelationService
{
    private ExchangeService $exchangeService;
    private SmartMoneyConceptsService $btcSmcService;
    
    public function __construct(ExchangeService $exchangeService)
    {
        $this->exchangeService = $exchangeService;
    }
    
    /**
     * Get optimal candle limit based on timeframe for micro trading
     */
    private function getOptimalCandleLimit(string $timeframe): int
    {
        // Use configuration for micro trading optimization
        $limits = config('micro_trading.candle_limits', []);
        
        return $limits[$timeframe] ?? 100; // Default fallback
    }

    /**
     * Check if Bitcoin signals align with the asset signal
     * Returns true if signals are aligned, false if they conflict
     */
    public function checkBitcoinCorrelation(array $assetSignal, string $timeframe): bool
    {
        try {
            // Get Bitcoin current price
            $btcPrice = $this->exchangeService->getCurrentPrice('BTC-USDT');
            if (!$btcPrice) {
                Log::warning("⚠️ [BTC CORRELATION] Failed to get Bitcoin price");
                return false;
            }
            
            // Get Bitcoin candlestick data for the same timeframe - optimized for micro trading
            $candleLimit = $this->getOptimalCandleLimit($timeframe);
            $btcCandles = $this->exchangeService->getCandles('BTC-USDT', $timeframe, $candleLimit);
            if (empty($btcCandles)) {
                Log::warning("⚠️ [BTC CORRELATION] Failed to get Bitcoin candlestick data");
                return false;
            }
            
            // Initialize SMC service for Bitcoin
            $this->btcSmcService = new SmartMoneyConceptsService($btcCandles);
            
            // Generate Bitcoin signals
            $btcSignals = $this->btcSmcService->generateSignals($btcPrice);
            
            if (empty($btcSignals)) {
                Log::info("ℹ️ [BTC CORRELATION] No Bitcoin signals found - allowing trade");
                return true; // Allow trade if no BTC signals (neutral)
            }
            
            // Get the strongest Bitcoin signal
            $strongestBtcSignal = $this->getStrongestSignal($btcSignals);
            
            // Check if signals align
            $isAligned = $this->signalsAreAligned($assetSignal, $strongestBtcSignal);
            
            Log::info("🔗 [BTC CORRELATION] Asset signal: {$assetSignal['direction']}, BTC signal: {$strongestBtcSignal['direction']}, Aligned: " . ($isAligned ? 'YES' : 'NO'));
            
            return $isAligned;
            
        } catch (\Exception $e) {
            Log::error("❌ [BTC CORRELATION] Error checking Bitcoin correlation: " . $e->getMessage());
            return false; // Fail safe - don't allow trade if we can't verify correlation
        }
    }
    
    /**
     * Get the strongest signal from an array of signals
     */
    private function getStrongestSignal(array $signals): array
    {
        $strongest = $signals[0];
        
        foreach ($signals as $signal) {
            if (($signal['strength'] ?? 0) > ($strongest['strength'] ?? 0)) {
                $strongest = $signal;
            }
        }
        
        return $strongest;
    }
    
    /**
     * Check if two signals are aligned (same direction)
     */
    private function signalsAreAligned(array $assetSignal, array $btcSignal): bool
    {
        $assetDirection = $assetSignal['direction'];
        $btcDirection = $btcSignal['direction'];
        
        // Map directions to binary (bullish = true, bearish = false)
        $assetIsBullish = in_array($assetDirection, ['bullish', 'long']);
        $btcIsBullish = in_array($btcDirection, ['bullish', 'long']);
        
        // Signals are aligned if both are bullish or both are bearish
        return $assetIsBullish === $btcIsBullish;
    }
    
    /**
     * Get Bitcoin market sentiment score (-1 to 1)
     * -1 = strongly bearish, 0 = neutral, 1 = strongly bullish
     */
    public function getBitcoinSentiment(string $timeframe): float
    {
        try {
            $btcPrice = $this->exchangeService->getCurrentPrice('BTC-USDT');
            if (!$btcPrice) {
                return 0; // Neutral if we can't get price
            }
            
            $btcCandles = $this->exchangeService->getCandles('BTC-USDT', $timeframe, 500);
            if (empty($btcCandles)) {
                return 0; // Neutral if we can't get data
            }
            
            $this->btcSmcService = new SmartMoneyConceptsService($btcCandles);
            $btcSignals = $this->btcSmcService->generateSignals($btcPrice);
            
            if (empty($btcSignals)) {
                return 0; // Neutral if no signals
            }
            
            // Calculate weighted sentiment based on signal strength and direction
            $totalWeight = 0;
            $weightedSentiment = 0;
            
            foreach ($btcSignals as $signal) {
                $strength = $signal['strength'] ?? 0.5;
                $direction = $signal['direction'];
                
                // Convert direction to sentiment value
                $sentiment = in_array($direction, ['bullish', 'long']) ? 1 : -1;
                
                $weightedSentiment += $sentiment * $strength;
                $totalWeight += $strength;
            }
            
            if ($totalWeight == 0) {
                return 0;
            }
            
            return $weightedSentiment / $totalWeight;
            
        } catch (\Exception $e) {
            Log::error("❌ [BTC SENTIMENT] Error calculating Bitcoin sentiment: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if Bitcoin is in a strong trend (for trend-following strategy)
     */
    public function isBitcoinInStrongTrend(string $timeframe, float $threshold = 0.7): bool
    {
        $sentiment = abs($this->getBitcoinSentiment($timeframe));
        return $sentiment >= $threshold;
    }
    
    /**
     * Get correlation recommendation for asset trading
     */
    public function getCorrelationRecommendation(array $assetSignal, string $timeframe): array
    {
        $btcSentiment = $this->getBitcoinSentiment($timeframe);
        $assetDirection = $assetSignal['direction'];
        $assetIsBullish = in_array($assetDirection, ['bullish', 'long']);
        
        $recommendation = [
            'should_trade' => false,
            'reason' => '',
            'btc_sentiment' => $btcSentiment,
            'asset_direction' => $assetDirection,
            'correlation_strength' => 0
        ];
        
        // Strong bullish BTC sentiment
        if ($btcSentiment > 0.6) {
            if ($assetIsBullish) {
                $recommendation['should_trade'] = true;
                $recommendation['reason'] = 'Strong bullish Bitcoin correlation';
                $recommendation['correlation_strength'] = $btcSentiment;
            } else {
                $recommendation['reason'] = 'Asset signal conflicts with strong bullish Bitcoin';
            }
        }
        // Strong bearish BTC sentiment
        elseif ($btcSentiment < -0.6) {
            if (!$assetIsBullish) {
                $recommendation['should_trade'] = true;
                $recommendation['reason'] = 'Strong bearish Bitcoin correlation';
                $recommendation['correlation_strength'] = abs($btcSentiment);
            } else {
                $recommendation['reason'] = 'Asset signal conflicts with strong bearish Bitcoin';
            }
        }
        // Neutral BTC sentiment
        else {
            $recommendation['should_trade'] = true;
            $recommendation['reason'] = 'Bitcoin is neutral - asset signal can proceed';
            $recommendation['correlation_strength'] = 0.5;
        }
        
        return $recommendation;
    }
}




