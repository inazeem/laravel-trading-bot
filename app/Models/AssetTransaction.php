<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_id',
        'type',
        'quantity',
        'price_per_unit',
        'total_amount',
        'status',
        'notes',
        'exchange_order_id',
        'exchange_response'
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price_per_unit' => 'decimal:8',
        'total_amount' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    public function getFormattedPricePerUnitAttribute()
    {
        return '$' . number_format($this->price_per_unit, 2);
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 8);
    }
}
