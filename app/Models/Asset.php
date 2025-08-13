<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'current_price',
        'type',
        'is_active'
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(AssetTransaction::class);
    }

    public function holdings()
    {
        return $this->hasMany(UserAssetHolding::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->current_price, 2);
    }

    public function getFormattedSymbolAttribute()
    {
        return strtoupper($this->symbol);
    }
}
