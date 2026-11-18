<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\OwnerContactRequestRepositoryInterface;
use App\Contracts\Repositories\OwnerRepositoryInterface;
use App\Contracts\Repositories\PropertyRepositoryInterface;
use App\Contracts\Repositories\PropertySearchRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\OwnerContactRequestServiceInterface;
use App\Contracts\Services\OwnerServiceInterface;
use App\Contracts\Services\PropertySearchServiceInterface;
use App\Contracts\Services\PropertyServiceInterface;
use App\Repositories\OwnerContactRequestRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\PropertySearchRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Owner\OwnerContactRequestService;
use App\Services\Owner\OwnerService;
use App\Services\Property\PropertySearchService;
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
        PropertySearchRepositoryInterface::class => PropertySearchRepository::class,
        PropertySearchServiceInterface::class => PropertySearchService::class,
        OwnerRepositoryInterface::class => OwnerRepository::class,
        OwnerContactRequestRepositoryInterface::class => OwnerContactRequestRepository::class,
        OwnerServiceInterface::class => OwnerService::class,
        OwnerContactRequestServiceInterface::class => OwnerContactRequestService::class,
    ];
}
