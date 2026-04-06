<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GoogleAccountService;
use App\Services\Auth\GoogleIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function google(
        Request $request,
        GoogleAccountService $linker,
        GoogleIdTokenVerifier $verifier,
    ): JsonResponse {
        if ((string) config('services.google.client_id') === '') {
            return response()->json(['message' => 'Google sign-in is not configured.'], 503);
        }

        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $payload = $verifier->verify($validated['id_token']);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Invalid Google token.'], 422);
        } catch (\Throwable) {
            return response()->json(['message' => 'Could not verify Google token.'], 422);
        }

        if (empty($payload['email']) || ! is_string($payload['email'])) {
            return response()->json(['message' => 'Google did not return an email.'], 422);
        }

        if (empty($payload['email_verified'])) {
            return response()->json(['message' => 'Google email must be verified.'], 422);
        }

        $user = $linker->userFromIdTokenPayload($payload);
        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
