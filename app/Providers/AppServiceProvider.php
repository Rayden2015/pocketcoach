<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\TaskBoard\TaskBoardGateway;
use App\Models\LessonProgress;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Observers\ReflectionPromptObserver;
use App\Services\Payments\PaystackClient;
use App\Services\Payments\PaystackGateway;
use App\Services\TaskBoard\NullTaskBoardGateway;
use App\Services\TaskBoard\TrelloTaskBoardGateway;
use Illuminate\Support\Facades\Log;
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

        $this->app->singleton(TaskBoardGateway::class, function (): TaskBoardGateway {
            $driver = (string) config('task_board.driver', 'null');

            if ($driver === 'trello') {
                $gateway = new TrelloTaskBoardGateway(
                    apiKey: (string) config('task_board.trello.api_key', ''),
                    token: (string) config('task_board.trello.token', ''),
                    explicitListId: (string) config('task_board.trello.default_list_id', ''),
                    boardId: (string) config('task_board.trello.board_id', ''),
                    baseUrl: rtrim((string) config('task_board.trello.base_url', 'https://api.trello.com/1'), '/'),
                );

                if ($gateway->isEnabled()) {
                    return $gateway;
                }

                Log::warning('task_board.trello.misconfigured_using_null_driver');
            }

            return new NullTaskBoardGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ReflectionPrompt::observe(ReflectionPromptObserver::class);

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

        Route::bind('reflection_prompt', function (string $value, \Illuminate\Routing\Route $route): ReflectionPrompt {
            $tenant = $route->parameter('tenant');
            if (! $tenant instanceof Tenant) {
                abort(404);
            }

            return ReflectionPrompt::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($value)
                ->firstOrFail();
        });

        Route::bind('reflectionResponse', function (string $value, \Illuminate\Routing\Route $route): ReflectionResponse {
            $tenant = $route->parameter('tenant');
            if (! $tenant instanceof Tenant) {
                abort(404);
            }

            return ReflectionResponse::query()
                ->whereKey($value)
                ->whereHas('reflectionPrompt', fn ($q) => $q->where('tenant_id', $tenant->id))
                ->firstOrFail();
        });

        Route::bind('lessonProgress', function (string $value, \Illuminate\Routing\Route $route): LessonProgress {
            $tenant = $route->parameter('tenant');
            if (! $tenant instanceof Tenant) {
                abort(404);
            }

            return LessonProgress::query()
                ->whereKey($value)
                ->where('tenant_id', $tenant->id)
                ->firstOrFail();
        });
    }
}
