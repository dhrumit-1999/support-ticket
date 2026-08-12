<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:customer,tenant_agent,tenant_admin'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'tenant_id' => $request->tenant_id,
        ]);

        // Log registration
        AuditLog::log(
            'user_registered',
            User::class,
            $user->id,
            $request->tenant_id,
            $user->id,
            null,
            ['role' => $request->role],
            $request->ip(),
            $request->userAgent()
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'error' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return response()->json([
                'error' => 'Account deactivated',
            ], 403);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Log login
        AuditLog::log(
            'user_login',
            User::class,
            $user->id,
            $user->tenant_id,
            $user->id,
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            // Log logout
            AuditLog::log(
                'user_logout',
                User::class,
                $user->id,
                $user->tenant_id,
                $user->id,
                null,
                null,
                $request->ip(),
                $request->userAgent()
            );
            
            // Revoke current token
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Log password change
        AuditLog::log(
            'password_changed',
            User::class,
            $user->id,
            $user->tenant_id,
            $user->id,
            null,
            null,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}
