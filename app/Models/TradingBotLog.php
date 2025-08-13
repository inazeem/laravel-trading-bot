<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingBotLog extends Model
{
    protected $fillable = [
        'trading_bot_id',
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
}
