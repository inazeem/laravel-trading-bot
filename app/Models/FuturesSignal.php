<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuturesSignal extends Model
{
    protected $fillable = [
        'futures_trading_bot_id',
        'symbol',
        'timeframe',
        'direction',
        'signal_type',
        'strength',
        'price',
        'stop_loss',
        'take_profit',
        'risk_reward_ratio',
        'signal_data',
        'executed',
        'futures_trade_id'
    ];

    protected $casts = [
        'strength' => 'decimal:4',
        'price' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
        'risk_reward_ratio' => 'decimal:2',
        'signal_data' => 'array',
        'executed' => 'boolean',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(FuturesTradingBot::class, 'futures_trading_bot_id');
    }

    public function trade(): BelongsTo
    {
        return $this->belongsTo(FuturesTrade::class, 'futures_trade_id');
    }

    public function isLong(): bool
    {
        return $this->direction === 'long';
    }

    public function isShort(): bool
    {
        return $this->direction === 'short';
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function markAsExecuted(FuturesTrade $trade): void
    {
        $this->update([
            'executed' => true,
            'futures_trade_id' => $trade->id
        ]);
    }
}
