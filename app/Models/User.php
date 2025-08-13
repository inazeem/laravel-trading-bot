<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function tradingBots()
    {
        return $this->hasMany(TradingBot::class);
    }

    public function futuresTradingBots()
    {
        return $this->hasMany(FuturesTradingBot::class);
    }

    public function assetTransactions()
    {
        return $this->hasMany(AssetTransaction::class);
    }

    public function assetHoldings()
    {
        return $this->hasMany(UserAssetHolding::class);
    }

    public function getPortfolioValueAttribute()
    {
        return $this->assetHoldings->sum(function ($holding) {
            return $holding->current_value;
        });
    }

    public function getFormattedPortfolioValueAttribute()
    {
        return '$' . number_format($this->portfolio_value, 2);
    }
}
