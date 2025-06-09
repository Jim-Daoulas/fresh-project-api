public function login(Request $request)
{
    $fields = $request->validate([
        'email' => 'required|string',
        'password' => 'required|string|min:6'
    ]);

    $user = User::where('email', $fields['email'])->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Bad creds'
        ], 401);
    }

    if (!Hash::check($fields['password'], $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Bad creds'
        ], 401);
    }

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

    // ✅ CHECK DAILY LOGIN BONUS
    $dailyBonus = $user->checkDailyLogin();

    $token = $user->createToken(Device::tokenName())->plainTextToken;

    $response = [
        'success' => true,
        'message' => 'User logged in',
        'data' => [
            'user' => $user->fresh(), // Fresh data με updated points
            'token' => $token,
            'daily_bonus' => $dailyBonus // Για να ξέρει το frontend
        ]
    ];

    return response()->json($response);
}