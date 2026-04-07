<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Services\Auth\GoogleAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if ((string) config('services.google.client_id') === '') {
            abort(404);
        }

        $tenantSlug = request()->query('tenant');
        if (is_string($tenantSlug) && $tenantSlug !== '') {
            session(['oauth_intended_tenant_slug' => strtolower($tenantSlug)]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(GoogleAccountService $linker): RedirectResponse
    {
        if ((string) config('services.google.client_id') === '') {
            abort(404);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in could not be completed. Try again.']);
        }

        $user = $linker->userFromSocialite($googleUser);
        Auth::login($user, true);
        request()->session()->regenerate();

        $slug = session()->pull('oauth_intended_tenant_slug');
        if (is_string($slug) && $slug !== '') {
            $tenant = Tenant::query()->where('slug', $slug)->where('status', Tenant::STATUS_ACTIVE)->first();
            if ($tenant !== null) {
                TenantMembership::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => 'learner',
                    ],
                );

                return redirect()->intended(route('learn.catalog', $tenant));
            }
        }

        return redirect()->intended(route('dashboard'));
    }
}
