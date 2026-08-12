<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsolation
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that users can only access resources belonging to their tenant.
     * Super admins and cross-tenant roles (L2/L3 agents) are exempt.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Super admins can access everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Cross-tenant roles (L2/L3 agents) can access all tenants
        if ($user->canAccessMultipleTenants()) {
            return $next($request);
        }
        
        // For tenant-scoped users, verify they belong to the requested tenant
        $requestedTenantId = $request->route('tenant_id') 
            ?? $request->route('tenant') 
            ?? $request->input('tenant_id');
        
        if ($requestedTenantId && $requestedTenantId != $user->tenant_id) {
            // Log unauthorized access attempt
            \App\Models\AuditLog::log(
                'unauthorized_tenant_access',
                'Request',
                null,
                $requestedTenantId,
                $user->id,
                null,
                null,
                $request->ip(),
                $request->userAgent()
            );
            
            return response()->json([
                'error' => 'Unauthorized access to tenant resources',
            ], 403);
        }
        
        // Set tenant context from user if not already set
        if (!$request->attributes->has('tenant_id')) {
            $request->attributes->set('tenant_id', $user->tenant_id);
        }
        
        return $next($request);
    }
}
