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
        'timeframes',
        'leverage',
        'margin_type',
        'position_side',
        'stop_loss_percentage',
        'take_profit_percentage',
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
