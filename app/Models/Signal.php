<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signal extends Model
{
    protected $fillable = [
        'trading_bot_id',
        'signal_type',
        'timeframe',
        'symbol',
        'price',
        'strength',
        'direction',
        'support_level',
        'resistance_level',
        'stop_loss',
        'take_profit',
        'risk_reward_ratio',
        'is_executed',
        'executed_at',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'support_level' => 'decimal:8',
        'resistance_level' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'risk_reward_ratio' => 'decimal:4',
        'is_executed' => 'boolean',
        'executed_at' => 'datetime',
    ];

    public function tradingBot(): BelongsTo
    {
        return $this->belongsTo(TradingBot::class);
    }
}
