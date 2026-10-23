<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\ApiAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Issues JSON Web Tokens for API clients.
 *
 * Send the token as `Authorization: Bearer <token>`.
 */
final class AuthController extends Controller
{
    public function __construct(private readonly ApiAuthService $auth) {}

    /**
     * Register a customer account.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->auth->register($request->validated());

        return $this->tokenResponse($token, UserResource::make($user)->resolve($request), 201);
    }

    /**
     * Exchange credentials for an access token.
     *
     * Accounts with two-factor authentication enabled must also send `two_factor_code`
     * (an authenticator code or a recovery code).
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->auth->login(
            (string) $request->validated('email'),
            (string) $request->validated('password'),
            $request->validated('two_factor_code'),
        );

        return $this->tokenResponse($token, UserResource::make($user)->resolve($request));
    }

    /**
     * Exchange the current token for a fresh one.
     */
    public function refresh(): JsonResponse
    {
        return response()->json($this->auth->tokenPayload($this->auth->refresh()));
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): Response
    {
        $this->auth->logout();

        return response()->noContent();
    }

    /**
     * The authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    /**
     * @param array<string, mixed> $user
     */
    private function tokenResponse(string $token, array $user, int $status = 200): JsonResponse
    {
        return response()->json([...$this->auth->tokenPayload($token), 'user' => $user], $status);
    }
}
