<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Contracts\Services\AuthServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $authService)
    {
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::guard('web')->attempt(
        $credentials,
        $request->boolean('remember')
    )) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $request->session()->regenerate();

    return response()->json([
        'success' => true,
        'message' => 'Login successful.',
        'data' => [
            'user' => $request->user('web'),
        ],
    ]);
}

    public function logout(Request $request)
{
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
        'success' => true,
        'message' => 'Logout successful.',
    ]);
}

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
