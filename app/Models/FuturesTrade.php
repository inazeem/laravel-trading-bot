<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuturesTrade extends Model
{
    protected $fillable = [
        'futures_trading_bot_id',
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
        'status',
        'order_id',
        'exchange_response',
        'opened_at',
        'closed_at'
    ];

    protected $casts = [
        'unrealized_pnl' => 'decimal:8',
        'realized_pnl' => 'decimal:8',
        'pnl_percentage' => 'decimal:4',
        'exchange_response' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(FuturesTradingBot::class, 'futures_trading_bot_id');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(FuturesSignal::class);
    }

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
        return $this->realized_pnl > 0;
    }

    public function isLosing(): bool
    {
        return $this->realized_pnl < 0;
    }

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

    public function calculatePnLPercentage(): float
    {
        if ($this->entry_price == 0) {
            return 0;
        }

        $pnl = $this->isClosed() ? $this->realized_pnl : $this->unrealized_pnl;
        $positionValue = $this->entry_price * $this->quantity;
        
        return ($pnl / $positionValue) * 100;
    }
}
