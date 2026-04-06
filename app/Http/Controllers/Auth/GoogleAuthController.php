<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        return redirect()->intended(route('dashboard'));
    }
}
