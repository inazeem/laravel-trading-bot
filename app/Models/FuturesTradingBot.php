<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesTradingBot extends Model
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
        'enable_bitcoin_correlation',
        'learning_data',
        'total_pnl',
        'total_trades',
        'winning_trades',
        'win_rate',
        'profit_factor',
        'avg_win',
        'avg_loss',
        'last_learning_at',
        'best_signal_type',
        'best_timeframe',
        'best_trading_hours',
        'worst_trading_hours',
        'strategy_settings',
        'last_run_at',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'timeframes' => 'array',
        'strategy_settings' => 'array',
        'last_run_at' => 'datetime',
        'leverage' => 'integer',
        'enable_bitcoin_correlation' => 'boolean',
        'learning_data' => 'array',
        'total_pnl' => 'decimal:8',
        'win_rate' => 'decimal:2',
        'profit_factor' => 'decimal:4',
        'avg_win' => 'decimal:8',
        'avg_loss' => 'decimal:8',
        'last_learning_at' => 'datetime',
        'best_trading_hours' => 'array',
        'worst_trading_hours' => 'array',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(FuturesTrade::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(FuturesSignal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /**
     * Get strategies attached to this futures bot
     */
    public function strategies()
    {
        return $this->morphToMany(TradingStrategy::class, 'bot', 'bot_strategies', 'bot_id', 'strategy_id')
            ->withPivot(['parameters', 'is_active', 'priority'])
            ->withTimestamps();
    }

    /**
     * Get bot strategies (pivot table)
     */
    public function botStrategies()
    {
        return $this->morphMany(BotStrategy::class, 'bot');
    }

    /**
     * Get active strategies for this futures bot
     */
    public function activeStrategies()
    {
        return $this->strategies()->wherePivot('is_active', true)->orderByPivot('priority');
    }

    public function logs()
    {
        return $this->hasMany(TradingBotLog::class, 'futures_trading_bot_id');
    }

    public function openTrades(): HasMany
    {
        return $this->trades()->where('status', 'open');
    }

    public function closedTrades(): HasMany
    {
        return $this->trades()->where('status', 'closed');
    }

    public function getTotalPnLAttribute(): float
    {
        return $this->trades()->sum('realized_pnl');
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
        
        $winningTrades = $closedTrades->where('realized_pnl', '>', 0)->count();
        return ($winningTrades / $totalTrades) * 100;
    }
}
