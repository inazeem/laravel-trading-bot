<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAssetHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_id',
        'quantity',
        'average_buy_price',
        'total_invested'
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'average_buy_price' => 'decimal:8',
        'total_invested' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function getCurrentValueAttribute()
    {
        return $this->quantity * $this->asset->current_price;
    }

    public function getProfitLossAttribute()
    {
        return $this->current_value - $this->total_invested;
    }

    public function getProfitLossPercentageAttribute()
    {
        if ($this->total_invested == 0) {
            return 0;
        }
        return (($this->current_value - $this->total_invested) / $this->total_invested) * 100;
    }

    public function getFormattedCurrentValueAttribute()
    {
        return '$' . number_format($this->current_value, 2);
    }

    public function getFormattedProfitLossAttribute()
    {
        $profitLoss = $this->profit_loss;
        $prefix = $profitLoss >= 0 ? '+' : '';
        return $prefix . '$' . number_format($profitLoss, 2);
    }

    public function getFormattedProfitLossPercentageAttribute()
    {
        $percentage = $this->profit_loss_percentage;
        $prefix = $percentage >= 0 ? '+' : '';
        return $prefix . number_format($percentage, 2) . '%';
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 8);
    }

    public function getFormattedAverageBuyPriceAttribute()
    {
        return '$' . number_format($this->average_buy_price, 2);
    }

    public function getFormattedTotalInvestedAttribute()
    {
        return '$' . number_format($this->total_invested, 2);
    }
}
