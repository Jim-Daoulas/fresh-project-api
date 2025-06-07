<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Device;
use App\Enum\RoleCode;

class AuthController extends Controller
{

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved',
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }

    public function register(Request $request)
    {
        if ($request->role && $request->role == 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Page not found'
            ], 404);
        }

        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password'])
        ]);

        // Assign role
        if ($request->role) {
            $roleId = null;
            
            if (is_numeric($request->role)) {
                $roleId = (int)$request->role;
            } else if ($request->role === 'admin') {
                $roleId = RoleCode::admin;
            } else if ($request->role === 'user') {
                $roleId = RoleCode::user;
            }
            
            if ($roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $user->roles()->attach($role->id);
                }
            }
        }

        // Initialize progression system for new user
        $user->initializeWithDefaults();

        $token = $user->createToken(Device::tokenName())->plainTextToken;

        $response = [
            'success' => true,
            'message' => 'User created and progression initialized',
            'data' => [
                'user' => $user,
                'token' => $token,
                'progression_initialized' => true
            ]
        ];

        return response()->json($response, 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Bad credentials'
            ], 401);
        }

        // Role checking logic (existing code)
        if ($request->role) {
            $roleId = null;
            
            if (is_numeric($request->role)) {
                $roleId = (int)$request->role;
            } else if ($request->role === 'admin') {
                $roleId = RoleCode::admin;
            } else if ($request->role === 'user') {
                $roleId = RoleCode::user;
            }
            
            if ($roleId) {
                $role = $user->roles()->where('role_id', $roleId)->first();
                if (!$role) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 401);
                }
            }
        }

        // Handle daily login bonus
        $dailyBonusResult = $user->claimDailyBonus();

        $token = $user->createToken(Device::tokenName())->plainTextToken;

        $response = [
            'success' => true,
            'message' => 'User logged in',
            'data' => [
                'user' => $user,
                'token' => $token,
                'daily_bonus' => $dailyBonusResult
            ]
        ];

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }
}