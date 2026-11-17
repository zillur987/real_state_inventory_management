<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

final class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Hasher $hasher,
        private readonly AuthFactory $auth,
    ) {
    }

    public function login(array $credentials, bool $remember = false): User
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (
            $user === null
            || ! $this->hasher->check($credentials['password'], $user->password)
            || ! $user->is_active
        ) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $this->auth->guard('web')->login($user, $remember);

        return $user;
    }

    public function logout(): void
    {
        $this->auth->guard('web')->logout();
    }
}
