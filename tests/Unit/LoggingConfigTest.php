<?php

namespace Tests\Unit;

use Tests\TestCase;

class LoggingConfigTest extends TestCase
{
    public function test_daily_log_paths_include_app_slug_and_environment(): void
    {
        $slug = (string) config('logging.app_slug');
        $env = strtolower((string) config('app.env'));

        $this->assertNotSame('', $slug);

        $dailyPath = (string) config('logging.channels.daily.path');
        $apiPath = (string) config('logging.channels.api.path');
        $emergencyPath = (string) config('logging.channels.emergency.path');

        $this->assertStringContainsString($slug.'-'.$env, $dailyPath);
        $this->assertStringContainsString($slug.'-api-'.$env, $apiPath);
        $this->assertStringContainsString($slug.'-'.$env.'-emergency', $emergencyPath);
    }
}
