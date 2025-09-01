<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScalpingSignal extends Model
{
    protected $fillable = [
        'scalping_trading_bot_id',
        'scalping_trade_id',
        'signal_type',
        'direction',
        'strength',
        'scalping_score',
        'timeframe',
        'urgency',
        'price_at_signal',
        'entry_reason',
        'signal_data',
        'confluence',
        'contributing_timeframes',
        'rsi_at_signal',
        'spread_at_signal',
        'volatility_at_signal',
        'market_conditions',
        'was_traded',
        'not_traded_reason',
        'max_price_move',
        'signal_duration_minutes',
        'was_successful',
        'signal_performance_score'
    ];

    protected $casts = [
        'strength' => 'decimal:3',
        'scalping_score' => 'decimal:3',
        'price_at_signal' => 'decimal:8',
        'signal_data' => 'array',
        'contributing_timeframes' => 'array',
        'rsi_at_signal' => 'decimal:2',
        'spread_at_signal' => 'decimal:3',
        'volatility_at_signal' => 'decimal:4',
        'market_conditions' => 'array',
        'was_traded' => 'boolean',
        'max_price_move' => 'decimal:4',
        'was_successful' => 'boolean',
        'signal_performance_score' => 'decimal:3',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ScalpingTradingBot::class, 'scalping_trading_bot_id');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(ScalpingTrade::class, 'scalping_trade_id');
    }

    // Signal quality methods
    public function getOverallQuality(): float
    {
        $quality = ($this->strength + $this->scalping_score) / 2;
        
        // Bonus for confluence
        if ($this->confluence >= 2) {
            $quality += 0.1;
        }
        
        // Bonus for high urgency
        if ($this->urgency === 'high') {
            $quality += 0.05;
        }
        
        return min($quality, 1.0);
    }

    public function shouldTrade(): bool
    {
        // Basic quality check
        if ($this->getOverallQuality() < 0.6) {
            return false;
        }

        // Check market conditions
        $conditions = $this->market_conditions ?? [];
        
        // Don't trade if spread is too wide
        if (isset($conditions['spread']) && $conditions['spread'] > 0.15) {
            return false;
        }

        // Don't trade if volatility is too low
        if (isset($conditions['volatility']) && $conditions['volatility'] < 0.5) {
            return false;
        }

        return true;
    }

    public function trackPerformance(float $currentPrice, int $minutesElapsed): void
    {
        if ($this->was_traded) {
            return; // Already being tracked by trade
        }

        // Calculate max price movement in signal direction
        $priceChange = (($currentPrice - $this->price_at_signal) / $this->price_at_signal) * 100;
        
        if ($this->direction === 'short') {
            $priceChange = -$priceChange;
        }

        // Update max price move if this is better
        if ($priceChange > ($this->max_price_move ?? 0)) {
            $this->update([
                'max_price_move' => $priceChange,
                'signal_duration_minutes' => $minutesElapsed
            ]);
        }

        // After 15 minutes, evaluate signal performance
        if ($minutesElapsed >= 15 && $this->signal_performance_score === null) {
            $this->evaluateSignalPerformance();
        }
    }

    private function evaluateSignalPerformance(): void
    {
        $score = 0;
        $maxMove = $this->max_price_move ?? 0;

        // Score based on price movement in signal direction
        if ($maxMove >= 2.0) {
            $score = 1.0; // Excellent signal
        } elseif ($maxMove >= 1.0) {
            $score = 0.8; // Good signal
        } elseif ($maxMove >= 0.5) {
            $score = 0.6; // Average signal
        } elseif ($maxMove >= 0.0) {
            $score = 0.4; // Weak signal
        } else {
            $score = 0.2; // Poor signal (moved against)
        }

        // Adjust for signal strength prediction accuracy
        $strengthAccuracy = min($maxMove / 2.0, 1.0); // How well strength predicted movement
        $strengthBonus = abs($this->strength - $strengthAccuracy) < 0.2 ? 0.1 : 0;

        $finalScore = min($score + $strengthBonus, 1.0);

        $this->update([
            'signal_performance_score' => $finalScore,
            'was_successful' => $finalScore >= 0.6
        ]);
    }

    // Learning methods
    public static function analyzeSignalTypePerformance(string $signalType, int $days = 7): array
    {
        $signals = self::where('signal_type', $signalType)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('signal_performance_score')
            ->get();

        if ($signals->isEmpty()) {
            return [];
        }

        $avgScore = $signals->avg('signal_performance_score');
        $successRate = $signals->where('was_successful', true)->count() / $signals->count() * 100;
        $avgMaxMove = $signals->avg('max_price_move');
        $tradedSignals = $signals->where('was_traded', true);
        $tradeSuccessRate = $tradedSignals->isEmpty() ? 0 : 
            $tradedSignals->whereHas('trade', function($q) {
                $q->where('net_pnl', '>', 0);
            })->count() / $tradedSignals->count() * 100;

        return [
            'signal_count' => $signals->count(),
            'avg_performance_score' => $avgScore,
            'success_rate' => $successRate,
            'avg_max_move' => $avgMaxMove,
            'trade_success_rate' => $tradeSuccessRate,
            'signals_traded' => $tradedSignals->count(),
            'quality_rating' => $avgScore >= 0.7 ? 'excellent' : ($avgScore >= 0.5 ? 'good' : 'poor')
        ];
    }

    public static function analyzeTimeframePerformance(string $timeframe, int $days = 7): array
    {
        $signals = self::where('timeframe', $timeframe)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('signal_performance_score')
            ->get();

        if ($signals->isEmpty()) {
            return [];
        }

        $avgScore = $signals->avg('signal_performance_score');
        $bestSignalTypes = $signals->groupBy('signal_type')
            ->map(function($typeSignals) {
                return $typeSignals->avg('signal_performance_score');
            })
            ->sortDesc()
            ->take(3);

        return [
            'signal_count' => $signals->count(),
            'avg_performance_score' => $avgScore,
            'best_signal_types' => $bestSignalTypes,
            'avg_strength' => $signals->avg('strength'),
            'avg_confluence' => $signals->avg('confluence'),
        ];
    }

    public static function getOptimalMarketConditions(int $days = 7): array
    {
        $successfulSignals = self::where('was_successful', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('market_conditions')
            ->get();

        if ($successfulSignals->isEmpty()) {
            return [];
        }

        $avgRsi = $successfulSignals->avg('rsi_at_signal');
        $avgSpread = $successfulSignals->avg('spread_at_signal');
        $avgVolatility = $successfulSignals->avg('volatility_at_signal');

        // Group by RSI ranges for long/short
        $longSignals = $successfulSignals->where('direction', 'long');
        $shortSignals = $successfulSignals->where('direction', 'short');

        return [
            'optimal_rsi_long' => $longSignals->avg('rsi_at_signal'),
            'optimal_rsi_short' => $shortSignals->avg('rsi_at_signal'),
            'optimal_spread' => $avgSpread,
            'optimal_volatility' => $avgVolatility,
            'long_count' => $longSignals->count(),
            'short_count' => $shortSignals->count(),
        ];
    }
}

