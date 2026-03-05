<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticationController extends Controller
{
    /**
     * Authenticate User Login
     */
    public function authenticate_user(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Too many login attempts. Please try again later.'
            ], 429);
        }

        $user = User::findUser($request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);

            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid username or password'
            ], 401);
        }

        if ($user->account_status !== 'active') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Account is not active'
            ], 403);
        }

        RateLimiter::clear($key);

        // Remove expired tokens
        $user->tokens()->where('expires_at', '<', now())->delete();

        // Short-lived access token
        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(30))->plainTextToken;

        // Long-lived refresh token
        $refreshToken = $user->createToken('refresh_token', ['*'], now()->addWeeks(2))->plainTextToken;

        return (new JsonResponse([
            'success' => true,
            'response' => 'Login successful',
            'user' => [
                'access_token' => $accessToken,
                'username' => $user->username,
                'email' => $user->email,
                'fullname' => $user->fullname,
                'school_code' => $user->school_code
            ],
            'access_expires_at' => now()->addMinutes(30)->toDateTimeString(),
        ], 200))->withCookie(
                cookie(
                    'refresh_token',
                    $refreshToken,
                    20160,
                    '/',
                    null,
                    app()->environment('production'), // secure in prod
                    true
                )
            );
    }

    /**
     * Refresh access token using the refresh token cookie
     */
    public function refresh_token(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return new JsonResponse(['success' => false, 'error' => 'No refresh token found'], 401);
        }

        $tokenModel = PersonalAccessToken::findToken($refreshToken);

        if (!$tokenModel || $tokenModel->tokenable->account_status !== 'active' || $tokenModel->expires_at < now()) {
            return new JsonResponse(['success' => false, 'error' => 'Refresh token invalid or expired'], 401);
        }

        $user = $tokenModel->tokenable;

        // Issue new short-lived access token
        $newAccessToken = $user->createToken('access_token', ['*'], now()->addMinutes(30))->plainTextToken;

        return new JsonResponse([
            'success' => true,
            'access_token' => $newAccessToken,
            'access_expires_at' => now()->addMinutes(30)->toDateTimeString()
        ], 200);
    }

    /**
     * Logout user by revoking refresh token cookie and access tokens
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Please login first'
                ], 401);
            }

            // Delete all user's tokens (both access and refresh)
            $user->tokens()->delete();

            return (new JsonResponse([
                'success' => true,
                'response' => 'Logged out successfully'
            ], 200))->withCookie(
                    cookie()->forget('refresh_token')
                );

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to logout'
            ], 500);
        }
    }
}