<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'exchange',
        'name',
        'api_key',
        'api_secret',
        'passphrase',
        'is_active',
        'permissions',
        'last_used_at',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'passphrase',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tradingBots()
    {
        return $this->hasMany(TradingBot::class, 'api_key_id');
    }

    // Encrypt API credentials before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($apiKey) {
            if ($apiKey->isDirty('api_key')) {
                $apiKey->api_key = Crypt::encryptString($apiKey->api_key);
            }
            if ($apiKey->isDirty('api_secret')) {
                $apiKey->api_secret = Crypt::encryptString($apiKey->api_secret);
            }
            if ($apiKey->isDirty('passphrase') && $apiKey->passphrase) {
                $apiKey->passphrase = Crypt::encryptString($apiKey->passphrase);
            }
        });
    }

    // Decrypt API credentials when accessing
    public function getDecryptedApiKeyAttribute()
    {
        return Crypt::decryptString($this->api_key);
    }

    public function getDecryptedApiSecretAttribute()
    {
        return Crypt::decryptString($this->api_secret);
    }

    public function getDecryptedPassphraseAttribute()
    {
        return $this->passphrase ? Crypt::decryptString($this->passphrase) : null;
    }

    // Check if API key has specific permission
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    // Get available permissions for an exchange
    public static function getAvailablePermissions(string $exchange): array
    {
        return match($exchange) {
            'kucoin' => ['read', 'trade', 'transfer'],
            'binance' => ['read', 'trade', 'transfer'],
            default => ['read']
        };
    }
}
