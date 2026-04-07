<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Models\Tenant;
use App\Services\Payments\PaystackClient;
use App\Services\Payments\PaystackGateway;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaystackClient::class, function ($app): PaystackClient {
            return new PaystackClient(
                secretKey: (string) config('services.paystack.secret_key', ''),
                baseUrl: (string) config('services.paystack.base_url', 'https://api.paystack.co'),
            );
        });

        $this->app->singleton(PaymentGateway::class, PaystackGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('tenant', function (string $value): Tenant {
            $slug = strtolower($value);
            if (in_array($slug, config('tenancy.reserved_slugs', []), true)) {
                abort(404);
            }

            return Tenant::query()
                ->where('slug', $slug)
                ->where('status', Tenant::STATUS_ACTIVE)
                ->firstOrFail();
        });

        Route::bind('adminTenant', function (string $value): Tenant {
            return Tenant::query()->findOrFail((int) $value);
        });
    }
}
