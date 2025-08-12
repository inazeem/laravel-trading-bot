<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }
}
