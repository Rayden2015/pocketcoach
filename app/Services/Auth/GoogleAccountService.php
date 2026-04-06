<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAccountService
{
    /**
     * Create or update a user from Google's ID token payload (keys: sub, email, email_verified, name, picture).
     *
     * @param  array<string, mixed>  $payload
     */
    public function userFromIdTokenPayload(array $payload): User
    {
        if (empty($payload['sub']) || ! is_string($payload['sub'])) {
            abort(422, 'Invalid Google token payload.');
        }

        return $this->sync(
            googleId: $payload['sub'],
            email: (string) $payload['email'],
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            avatarUrl: isset($payload['picture']) ? (string) $payload['picture'] : null,
            emailVerified: ! empty($payload['email_verified']),
        );
    }

    public function userFromSocialite(SocialiteUser $google): User
    {
        $email = $google->getEmail();
        if ($email === null || $email === '') {
            abort(422, 'Google did not return an email address.');
        }

        return $this->sync(
            googleId: (string) $google->getId(),
            email: $email,
            name: $google->getName(),
            avatarUrl: $google->getAvatar(),
            emailVerified: true,
        );
    }

    private function sync(
        string $googleId,
        string $email,
        ?string $name,
        ?string $avatarUrl,
        bool $emailVerified,
    ): User {
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if ($user->google_id !== null && $user->google_id !== $googleId) {
                abort(403, 'This email is linked to a different Google account.');
            }
            if ($user->google_id === null) {
                $user->google_id = $googleId;
            }
            if ($avatarUrl !== null && ($user->avatar_url === null || $user->avatar_url === '')) {
                $user->avatar_url = $avatarUrl;
            }
            if ($name !== null && $user->name === '') {
                $user->name = $name;
            }
            if ($emailVerified) {
                $user->email_verified_at = $user->email_verified_at ?? now();
            }
            $user->save();

            return $user;
        }

        return User::query()->create([
            'name' => $name !== null && $name !== '' ? $name : 'Learner',
            'email' => $email,
            'google_id' => $googleId,
            'avatar_url' => $avatarUrl,
            'password' => Hash::make(Str::password(32)),
            'email_verified_at' => $emailVerified ? now() : null,
        ]);
    }
}
