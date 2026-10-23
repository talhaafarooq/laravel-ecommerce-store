<?php

declare(strict_types = 1);

namespace App\Services;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

/**
 * Issues and revokes JSON Web Tokens for REST API clients.
 */
final readonly class ApiAuthService
{
    public function __construct(
        private AuthFactory $auth,
        private CreateNewUser $creator,
        private TwoFactorAuthenticationProvider $twoFactor,
    ) {}

    /**
     * @param array<string, string> $input
     *
     * @return array{user: User, token: string}
     */
    public function register(array $input): array
    {
        $user = $this->creator->create($input);

        event(new Registered($user));

        return ['user' => $user, 'token' => $this->guard()->login($user)];
    }

    /**
     * @throws ValidationException
     *
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password, ?string $twoFactorCode = null): array
    {
        $guard = $this->guard();

        /** @var User|null $user */
        $user = $guard->getProvider()->retrieveByCredentials(['email' => $email]);

        if ($user === null || !$guard->getProvider()->validateCredentials($user, ['password' => $password])) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages(['email' => __('Your account has been deactivated. Please contact support.')]);
        }

        if ($user->hasEnabledTwoFactorAuthentication() && !$this->passesTwoFactor($user, $twoFactorCode)) {
            throw ValidationException::withMessages([
                'two_factor_code' => $twoFactorCode === null
                    ? __('A two-factor authentication code is required.')
                    : __('The provided two-factor authentication code was invalid.'),
            ]);
        }

        return ['user' => $user, 'token' => $guard->login($user)];
    }

    public function refresh(): string
    {
        return $this->guard()->refresh();
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    /**
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function tokenPayload(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $this->guard()->factory()->getTTL() * 60,
        ];
    }

    private function passesTwoFactor(User $user, ?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        $secret = $user->two_factor_secret === null ? null : decrypt($user->two_factor_secret);

        if (is_string($secret) && $this->twoFactor->verify($secret, $code)) {
            return true;
        }

        $recoveryCode = collect($user->recoveryCodes())->first(static fn (string $recovery): bool => hash_equals($recovery, $code));

        if ($recoveryCode === null) {
            return false;
        }

        $user->replaceRecoveryCode($recoveryCode);

        return true;
    }

    private function guard(): JWTGuard
    {
        $guard = $this->auth->guard('api');

        assert($guard instanceof JWTGuard);

        return $guard;
    }
}
