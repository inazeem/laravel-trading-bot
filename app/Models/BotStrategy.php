<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BotStrategy extends Model
{
    protected $fillable = [
        'strategy_id',
        'bot_type',
        'bot_id',
        'parameters',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the strategy
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    /**
     * Get the bot (polymorphic)
     */
    public function bot(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get merged parameters (strategy defaults + bot custom)
     */
    public function getMergedParameters(): array
    {
        $strategyDefaults = $this->strategy->default_parameters ?? [];
        $botCustom = $this->parameters ?? [];
        
        return array_merge($strategyDefaults, $botCustom);
    }

    /**
     * Validate bot parameters against strategy requirements
     */
    public function validateParameters(): array
    {
        $errors = [];
        $mergedParams = $this->getMergedParameters();
        
        foreach ($this->strategy->parameters as $param) {
            $value = $mergedParams[$param->parameter_name] ?? null;
            
            // Check required parameters
            if ($param->is_required && $value === null) {
                $errors[] = "Required parameter '{$param->parameter_name}' is missing";
                continue;
            }
            
            // Validate parameter value
            if ($value !== null && !$param->validateValue($value)) {
                $errors[] = "Invalid value for parameter '{$param->parameter_name}'";
            }
        }
        
        return $errors;
    }

    /**
     * Get parameter value with proper formatting
     */
    public function getParameterValue(string $parameterName, $default = null)
    {
        $mergedParams = $this->getMergedParameters();
        $value = $mergedParams[$parameterName] ?? $default;
        
        // Find the parameter definition to format the value
        $paramDef = $this->strategy->parameters()
            ->where('parameter_name', $parameterName)
            ->first();
            
        if ($paramDef) {
            return $paramDef->formatValue($value);
        }
        
        return $value;
    }

    /**
     * Scope for active bot strategies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('created_at');
    }
}
