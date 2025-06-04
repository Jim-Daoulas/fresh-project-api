<?php

namespace App\Http\Controllers;

use App\Helpers\Device;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request)
{
    // Log all tokens for debugging
    \Log::debug('Token from header:', ['token' => $request->bearerToken()]);
    
    // Check which tokens exist in the database
    $tokens = \Laravel\Sanctum\PersonalAccessToken::all();
    \Log::debug('All tokens in db:', ['tokens' => $tokens->toArray()]);
    
    // Try to get the user
    $user = $request->user();
    \Log::debug('User after authentication:', ['user' => $user]);
    
    return response()->json([
        'success' => true,
        'message' => 'User retrieved',
        'data' => [
            'user' => $user
        ]
    ]);
}

    public function tokens(Request $request)
    {
        $tokens = [];
        foreach($request->user()->tokens as $token) {
            $tokens[] = [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at
            ];
        }

        return response()->json([
            'tokens' => $tokens
        ]);
    }

    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Tokens revoked, you will be logged out from all devices'
        ]);
    }
}
