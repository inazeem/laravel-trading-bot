<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SmartMoneyConceptsService
{
    private array $candles;
    private array $swingHighs = [];
    private array $swingLows = [];
    private array $orderBlocks = [];
    private array $fairValueGaps = [];
    private array $equalHighs = [];
    private array $equalLows = [];

    public function __construct(array $candles)
    {
        $this->candles = $candles;
        $this->analyzeStructure();
    }

    /**
     * Analyze market structure using Smart Money Concepts
     */
    private function analyzeStructure(): void
    {
        \Log::info("🧠 [SMC] Starting Smart Money Concepts analysis...");
        \Log::info("📊 [SMC] Analyzing " . count($this->candles) . " candlesticks...");
        
        $this->identifySwingPoints();
        \Log::info("📈 [SMC] Found " . count($this->swingHighs) . " swing highs and " . count($this->swingLows) . " swing lows");
        
        $this->identifyOrderBlocks();
        \Log::info("📦 [SMC] Found " . count($this->orderBlocks) . " order blocks");
        
        $this->identifyFairValueGaps();
        \Log::info("🕳️ [SMC] Found " . count($this->fairValueGaps) . " fair value gaps");
        
        $this->identifyEqualHighsLows();
        \Log::info("⚖️ [SMC] Found " . count($this->equalHighs) . " equal highs and " . count($this->equalLows) . " equal lows");
        
        \Log::info("✅ [SMC] Smart Money Concepts analysis completed");
    }

    /**
     * Identify swing highs and lows
     */
    private function identifySwingPoints(): void
    {
        $length = 5; // Swing detection length
        
        for ($i = $length; $i < count($this->candles) - $length; $i++) {
            $candle = $this->candles[$i];
            
            // Check for swing high
            $isSwingHigh = true;
            for ($j = $i - $length; $j <= $i + $length; $j++) {
                if ($j != $i && $this->candles[$j]['high'] >= $candle['high']) {
                    $isSwingHigh = false;
                    break;
                }
            }
            
            if ($isSwingHigh) {
                $this->swingHighs[] = [
                    'index' => $i,
                    'price' => $candle['high'],
                    'time' => $candle['timestamp']
                ];
            }
            
            // Check for swing low
            $isSwingLow = true;
            for ($j = $i - $length; $j <= $i + $length; $j++) {
                if ($j != $i && $this->candles[$j]['low'] <= $candle['low']) {
                    $isSwingLow = false;
                    break;
                }
            }
            
            if ($isSwingLow) {
                $this->swingLows[] = [
                    'index' => $i,
                    'price' => $candle['low'],
                    'time' => $candle['timestamp']
                ];
            }
        }
    }

    /**
     * Identify order blocks (institutional order zones)
     */
    private function identifyOrderBlocks(): void
    {
        $swingPoints = array_merge($this->swingHighs, $this->swingLows);
        usort($swingPoints, fn($a, $b) => $a['index'] - $b['index']);
        
        for ($i = 0; $i < count($swingPoints) - 1; $i++) {
            $current = $swingPoints[$i];
            $next = $swingPoints[$i + 1];
            
            // Find the highest high and lowest low between swing points
            $high = $current['price'];
            $low = $current['price'];
            
            for ($j = $current['index']; $j <= $next['index']; $j++) {
                $high = max($high, $this->candles[$j]['high']);
                $low = min($low, $this->candles[$j]['low']);
            }
            
            // Determine if it's a bullish or bearish order block
            $isBullish = $next['price'] > $current['price'];
            
            $this->orderBlocks[] = [
                'start_index' => $current['index'],
                'end_index' => $next['index'],
                'high' => $high,
                'low' => $low,
                'type' => $isBullish ? 'bullish' : 'bearish',
                'strength' => $this->calculateOrderBlockStrength($current['index'], $next['index'])
            ];
        }
    }

    /**
     * Identify fair value gaps
     */
    private function identifyFairValueGaps(): void
    {
        for ($i = 1; $i < count($this->candles) - 1; $i++) {
            $prev = $this->candles[$i - 1];
            $current = $this->candles[$i];
            $next = $this->candles[$i + 1];
            
            // Bullish fair value gap
            if ($next['low'] > $prev['high']) {
                $this->fairValueGaps[] = [
                    'index' => $i,
                    'type' => 'bullish',
                    'gap_low' => $prev['high'],
                    'gap_high' => $next['low'],
                    'strength' => ($next['low'] - $prev['high']) / $prev['high'] * 100
                ];
            }
            
            // Bearish fair value gap
            if ($next['high'] < $prev['low']) {
                $this->fairValueGaps[] = [
                    'index' => $i,
                    'type' => 'bearish',
                    'gap_low' => $next['high'],
                    'gap_high' => $prev['low'],
                    'strength' => ($prev['low'] - $next['high']) / $prev['low'] * 100
                ];
            }
        }
    }

    /**
     * Identify equal highs and lows
     */
    private function identifyEqualHighsLows(): void
    {
        $threshold = 0.1; // 0.1% threshold for equal levels
        
        // Find equal highs
        for ($i = 0; $i < count($this->swingHighs); $i++) {
            for ($j = $i + 1; $j < count($this->swingHighs); $j++) {
                $diff = abs($this->swingHighs[$i]['price'] - $this->swingHighs[$j]['price']) / $this->swingHighs[$i]['price'];
                if ($diff <= $threshold) {
                    $this->equalHighs[] = [
                        'price' => ($this->swingHighs[$i]['price'] + $this->swingHighs[$j]['price']) / 2,
                        'index1' => $this->swingHighs[$i]['index'],
                        'index2' => $this->swingHighs[$j]['index']
                    ];
                }
            }
        }
        
        // Find equal lows
        for ($i = 0; $i < count($this->swingLows); $i++) {
            for ($j = $i + 1; $j < count($this->swingLows); $j++) {
                $diff = abs($this->swingLows[$i]['price'] - $this->swingLows[$j]['price']) / $this->swingLows[$i]['price'];
                if ($diff <= $threshold) {
                    $this->equalLows[] = [
                        'price' => ($this->swingLows[$i]['price'] + $this->swingLows[$j]['price']) / 2,
                        'index1' => $this->swingLows[$i]['index'],
                        'index2' => $this->swingLows[$j]['index']
                    ];
                }
            }
        }
    }

    /**
     * Calculate order block strength
     */
    private function calculateOrderBlockStrength(int $startIndex, int $endIndex): float
    {
        $volume = 0;
        $priceRange = 0;
        
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $volume += $this->candles[$i]['volume'] ?? 0;
            $priceRange += $this->candles[$i]['high'] - $this->candles[$i]['low'];
        }
        
        return $volume * $priceRange;
    }

    /**
     * Detect Break of Structure (BOS)
     */
    public function detectBOS(float $currentPrice): ?array
    {
        $recentSwingHighs = array_filter($this->swingHighs, fn($swing) => $swing['index'] >= count($this->candles) - 20);
        $recentSwingLows = array_filter($this->swingLows, fn($swing) => $swing['index'] >= count($this->candles) - 20);
        
        // Bullish BOS
        foreach ($recentSwingHighs as $swing) {
            if ($currentPrice > $swing['price']) {
                return [
                    'type' => 'BOS',
                    'direction' => 'bullish',
                    'level' => $swing['price'],
                    'strength' => ($currentPrice - $swing['price']) / $swing['price'] * 100
                ];
            }
        }
        
        // Bearish BOS
        foreach ($recentSwingLows as $swing) {
            if ($currentPrice < $swing['price']) {
                return [
                    'type' => 'BOS',
                    'direction' => 'bearish',
                    'level' => $swing['price'],
                    'strength' => ($swing['price'] - $currentPrice) / $swing['price'] * 100
                ];
            }
        }
        
        return null;
    }

    /**
     * Detect Change of Character (CHoCH)
     */
    public function detectCHoCH(float $currentPrice): ?array
    {
        $lastSwingHigh = end($this->swingHighs);
        $lastSwingLow = end($this->swingLows);
        
        if (!$lastSwingHigh || !$lastSwingLow) {
            return null;
        }
        
        // Bullish CHoCH (price breaks above last swing high after breaking below last swing low)
        if ($currentPrice > $lastSwingHigh['price'] && $currentPrice > $lastSwingLow['price']) {
            return [
                'type' => 'CHoCH',
                'direction' => 'bullish',
                'support_level' => $lastSwingLow['price'],
                'resistance_level' => $lastSwingHigh['price'],
                'strength' => ($currentPrice - $lastSwingHigh['price']) / $lastSwingHigh['price'] * 100
            ];
        }
        
        // Bearish CHoCH (price breaks below last swing low after breaking above last swing high)
        if ($currentPrice < $lastSwingLow['price'] && $currentPrice < $lastSwingHigh['price']) {
            return [
                'type' => 'CHoCH',
                'direction' => 'bearish',
                'support_level' => $lastSwingLow['price'],
                'resistance_level' => $lastSwingHigh['price'],
                'strength' => ($lastSwingLow['price'] - $currentPrice) / $lastSwingLow['price'] * 100
            ];
        }
        
        return null;
    }

    /**
     * Get support and resistance levels
     */
    public function getSupportResistanceLevels(): array
    {
        $levels = [];
        
        // Add recent swing highs as resistance
        $recentHighs = array_slice($this->swingHighs, -5);
        foreach ($recentHighs as $high) {
            $levels[] = [
                'type' => 'resistance',
                'price' => $high['price'],
                'strength' => 'high'
            ];
        }
        
        // Add recent swing lows as support
        $recentLows = array_slice($this->swingLows, -5);
        foreach ($recentLows as $low) {
            $levels[] = [
                'type' => 'support',
                'price' => $low['price'],
                'strength' => 'high'
            ];
        }
        
        // Add equal highs and lows
        foreach ($this->equalHighs as $equal) {
            $levels[] = [
                'type' => 'resistance',
                'price' => $equal['price'],
                'strength' => 'medium'
            ];
        }
        
        foreach ($this->equalLows as $equal) {
            $levels[] = [
                'type' => 'support',
                'price' => $equal['price'],
                'strength' => 'medium'
            ];
        }
        
        return $levels;
    }

    /**
     * Get order blocks near current price
     */
    public function getNearbyOrderBlocks(float $currentPrice, float $threshold = 0.02): array
    {
        return array_filter($this->orderBlocks, function($block) use ($currentPrice, $threshold) {
            $blockMid = ($block['high'] + $block['low']) / 2;
            $distance = abs($currentPrice - $blockMid) / $blockMid;
            return $distance <= $threshold;
        });
    }

    /**
     * Generate trading signals
     */
    public function generateSignals(float $currentPrice): array
    {
        $signals = [];
        
        \Log::info("🔍 [SIGNALS] Generating signals for current price: $currentPrice");
        
        // Check for BOS
        \Log::info("🔍 [SIGNALS] Checking for Break of Structure (BOS)...");
        $bos = $this->detectBOS($currentPrice);
        if ($bos) {
            $signals[] = $bos;
            \Log::info("✅ [SIGNALS] BOS detected: " . json_encode($bos));
        } else {
            \Log::info("❌ [SIGNALS] No BOS detected");
        }
        
        // Check for CHoCH
        \Log::info("🔍 [SIGNALS] Checking for Change of Character (CHoCH)...");
        $choch = $this->detectCHoCH($currentPrice);
        if ($choch) {
            $signals[] = $choch;
            \Log::info("✅ [SIGNALS] CHoCH detected: " . json_encode($choch));
        } else {
            \Log::info("❌ [SIGNALS] No CHoCH detected");
        }
        
        // Check for order block interactions
        \Log::info("🔍 [SIGNALS] Checking for Order Block interactions...");
        $nearbyBlocks = $this->getNearbyOrderBlocks($currentPrice);
        \Log::info("📦 [SIGNALS] Found " . count($nearbyBlocks) . " nearby order blocks");
        
        foreach ($nearbyBlocks as $block) {
            if ($block['type'] === 'bullish' && $currentPrice > $block['high']) {
                $signal = [
                    'type' => 'OrderBlock_Breakout',
                    'direction' => 'bullish',
                    'level' => $block['high'],
                    'strength' => $block['strength']
                ];
                $signals[] = $signal;
                \Log::info("✅ [SIGNALS] Bullish Order Block breakout: " . json_encode($signal));
            } elseif ($block['type'] === 'bearish' && $currentPrice < $block['low']) {
                $signal = [
                    'type' => 'OrderBlock_Breakout',
                    'direction' => 'bearish',
                    'level' => $block['low'],
                    'strength' => $block['strength']
                ];
                $signals[] = $signal;
                \Log::info("✅ [SIGNALS] Bearish Order Block breakout: " . json_encode($signal));
            }
        }
        
        \Log::info("🎯 [SIGNALS] Generated " . count($signals) . " total signals");
        return $signals;
    }
}
