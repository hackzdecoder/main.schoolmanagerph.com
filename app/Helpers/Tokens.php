<?php

namespace App\Helpers;

use Laravel\Sanctum\PersonalAccessToken;

class Tokens
{
    public static function refreshToken($refreshToken)
    {
        if (!$refreshToken) {
            return null;
        }

        $tokenModel = PersonalAccessToken::findToken($refreshToken);

        if (!$tokenModel) {
            return null;
        }

        $user = $tokenModel->tokenable;

        // Check if user exists AND is active AND token not expired
        if (!$user || $user->account_status !== 'active' || $tokenModel->expires_at < now()) {
            return null;
        }

        return $user->createToken('access_token', ['*'], now()->addHours(8))->plainTextToken;
    }

    public static function createAccessToken($user)
    {
        return $user->createToken('access_token', ['*'], now()->addHours(8))->plainTextToken; // CHANGED: 30 mins → 8 hours
    }

    public static function createRefreshToken($user)
    {
        return $user->createToken('refresh_token', ['*'], now()->addDays(14))->plainTextToken; // STAYS: 14 days
    }

    public static function deleteAllUserTokens($user)
    {
        $user->tokens()->delete();
    }
}