<?php

namespace App\Policies;

use App\Models\ScalpingTradingBot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScalpingTradingBotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their own scalping bots
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScalpingTradingBot $scalpingTradingBot): bool
    {
        return $user->id === $scalpingTradingBot->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create scalping bots
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScalpingTradingBot $scalpingTradingBot): bool
    {
        return $user->id === $scalpingTradingBot->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScalpingTradingBot $scalpingTradingBot): bool
    {
        return $user->id === $scalpingTradingBot->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScalpingTradingBot $scalpingTradingBot): bool
    {
        return $user->id === $scalpingTradingBot->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScalpingTradingBot $scalpingTradingBot): bool
    {
        return $user->id === $scalpingTradingBot->user_id;
    }
}

