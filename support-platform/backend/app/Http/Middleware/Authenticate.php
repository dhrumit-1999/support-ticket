<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends BaseAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // Try to authenticate using Sanctum token
        if ($request->bearerToken()) {
            $guard = $this->guard('sanctum');
            
            if (!$guard->check()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Invalid or expired token',
                ], 401);
            }
            
            // Check if user is active
            $user = $guard->user();
            if ($user && !$user->is_active) {
                return response()->json([
                    'error' => 'Account deactivated',
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }
            
            // Update last login info
            if ($user) {
                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);
            }
        }
        
        return parent::handle($request, $next, ...$guards);
    }
    
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            return route('login');
        }
        
        return null;
    }
}
