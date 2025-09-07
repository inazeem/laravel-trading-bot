<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TradingStrategy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'market_type',
        'default_parameters',
        'required_indicators',
        'supported_timeframes',
        'is_active',
        'is_system',
        'created_by'
    ];

    protected $casts = [
        'default_parameters' => 'array',
        'required_indicators' => 'array',
        'supported_timeframes' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get strategy parameters
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(StrategyParameter::class, 'strategy_id');
    }

    /**
     * Get the user who created this strategy
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all bots using this strategy
     */
    public function bots(): MorphToMany
    {
        return $this->morphToMany(TradingBot::class, 'bot', 'bot_strategies')
            ->withPivot(['parameters', 'is_active', 'priority'])
            ->withTimestamps();
    }

    /**
     * Get all futures bots using this strategy
     */
    public function futuresBots(): MorphToMany
    {
        return $this->morphToMany(FuturesTradingBot::class, 'bot', 'bot_strategies')
            ->withPivot(['parameters', 'is_active', 'priority'])
            ->withTimestamps();
    }

    /**
     * Get all bot strategies (both spot and futures)
     */
    public function botStrategies(): HasMany
    {
        return $this->hasMany(BotStrategy::class);
    }

    /**
     * Scope for active strategies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system strategies
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for user strategies
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope by market type
     */
    public function scopeForMarket($query, string $marketType)
    {
        return $query->where(function ($q) use ($marketType) {
            $q->where('market_type', $marketType)
              ->orWhere('market_type', 'both');
        });
    }

    /**
     * Get strategy type options
     */
    public static function getTypeOptions(): array
    {
        return [
            'trend_following' => 'Trend Following',
            'mean_reversion' => 'Mean Reversion',
            'momentum' => 'Momentum',
            'scalping' => 'Scalping',
            'swing_trading' => 'Swing Trading',
            'arbitrage' => 'Arbitrage',
            'grid_trading' => 'Grid Trading',
            'dca' => 'Dollar Cost Averaging',
            'smart_money_concept' => 'Smart Money Concept',
            'custom' => 'Custom Strategy'
        ];
    }

    /**
     * Get market type options
     */
    public static function getMarketTypeOptions(): array
    {
        return [
            'spot' => 'Spot Trading',
            'futures' => 'Futures Trading',
            'both' => 'Both Markets'
        ];
    }

    /**
     * Check if strategy supports timeframe
     */
    public function supportsTimeframe(string $timeframe): bool
    {
        if (empty($this->supported_timeframes)) {
            return true; // If no restrictions, support all
        }
        
        return in_array($timeframe, $this->supported_timeframes);
    }

    /**
     * Check if strategy requires indicator
     */
    public function requiresIndicator(string $indicator): bool
    {
        if (empty($this->required_indicators)) {
            return false;
        }
        
        return in_array($indicator, $this->required_indicators);
    }

    /**
     * Get merged parameters (default + custom)
     */
    public function getMergedParameters(array $customParameters = []): array
    {
        $defaults = $this->default_parameters ?? [];
        return array_merge($defaults, $customParameters);
    }
}
