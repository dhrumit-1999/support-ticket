<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tenants table - represents client companies
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // Company name (e.g., "ABC Software")
            $table->string('slug')->unique();  // URL-friendly identifier
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'is_active']);
        });

        // Tenant branding/white-label configuration
        Schema::create('tenant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('company_name')->nullable();  // Override default name
            $table->string('logo_path')->nullable();  // Path to logo file
            $table->string('favicon_path')->nullable();  // Path to favicon
            $table->string('primary_color')->default('#3B82F6');  // Primary brand color
            $table->string('secondary_color')->default('#1E40AF');  // Secondary color
            $table->string('email_sender_name')->nullable();  // e.g., "ABC Support"
            $table->string('email_sender_address')->nullable();  // e.g., support@abc.com
            $table->string('support_email')->nullable();
            $table->string('portal_title')->nullable();  // Browser title
            $table->text('custom_css')->nullable();  // Additional CSS styling
            $table->text('welcome_message')->nullable();  // Portal welcome message
            $table->timestamps();

            $table->unique('tenant_id');
            $table->index('tenant_id');
        });

        // Tenant domains for custom domain support
        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique();  // e.g., "support.abc.com"
            $table->boolean('is_primary')->default(false);
            $table->boolean('ssl_enabled')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_primary']);
            $table->index('domain');
        });

        // Users table - all user types (admins, agents, customers)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->comment('super_admin, tenant_admin, tenant_agent, l2_agent, l3_agent, customer');
            $table->string('phone')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('job_title')->nullable();
            $table->text('signature')->nullable();  // Email signature for agents
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'role']);
            $table->index(['email', 'is_active']);
            $table->index('role');
        });

        // Departments/Teams within tenants
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');  // e.g., "Technical Support", "Billing"
            $table->text('description')->nullable();
            $table->string('email')->nullable();  // Department email
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        // Department-Agent assignment
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_lead')->default(false);  // Department lead
            $table->timestamps();

            $table->unique(['department_id', 'user_id']);
            $table->index('department_id');
            $table->index('user_id');
        });

        // Tickets table
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('ticket_number')->unique();  // Human-readable ticket ID
            $table->string('subject');
            $table->text('description');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');  // Ticket creator
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('open');  // open, in_progress, pending_customer, resolved, closed
            $table->string('priority')->default('medium');  // low, medium, high, urgent
            $table->string('escalation_level')->default('l1');  // l1, l2, l3
            $table->foreignId('escalated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('sla_breach')->default(0);  // 0 = no breach, 1 = breached
            $table->json('tags')->nullable();  // Array of tags
            $table->string('category')->nullable();  // Ticket category
            $table->string('source')->default('portal');  // portal, email, api
            $table->text('internal_notes')->nullable();  // Private notes for agents
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['tenant_id', 'escalation_level']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('ticket_number');
            
            // Full-text search index
            $table->fullText(['subject', 'description']);
        });

        // Ticket messages/replies
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Sender
            $table->text('message');
            $table->string('message_type')->default('reply');  // reply, internal_note, system
            $table->boolean('is_internal')->default(false);  // Visible only to agents
            $table->json('attachments')->nullable();  // Array of attachment IDs
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
            $table->index(['ticket_id', 'is_internal']);
            $table->index('user_id');
        });

        // Attachments table
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Uploader
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');  // Size in bytes
            $table->string('storage_path');  // Path in storage
            $table->string('storage_disk')->default('local');  // local, s3
            $table->string('hash')->unique();  // SHA256 hash for deduplication
            $table->boolean('is_scanned')->default(false);  // Virus scan status
            $table->boolean('scan_clean')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'ticket_id']);
            $table->index('ticket_id');
            $table->index('hash');
        });

        // Knowledge base articles
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_published']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'is_featured', 'is_published']);
            
            // Full-text search index
            $table->fullText(['title', 'content']);
        });

        // Audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');  // login, ticket_created, ticket_updated, etc.
            $table->string('resource_type');  // App\Models\Ticket, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('old_values')->nullable();  // Previous values
            $table->json('new_values')->nullable();  // New values
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
        });

        // Email templates for white-label emails
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');  // Template identifier
            $table->string('subject');  // Email subject with variables
            $table->longText('body_html');  // HTML body with variables
            $table->text('body_text')->nullable();  // Plain text version
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });

        // SLA policies
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('first_response_minutes')->default(60);  // SLA for first response
            $table->integer('resolution_minutes')->default(1440);  // SLA for resolution
            $table->string('priority')->default('medium');  // Priority this SLA applies to
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'priority']);
        });

        // Sessions table (database session driver)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Cache table (database cache driver)
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Jobs table (database queue driver)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Personal access tokens (Laravel Sanctum)
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('sla_policies');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('department_user');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenant_branding');
        Schema::dropIfExists('tenants');
    }
};
