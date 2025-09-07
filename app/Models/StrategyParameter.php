<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyParameter extends Model
{
    protected $fillable = [
        'strategy_id',
        'parameter_name',
        'parameter_type',
        'description',
        'default_value',
        'min_value',
        'max_value',
        'options',
        'is_required',
        'sort_order'
    ];

    protected $casts = [
        'default_value' => 'array',
        'min_value' => 'array',
        'max_value' => 'array',
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    /**
     * Get the strategy this parameter belongs to
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(TradingStrategy::class, 'strategy_id');
    }

    /**
     * Get parameter type options
     */
    public static function getTypeOptions(): array
    {
        return [
            'integer' => 'Integer',
            'float' => 'Decimal/Float',
            'boolean' => 'Boolean (True/False)',
            'string' => 'Text/String',
            'array' => 'Array/List',
            'select' => 'Select/Dropdown',
            'multiselect' => 'Multi-Select'
        ];
    }

    /**
     * Validate parameter value
     */
    public function validateValue($value): bool
    {
        // Type validation
        switch ($this->parameter_type) {
            case 'integer':
                if (!is_int($value)) return false;
                break;
            case 'float':
                if (!is_numeric($value)) return false;
                break;
            case 'boolean':
                if (!is_bool($value)) return false;
                break;
            case 'string':
                if (!is_string($value)) return false;
                break;
            case 'array':
            case 'select':
            case 'multiselect':
                if (!is_array($value)) return false;
                break;
        }

        // Min/Max validation for numeric types
        if (in_array($this->parameter_type, ['integer', 'float'])) {
            if ($this->min_value !== null && $value < $this->min_value) {
                return false;
            }
            if ($this->max_value !== null && $value > $this->max_value) {
                return false;
            }
        }

        // Options validation for select types
        if (in_array($this->parameter_type, ['select', 'multiselect']) && !empty($this->options)) {
            if ($this->parameter_type === 'select') {
                if (!in_array($value, $this->options)) {
                    return false;
                }
            } else { // multiselect
                foreach ($value as $v) {
                    if (!in_array($v, $this->options)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get formatted parameter value
     */
    public function formatValue($value)
    {
        switch ($this->parameter_type) {
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'string':
                return (string) $value;
            case 'array':
            case 'select':
            case 'multiselect':
                return is_array($value) ? $value : [$value];
            default:
                return $value;
        }
    }

    /**
     * Scope for required parameters
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('parameter_name');
    }
}
