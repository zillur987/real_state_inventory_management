<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use Illuminate\Support\ServiceProvider;

/**
 * Single place to bind abstractions to implementations (Dependency Inversion).
 * Add one line here for every new repository / service.
 */
final class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        AuthServiceInterface::class => AuthService::class,
    ];
}
