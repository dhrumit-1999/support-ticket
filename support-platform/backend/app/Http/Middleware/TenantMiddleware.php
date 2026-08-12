<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\TenantDomain;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Find tenant by domain
        $tenantDomain = TenantDomain::where('domain', $host)->first();
        
        if ($tenantDomain) {
            $tenant = $tenantDomain->tenant;
            
            if (!$tenant || !$tenant->is_active) {
                return response()->json([
                    'error' => 'Tenant not found or inactive',
                ], 404);
            }
            
            // Set tenant in request attributes
            $request->attributes->set('tenant', $tenant);
            $request->attributes->set('tenant_id', $tenant->id);
        }
        
        return $next($request);
    }
}
