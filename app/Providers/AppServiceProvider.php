<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Services\Payments\PaystackClient;
use App\Services\Payments\PaystackGateway;
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
        //
    }
}
