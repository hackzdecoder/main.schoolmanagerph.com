<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Helpers\Tokens;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\JsonResponse;

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

        // Delete ALL existing tokens for this user (forces single session)
        $user->tokens()->delete();

        // Create NEW tokens (8h access + 14d refresh)
        $accessToken = Tokens::createAccessToken($user);
        $refreshToken = Tokens::createRefreshToken($user);

        $accessExpiresAt = now()->addHours(8);

        // Return response with new cookie (overwrites old one)
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
            'access_expires_at' => $accessExpiresAt->toDateTimeString(),
        ], 200))->withCookie(
                cookie(
                    'refresh_token',
                    $refreshToken, // New refresh token
                    20160, // 14 days
                    '/',
                    null,
                    app()->environment('production'),
                    true, // httpOnly
                    false,
                    'strict'
                )
            );
    }

    /**
     * Mailer
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test_mailer(Request $request)
    {
        try {
            // Validate email if provided
            $request->validate([
                'email' => 'required|email'
            ]);

            // Get recipient email
            $toEmail = $request->input('email');

            // Send the test email
            Mail::to($toEmail)->send(new TestMail());

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!',
                'data' => [
                    'recipient' => $toEmail,
                    'sent_at' => now()->toDateTimeString(),
                    'mailer' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'environment' => app()->environment()
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Refresh access token using refresh token from cookie
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Refresh token from cookie
            $refreshToken = $request->cookie('refresh_token');

            if (!$refreshToken) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No refresh token provided'
                ], 401);
            }

            // New access token
            $newAccessToken = Tokens::refreshToken($refreshToken);

            if (!$newAccessToken) {
                return (new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid or expired refresh token. Please login again.'
                ], 401))->withCookie(cookie()->forget('refresh_token'));
            }

            // Return new access token
            return new JsonResponse([
                'success' => true,
                'access_token' => $newAccessToken,
                'access_expires_at' => now()->addHours(8)->toDateTimeString()
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to refresh token: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
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

            // Delete tokens
            Tokens::deleteAllUserTokens($user);

            return (new JsonResponse([
                'success' => true,
                'response' => 'Logged out successfully'
            ], 200))->withCookie(cookie()->forget('refresh_token'));

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to logout'
            ], 500);
        }
    }
}