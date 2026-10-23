<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Models\User;
use App\Services\Payments\Gateways\CashOnDeliveryGateway;
use App\Services\Payments\Gateways\StripeGateway;
use App\Services\Payments\PaymentGatewayManager;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManagerInterface::class, static fn (): ImageManager => new ImageManager(GdDriver::class));

        $this->app->tag([CashOnDeliveryGateway::class, StripeGateway::class], 'payment.gateways');

        $this->app->singleton(
            PaymentGatewayManager::class,
            static fn (Application $app): PaymentGatewayManager => new PaymentGatewayManager($app->tagged('payment.gateways')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureModels();
        $this->configureAuthorization();
        $this->configureRateLimiting();
        $this->configureApiDocumentation();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Error pages can be rendered before the Inertia middleware has run.
        Inertia::setRootView('master');

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => $this->app->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Surface lazy loading and silently dropped attributes while developing.
     */
    protected function configureModels(): void
    {
        $strict = !$this->app->isProduction();

        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
    }

    protected function configureAuthorization(): void
    {
        Gate::define('access-admin', static fn (User $user): bool => $user->isAdmin() && $user->is_active);

        Gate::define('viewApiDocs', static fn (?User $user): bool => !app()->isProduction() || ($user?->isAdmin() ?? false));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('api-auth', static fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));

        RateLimiter::for('search', static fn (Request $request): Limit => Limit::perMinute(120)->by((string) $request->ip()));

        RateLimiter::for('contact', static fn (Request $request): Limit => Limit::perHour(5)->by((string) $request->ip()));

        RateLimiter::for('checkout', static fn (Request $request): Limit => Limit::perMinute(10)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }

    protected function configureApiDocumentation(): void
    {
        Scramble::configure()->withDocumentTransformers(static function (OpenApi $openApi): void {
            $openApi->secure(SecurityScheme::http('bearer', 'JWT'));
        });
    }
}
