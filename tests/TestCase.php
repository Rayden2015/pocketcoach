<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        // PHPUnit sets PAYSTACK_SECRET_KEY in phpunit.xml, but config may still read an empty
        // value from .env during bootstrap; keep services.paystack in sync for webhook tests.
        $paystackSecret = getenv('PAYSTACK_SECRET_KEY');
        if (is_string($paystackSecret) && $paystackSecret !== '') {
            config(['services.paystack.secret_key' => $paystackSecret]);
        }

        $vite = new class extends Vite
        {
            public function __invoke($entrypoints, $buildDirectory = null)
            {
                return new HtmlString('');
            }
        };
        $this->app->instance(Vite::class, $vite);
        $this->app->instance('Illuminate\Foundation\Vite', $vite);
    }
}
