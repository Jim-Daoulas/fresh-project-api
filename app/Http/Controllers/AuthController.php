<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        try {
            \Log::info('Register method called', $request->all());
            
            // Check if trying to register as admin
            if ($request->role && $request->role == 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found'
                ], 404);
            }

            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => Hash::make($fields['password']),
                'points' => 100 // Ξεκινά με 100 πόντους
            ]);

            // Assign default user role if roles exist
            if (class_exists(RoleCode::class)) {
                $userRole = Role::find(RoleCode::user);
                if ($userRole) {
                    $user->roles()->attach($userRole->id);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $response = [
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user' => $user->load('roles'),
                    'token' => $token
                ]
            ];

            return response()->json($response, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   public function login(Request $request)
{
    try {
        \Log::info('Login method called', $request->only(['email']));
        
        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Role checking (if specified)
        if ($request->role) {
            $roleId = null;
            
            // If role is numeric (from middleware)
            if (is_numeric($request->role)) {
                $roleId = (int)$request->role;
            }
            // If role is string (from manual request)
            else if ($request->role === 'admin') {
                $roleId = RoleCode::admin;
            } else if ($request->role === 'user') {
                $roleId = RoleCode::user;
            }
            
            if ($roleId) {
                $hasRole = $user->roles()->where('role_id', $roleId)->exists();
                if (!$hasRole) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized for this role'
                    ], 401);
                }
            }
        }

        //  DAILY LOGIN POINTS SYSTEM
        $dailyLoginReward = $user->checkDailyLogin();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'success' => true,
            'message' => 'User logged in successfully',
            'data' => [
                'user' => $user->fresh()->load('roles'),
                'token' => $token,
                'daily_login_reward' => $dailyLoginReward
            ]
        ];

        return response()->json($response);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Login error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Login failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }
}