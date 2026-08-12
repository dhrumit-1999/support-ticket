<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * List all tenants (Super Admin only).
     */
    public function index(Request $request)
    {
        $query = Tenant::query();
        
        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }
        
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        $tenants = $query->with('branding')->paginate(20);
        
        return response()->json($tenants);
    }

    /**
     * Create a new tenant (Super Admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:tenants,slug'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $tenant = Tenant::create([
            'name' => $request->name,
            'slug' => strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $request->slug)),
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'address' => $request->address,
        ]);

        // Create default branding
        TenantBranding::create([
            'tenant_id' => $tenant->id,
            'company_name' => $request->name,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
        ]);

        // Log creation
        AuditLog::log(
            'tenant_created',
            Tenant::class,
            $tenant->id,
            $tenant->id,
            $request->user()?->id,
            null,
            $tenant->toArray(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant' => $tenant->load('branding'),
        ], 201);
    }

    /**
     * Get tenant details.
     */
    public function show(Tenant $tenant)
    {
        return response()->json([
            'tenant' => $tenant->load(['branding', 'domains']),
        ]);
    }

    /**
     * Update tenant (Super Admin only).
     */
    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('tenants')->ignore($tenant->id)],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $oldValues = $tenant->toArray();
        
        $tenant->update($request->only([
            'name', 'slug', 'contact_email', 'contact_phone', 'address', 'is_active'
        ]));

        // Log update
        AuditLog::log(
            'tenant_updated',
            Tenant::class,
            $tenant->id,
            $tenant->id,
            $request->user()?->id,
            $oldValues,
            $tenant->toArray(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Tenant updated successfully',
            'tenant' => $tenant->fresh(['branding', 'domains']),
        ]);
    }

    /**
     * Delete tenant (Super Admin only).
     */
    public function destroy(Tenant $tenant)
    {
        $tenantId = $tenant->id;
        $tenant->delete();

        // Log deletion
        AuditLog::log(
            'tenant_deleted',
            Tenant::class,
            $tenantId,
            $tenantId,
            request()->user()?->id,
            null,
            null,
            request()->ip(),
            request()->userAgent()
        );

        return response()->json([
            'message' => 'Tenant deleted successfully',
        ]);
    }

    /**
     * Get tenant branding configuration.
     */
    public function getBranding(Tenant $tenant)
    {
        return response()->json([
            'branding' => $tenant->branding,
        ]);
    }

    /**
     * Update tenant branding.
     */
    public function updateBranding(Request $request, Tenant $tenant)
    {
        $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:512'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'email_sender_name' => ['nullable', 'string', 'max:255'],
            'email_sender_address' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'portal_title' => ['nullable', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string'],
            'welcome_message' => ['nullable', 'string'],
        ]);

        $branding = $tenant->branding ?? new TenantBranding(['tenant_id' => $tenant->id]);

        $data = $request->except(['logo', 'favicon']);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if ($branding->favicon_path) {
                Storage::disk('public')->delete($branding->favicon_path);
            }
            $data['favicon_path'] = $request->file('favicon')->store('favicons', 'public');
        }

        $branding->fill($data);
        $branding->save();

        // Log update
        AuditLog::log(
            'tenant_branding_updated',
            TenantBranding::class,
            $branding->id,
            $tenant->id,
            $request->user()?->id,
            null,
            $data,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Branding updated successfully',
            'branding' => $branding,
        ]);
    }
}
