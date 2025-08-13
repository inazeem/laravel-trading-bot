<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingBot extends Model
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
        'strategy_settings',
        'last_run_at',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'timeframes' => 'array',
        'strategy_settings' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class);
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
        return $this->hasMany(TradingBotLog::class);
    }
}
