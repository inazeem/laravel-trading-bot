<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingBotLog extends Model
{
    protected $fillable = [
        'trading_bot_id',
        'futures_trading_bot_id',
        'bot_type',
        'level',
        'category',
        'message',
        'context',
        'logged_at'
    ];

    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
    ];

    public function tradingBot(): BelongsTo
    {
        return $this->belongsTo(TradingBot::class);
    }

    public function futuresTradingBot(): BelongsTo
    {
        return $this->belongsTo(FuturesTradingBot::class, 'futures_trading_bot_id');
    }

    // Scopes for filtering
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    public function scopeByBotType($query, $botType)
    {
        return $query->where('bot_type', $botType);
    }

    public function scopeForTradingBot($query, $tradingBotId)
    {
        return $query->where('trading_bot_id', $tradingBotId)->where('bot_type', 'trading_bot');
    }

    public function scopeForFuturesTradingBot($query, $futuresTradingBotId)
    {
        return $query->where('futures_trading_bot_id', $futuresTradingBotId)->where('bot_type', 'futures_trading_bot');
    }
}
