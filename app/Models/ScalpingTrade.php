<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScalpingTrade extends Model
{
    protected $fillable = [
        'scalping_trading_bot_id',
        'symbol',
        'side',
        'quantity',
        'entry_price',
        'exit_price',
        'stop_loss',
        'take_profit',
        'leverage',
        'margin_type',
        'unrealized_pnl',
        'realized_pnl',
        'pnl_percentage',
        'fees_paid',
        'net_pnl',
        'order_id',
        'stop_loss_order_id',
        'take_profit_order_id',
        'trailing_stop_order_id',
        'signal_type',
        'entry_reason',
        'signal_strength',
        'scalping_score',
        'confluence',
        'signal_timeframes',
        'primary_timeframe',
        'exit_reason',
        'was_trailing_stop_used',
        'was_quick_exit',
        'trade_duration_seconds',
        'max_favorable_excursion',
        'max_adverse_excursion',
        'entry_spread_percentage',
        'entry_rsi',
        'entry_market_data',
        'status',
        'exchange_response',
        'opened_at',
        'closed_at'
    ];

    protected $casts = [
        'unrealized_pnl' => 'decimal:8',
        'realized_pnl' => 'decimal:8',
        'pnl_percentage' => 'decimal:4',
        'fees_paid' => 'decimal:8',
        'net_pnl' => 'decimal:8',
        'signal_strength' => 'decimal:3',
        'scalping_score' => 'decimal:3',
        'signal_timeframes' => 'array',
        'was_trailing_stop_used' => 'boolean',
        'was_quick_exit' => 'boolean',
        'max_favorable_excursion' => 'decimal:4',
        'max_adverse_excursion' => 'decimal:4',
        'entry_spread_percentage' => 'decimal:3',
        'entry_rsi' => 'decimal:2',
        'entry_market_data' => 'array',
        'exchange_response' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ScalpingTradingBot::class, 'scalping_trading_bot_id');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(ScalpingSignal::class);
    }

    // Trade state methods
    public function isLong(): bool
    {
        return $this->side === 'long';
    }

    public function isShort(): bool
    {
        return $this->side === 'short';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isWinning(): bool
    {
        return $this->net_pnl > 0;
    }

    public function isLosing(): bool
    {
        return $this->net_pnl < 0;
    }

    public function isBreakeven(): bool
    {
        return abs($this->net_pnl) < 0.01; // Within 1 cent
    }

    // Performance calculations
    public function calculateUnrealizedPnL(float $currentPrice): float
    {
        if ($this->isClosed()) {
            return 0;
        }

        if ($this->isLong()) {
            return ($currentPrice - $this->entry_price) * $this->quantity;
        } else {
            return ($this->entry_price - $currentPrice) * $this->quantity;
        }
    }

    public function calculatePnLPercentage(float $currentPrice = null): float
    {
        if ($this->entry_price == 0) {
            return 0;
        }

        if ($this->isClosed()) {
            $pnl = $this->net_pnl;
        } else {
            $pnl = $currentPrice ? $this->calculateUnrealizedPnL($currentPrice) : $this->unrealized_pnl;
        }

        $positionValue = $this->entry_price * $this->quantity;
        
        return ($pnl / $positionValue) * 100;
    }

    public function calculateRiskReward(): float
    {
        if ($this->entry_price == 0) {
            return 0;
        }

        $riskDistance = $this->isLong() 
            ? $this->entry_price - $this->stop_loss
            : $this->stop_loss - $this->entry_price;

        $rewardDistance = $this->isLong()
            ? $this->take_profit - $this->entry_price
            : $this->entry_price - $this->take_profit;

        return $riskDistance > 0 ? $rewardDistance / $riskDistance : 0;
    }

    public function getTradeDurationAttribute(): string
    {
        if (!$this->trade_duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->trade_duration_seconds / 60);
        $seconds = $this->trade_duration_seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }
        
        return "{$seconds}s";
    }

    // Performance analysis
    public function getTradeEfficiency(): float
    {
        if (!$this->max_favorable_excursion || $this->max_favorable_excursion == 0) {
            return 0;
        }

        $actualPnl = $this->isClosed() ? $this->pnl_percentage : $this->calculatePnLPercentage();
        return ($actualPnl / $this->max_favorable_excursion) * 100;
    }

    public function getTradeQualityScore(): float
    {
        $score = 0;

        // Signal quality (40%)
        $score += ($this->signal_strength * $this->scalping_score) * 0.4;

        // Trade outcome (30%)
        if ($this->isClosed()) {
            $outcome = $this->isWinning() ? 1 : 0;
            $score += $outcome * 0.3;
        }

        // Efficiency (20%)
        $efficiency = $this->getTradeEfficiency() / 100;
        $score += min($efficiency, 1) * 0.2;

        // Duration appropriateness (10%) - shorter is better for scalping
        if ($this->trade_duration_seconds) {
            $optimalDuration = 300; // 5 minutes
            $durationScore = max(0, 1 - ($this->trade_duration_seconds / $optimalDuration));
            $score += $durationScore * 0.1;
        }

        return min($score, 1); // Cap at 1.0
    }

    // Market conditions analysis
    public function wasEntryOptimal(): bool
    {
        $checks = [];

        // Spread check
        if ($this->entry_spread_percentage !== null) {
            $checks['spread'] = $this->entry_spread_percentage <= 0.1; // 0.1% max
        }

        // RSI check (if momentum scalping)
        if ($this->signal_type === 'momentum_scalping' && $this->entry_rsi !== null) {
            if ($this->isLong()) {
                $checks['rsi'] = $this->entry_rsi <= 35; // Oversold entry for long
            } else {
                $checks['rsi'] = $this->entry_rsi >= 65; // Overbought entry for short
            }
        }

        // Signal strength check
        $checks['signal_strength'] = $this->signal_strength >= 0.7;

        // Confluence check
        $checks['confluence'] = $this->confluence >= 1;

        return collect($checks)->filter()->count() >= (count($checks) * 0.7); // 70% of checks pass
    }

    // Learning methods
    public function analyzeExitOptimization(): array
    {
        $analysis = [];

        if ($this->isClosed()) {
            // Was the exit optimal?
            $maxGain = $this->max_favorable_excursion;
            $actualGain = $this->pnl_percentage;

            if ($maxGain > 0) {
                $exitEfficiency = ($actualGain / $maxGain) * 100;
                $analysis['exit_efficiency'] = $exitEfficiency;
                $analysis['could_improve'] = $exitEfficiency < 70;
            }

            // Should trailing stop have been used?
            if (!$this->was_trailing_stop_used && $maxGain > 2) {
                $analysis['should_use_trailing'] = true;
            }

            // Was quick exit appropriate?
            if ($this->was_quick_exit) {
                $analysis['quick_exit_result'] = $this->isWinning() ? 'good' : 'premature';
            }
        }

        return $analysis;
    }

    public function updateMaxExcursions(float $currentPrice): void
    {
        if ($this->isClosed()) {
            return;
        }

        $currentPnlPercentage = $this->calculatePnLPercentage($currentPrice);
        
        // Update max favorable excursion (best profit)
        if ($currentPnlPercentage > $this->max_favorable_excursion) {
            $this->update(['max_favorable_excursion' => $currentPnlPercentage]);
        }

        // Update max adverse excursion (worst loss)
        if ($currentPnlPercentage < $this->max_adverse_excursion) {
            $this->update(['max_adverse_excursion' => $currentPnlPercentage]);
        }
    }

    public function shouldQuickExit(float $currentPrice, array $marketSignals = []): bool
    {
        if (!$this->bot->enable_quick_exit) {
            return false;
        }

        // Check for opposite signals
        $oppositeSignals = collect($marketSignals)
            ->where('direction', '!=', $this->side)
            ->where('strength', '>', 0.7);

        if ($oppositeSignals->count() >= 2) {
            return true;
        }

        // Check for adverse price movement beyond threshold
        $currentPnl = $this->calculatePnLPercentage($currentPrice);
        
        // If losing more than 50% of stop loss, consider quick exit
        $stopLossPercentage = $this->isLong() 
            ? (($this->entry_price - $this->stop_loss) / $this->entry_price) * 100
            : (($this->stop_loss - $this->entry_price) / $this->entry_price) * 100;

        if ($currentPnl < ($stopLossPercentage * -0.5)) {
            return true;
        }

        return false;
    }
}

