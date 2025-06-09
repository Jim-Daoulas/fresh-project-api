<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Load user with roles
            $user->load('roles');
            
            return response()->json([
                'success' => true,
                'message' => 'User retrieved',
                'data' => [
                    'user' => $user
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('UserController@me error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function tokens(Request $request)
    {
        try {
            $tokens = [];
            foreach($request->user()->tokens as $token) {
                $tokens[] = [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at
                ];
            }

            return response()->json([
                'success' => true,
                'tokens' => $tokens
            ]);
        } catch (\Exception $e) {
            \Log::error('UserController@tokens error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tokens'
            ], 500);
        }
    }

    public function revokeAllTokens(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tokens revoked, you will be logged out from all devices'
            ]);
        } catch (\Exception $e) {
            \Log::error('UserController@revokeAllTokens error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke tokens'
            ], 500);
        }
    }
}