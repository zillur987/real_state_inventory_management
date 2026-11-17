<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\PropertyRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\PropertyServiceInterface;
use App\Repositories\PropertyRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Property\PropertyService;
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
        PropertyRepositoryInterface::class => PropertyRepository::class,
        PropertyServiceInterface::class => PropertyService::class,
    ];
}
