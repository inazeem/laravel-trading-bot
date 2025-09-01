<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScalpingTradingBot extends Model
{
    protected $fillable = [
        'user_id',
        'api_key_id',
        'name',
        'exchange',
        'symbol',
        'is_active',
        'risk_percentage',
        'max_position_size',
        'min_order_value',
        'order_type',
        'limit_order_buffer',
        'min_risk_reward_ratio',
        'timeframes',
        'leverage',
        'margin_type',
        'position_side',
        'stop_loss_percentage',
        'take_profit_percentage',
        'enable_trailing_stop',
        'trailing_distance',
        'enable_breakeven',
        'breakeven_trigger',
        'enable_momentum_scalping',
        'enable_price_action_scalping',
        'enable_smart_money_scalping',
        'enable_quick_exit',
        'max_trades_per_hour',
        'cooldown_seconds',
        'max_concurrent_positions',
        'max_spread_percentage',
        'enable_bitcoin_correlation',
        'enable_volatility_filter',
        'enable_volume_filter',
        'strategy_settings',
        'total_pnl',
        'total_trades',
        'winning_trades',
        'win_rate',
        'profit_factor',
        'avg_win',
        'avg_loss',
        'avg_trade_duration_minutes',
        'max_drawdown',
        'learning_data',
        'last_learning_at',
        'best_signal_type',
        'best_timeframe',
        'best_trading_hours',
        'worst_trading_hours',
        'best_rsi_entry_level',
        'optimal_spread_threshold',
        'last_run_at',
        'status',
        'last_error',
        'consecutive_losses',
        'risk_management_paused'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'timeframes' => 'array',
        'strategy_settings' => 'array',
        'last_run_at' => 'datetime',
        'leverage' => 'integer',
        'enable_trailing_stop' => 'boolean',
        'enable_breakeven' => 'boolean',
        'enable_momentum_scalping' => 'boolean',
        'enable_price_action_scalping' => 'boolean',
        'enable_smart_money_scalping' => 'boolean',
        'enable_quick_exit' => 'boolean',
        'enable_bitcoin_correlation' => 'boolean',
        'enable_volatility_filter' => 'boolean',
        'enable_volume_filter' => 'boolean',
        'learning_data' => 'array',
        'total_pnl' => 'decimal:8',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'avg_win' => 'decimal:8',
        'avg_loss' => 'decimal:8',
        'avg_trade_duration_minutes' => 'decimal:2',
        'max_drawdown' => 'decimal:4',
        'last_learning_at' => 'datetime',
        'best_trading_hours' => 'array',
        'worst_trading_hours' => 'array',
        'consecutive_losses' => 'integer',
        'risk_management_paused' => 'boolean',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(ScalpingTrade::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(ScalpingSignal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function openTrades(): HasMany
    {
        return $this->trades()->where('status', 'open');
    }

    public function closedTrades(): HasMany
    {
        return $this->trades()->where('status', 'closed');
    }

    public function todaysTrades(): HasMany
    {
        return $this->trades()->whereDate('created_at', today());
    }

    public function tradesThisHour(): HasMany
    {
        return $this->trades()->where('created_at', '>=', now()->subHour());
    }

    // Calculated attributes
    public function getTotalPnLAttribute(): float
    {
        return $this->trades()->sum('realized_pnl') - $this->trades()->sum('fees_paid');
    }

    public function getUnrealizedPnLAttribute(): float
    {
        return $this->openTrades()->sum('unrealized_pnl');
    }

    public function getWinRateAttribute(): float
    {
        $closedTrades = $this->closedTrades();
        $totalTrades = $closedTrades->count();
        
        if ($totalTrades === 0) {
            return 0;
        }
        
        $winningTrades = $closedTrades->where('net_pnl', '>', 0)->count();
        return ($winningTrades / $totalTrades) * 100;
    }

    public function getProfitFactorAttribute(): float
    {
        $grossWins = $this->closedTrades()->where('net_pnl', '>', 0)->sum('net_pnl');
        $grossLosses = abs($this->closedTrades()->where('net_pnl', '<', 0)->sum('net_pnl'));
        
        if ($grossLosses == 0) {
            return $grossWins > 0 ? 999 : 0;
        }
        
        return $grossWins / $grossLosses;
    }

    public function getAverageTradeTimeAttribute(): float
    {
        return $this->closedTrades()->avg('trade_duration_seconds') / 60; // in minutes
    }

    // Helper methods
    public function canTrade(): bool
    {
        if (!$this->is_active || $this->risk_management_paused) {
            return false;
        }

        // Check if we've hit max trades per hour
        $tradesThisHour = $this->tradesThisHour()->count();
        if ($tradesThisHour >= $this->max_trades_per_hour) {
            return false;
        }

        // Check cooldown
        $lastTrade = $this->trades()->latest()->first();
        if ($lastTrade && $lastTrade->created_at->diffInSeconds(now()) < $this->cooldown_seconds) {
            return false;
        }

        return true;
    }

    public function hasMaxConcurrentPositions(): bool
    {
        return $this->openTrades()->count() >= $this->max_concurrent_positions;
    }

    public function shouldPauseRiskManagement(): bool
    {
        // Pause if too many consecutive losses
        return $this->consecutive_losses >= 5;
    }

    public function getOptimalTimeframes(): array
    {
        // Return timeframes ordered by performance
        $learning = $this->learning_data ?? [];
        
        if (isset($learning['timeframe_performance'])) {
            $sorted = collect($learning['timeframe_performance'])
                ->sortByDesc('win_rate')
                ->keys()
                ->toArray();
            
            return array_intersect($sorted, $this->timeframes);
        }
        
        return $this->timeframes;
    }

    public function updatePerformanceStats(): void
    {
        $closedTrades = $this->closedTrades;
        
        $this->update([
            'total_trades' => $closedTrades->count(),
            'winning_trades' => $closedTrades->where('net_pnl', '>', 0)->count(),
            'total_pnl' => $closedTrades->sum('net_pnl'),
            'avg_win' => $closedTrades->where('net_pnl', '>', 0)->avg('net_pnl') ?? 0,
            'avg_loss' => $closedTrades->where('net_pnl', '<', 0)->avg('net_pnl') ?? 0,
            'avg_trade_duration_minutes' => $closedTrades->avg('trade_duration_seconds') / 60,
        ]);
    }

    // Learning methods
    public function learnFromTrades(): void
    {
        $recentTrades = $this->closedTrades()
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($recentTrades->count() < 10) {
            return; // Not enough data
        }

        $learning = $this->learning_data ?? [];

        // Analyze best signal types
        $signalPerformance = $recentTrades->groupBy('signal_type')
            ->map(function ($trades, $signalType) {
                $winRate = $trades->where('net_pnl', '>', 0)->count() / $trades->count() * 100;
                $avgPnl = $trades->avg('net_pnl');
                return [
                    'win_rate' => $winRate,
                    'avg_pnl' => $avgPnl,
                    'trade_count' => $trades->count(),
                    'score' => ($winRate * $avgPnl) / 100
                ];
            });

        $bestSignalType = $signalPerformance->sortByDesc('score')->keys()->first();

        // Analyze best timeframes
        $timeframePerformance = $recentTrades->groupBy('primary_timeframe')
            ->map(function ($trades, $timeframe) {
                $winRate = $trades->where('net_pnl', '>', 0)->count() / $trades->count() * 100;
                $avgPnl = $trades->avg('net_pnl');
                return [
                    'win_rate' => $winRate,
                    'avg_pnl' => $avgPnl,
                    'trade_count' => $trades->count(),
                ];
            });

        $bestTimeframe = $timeframePerformance->sortByDesc('win_rate')->keys()->first();

        // Analyze best RSI levels
        $rsiAnalysis = $recentTrades->where('entry_rsi', '!=', null)
            ->groupBy(function ($trade) {
                return floor($trade->entry_rsi / 10) * 10; // Group by RSI ranges
            })
            ->map(function ($trades) {
                return [
                    'win_rate' => $trades->where('net_pnl', '>', 0)->count() / $trades->count() * 100,
                    'trade_count' => $trades->count(),
                ];
            })
            ->where('trade_count', '>=', 3); // At least 3 trades

        $bestRsiLevel = $rsiAnalysis->sortByDesc('win_rate')->keys()->first();

        // Update learning data
        $this->update([
            'learning_data' => array_merge($learning, [
                'signal_performance' => $signalPerformance,
                'timeframe_performance' => $timeframePerformance,
                'rsi_analysis' => $rsiAnalysis,
                'last_analysis_at' => now()->toISOString(),
                'trades_analyzed' => $recentTrades->count(),
            ]),
            'best_signal_type' => $bestSignalType,
            'best_timeframe' => $bestTimeframe,
            'best_rsi_entry_level' => $bestRsiLevel,
            'last_learning_at' => now(),
        ]);
    }
}

