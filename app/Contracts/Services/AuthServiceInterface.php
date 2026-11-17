<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(array $credentials, bool $remember = false): User;

    public function logout(): void;
}
