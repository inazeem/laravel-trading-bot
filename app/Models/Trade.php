<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'trading_bot_id',
        'exchange_order_id',
        'side',
        'symbol',
        'quantity',
        'price',
        'total',
        'fee',
        'status',
        'signal_type',
        'entry_time',
        'exit_time',
        'profit_loss',
        'profit_loss_percentage',
        'stop_loss',
        'take_profit',
        'notes'
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'quantity' => 'decimal:8',
        'price' => 'decimal:8',
        'total' => 'decimal:8',
        'fee' => 'decimal:8',
        'profit_loss' => 'decimal:8',
        'profit_loss_percentage' => 'decimal:4',
        'stop_loss' => 'decimal:8',
        'take_profit' => 'decimal:8',
    ];

    public function tradingBot(): BelongsTo
    {
        return $this->belongsTo(TradingBot::class);
    }
}
