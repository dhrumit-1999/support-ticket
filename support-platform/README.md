# Multi-Tenant White-Label Customer Support Platform

## Architecture Overview

A production-ready, self-hosted, multi-tenant customer support/ticketing platform built with **PHP 8.2+** and **MySQL 8.0+**.

### Technology Stack

- **Backend**: PHP 8.2+ (Laravel 11.x framework)
- **Database**: MySQL 8.0+ (with InnoDB engine)
- **Cache/Queue**: Database-driven queues (Redis optional, not required)
- **Storage**: Local filesystem or S3-compatible object storage
- **Web Server**: Nginx/Apache with PHP-FPM
- **Frontend**: Blade templates + Alpine.js or React/Vue.js (optional SPA)

### Key Features

✅ **True Multi-Tenancy**: Shared database with tenant_id isolation enforced at query level  
✅ **White-Label Portals**: Per-tenant branding, custom domains, logos, colors  
✅ **Role-Based Access**: Super Admin, Tenant Admin, Tenant Agent, L2/L3 Agent, Customer  
✅ **Ticket Escalation**: L1 (Client) → L2 (Internal) → L3 (Engineering) workflow  
✅ **Custom Domains**: support.clientA.com, support.clientB.com  
✅ **Email Integration**: Per-tenant email configuration, branded templates  
✅ **Knowledge Base**: Tenant-isolated articles  
✅ **Audit Logging**: Complete action tracking  
✅ **Security**: IDOR prevention, SQL injection protection, XSS/CSRF defense  

## Quick Start

### Development Environment (Windows 10)

#### Option 1: Docker (Recommended)
```bash
cd docker
docker-compose -f docker-compose.dev.yml up -d
```

Access:
- Application: http://localhost:8000
- phpMyAdmin: http://localhost:8080

#### Option 2: Native PHP Installation
1. Install XAMPP/WAMP or PHP 8.2+ with MySQL
2. Clone this repository
3. Configure `.env` file
4. Run migrations: `php artisan migrate`
5. Start server: `php artisan serve`

## Project Structure

```
support-platform/
├── backend/
│   ├── app/
│   │   ├── Core/           # Configuration, constants, base classes
│   │   ├── Models/         # Eloquent ORM models
│   │   ├── Http/
│   │   │   ├── Controllers/ # API and web controllers
│   │   │   ├── Middleware/  # Tenant resolution, auth, authorization
│   │   │   └── Requests/    # Form validation
│   │   ├── Services/       # Business logic (Auth, Ticket, Email)
│   │   └── Utils/          # Helper functions
│   ├── database/
│   │   ├── migrations/     # Database schema
│   │   └── seeders/        # Initial data
│   ├── routes/
│   │   ├── api.php         # API routes
│   │   └── web.php         # Web routes
│   └── config/             # Configuration files
├── public/                 # Web root (index.php, assets)
├── resources/
│   ├── views/              # Blade templates
│   └── emails/             # Email templates
├── storage/                # File uploads, logs
├── docker/
│   └── docker-compose.dev.yml
├── .env.example
└── README.md
```

## Database Schema

### Core Tables

- `tenants` - Client companies (ABC Software, XYZ Software)
- `users` - All users (admins, agents, customers) with role and tenant_id
- `tickets` - Support tickets with escalation levels
- `ticket_messages` - Conversations (public replies, internal notes)
- `attachments` - File uploads with tenant isolation
- `knowledge_articles` - Tenant-specific help articles
- `departments` - Support teams within tenants
- `audit_logs` - Action tracking
- `tenant_domains` - Custom domain mappings
- `email_templates` - Branded email configurations

### Tenant Isolation Strategy

**Shared Database, Shared Schema** with `tenant_id` column on every tenant-specific table.

**Why this approach:**
- Cost-effective for hundreds/thousands of tenants
- Simplified backups and maintenance
- Cross-tenant reporting for L2/L3 support
- Easy to implement row-level security in application

**Security Layers:**
1. Global scope automatically adds `WHERE tenant_id = ?` to all queries
2. Middleware validates tenant from authenticated user/domain
3. Authorization checks verify user belongs to requested tenant
4. Never trust client-supplied tenant_id
5. Database indexes optimize tenant-scoped queries

## Authentication & Authorization

### Roles

| Role | Scope | Permissions |
|------|-------|-------------|
| SUPER_ADMIN | Platform-wide | Manage all tenants, view all data |
| TENANT_ADMIN | Single tenant | Manage tenant settings, agents, customers |
| TENANT_AGENT | Single tenant | Handle tenant's tickets only |
| L2_AGENT | Cross-tenant | Handle escalations from any tenant |
| L3_AGENT | Cross-tenant | Engineering-level access |
| CUSTOMER | Single tenant | View/create own tickets only |

### JWT Authentication

- Access token (15 min expiry)
- Refresh token (7 day expiry)
- Token contains: user_id, tenant_id (or null for cross-tenant roles), role
- All API requests require Bearer token

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user (Super Admin only for tenants)
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/logout` - Logout

### Tenants (Super Admin)
- `GET /api/tenants` - List all tenants
- `POST /api/tenants` - Create tenant
- `GET /api/tenants/{id}` - Get tenant details
- `PUT /api/tenants/{id}` - Update tenant
- `DELETE /api/tenants/{id}` - Delete tenant
- `GET /api/tenants/{id}/branding` - Get white-label config
- `PUT /api/tenants/{id}/branding` - Update branding

### Tickets
- `GET /api/tickets` - List tickets (tenant-scoped)
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PUT /api/tickets/{id}` - Update ticket
- `POST /api/tickets/{id}/messages` - Add reply/note
- `POST /api/tickets/{id}/escalate` - Escalate to L2/L3
- `POST /api/tickets/{id}/assign` - Assign to agent

### Knowledge Base
- `GET /api/knowledge` - List articles (tenant-scoped)
- `POST /api/knowledge` - Create article
- `GET /api/knowledge/{id}` - Get article
- `PUT /api/knowledge/{id}` - Update article
- `DELETE /api/knowledge/{id}` - Delete article

### Public (White-Label Portal)
- `GET /api/portal/branding` - Get branding by domain
- `GET /api/portal/knowledge` - Public KB articles

## Deployment Architecture

### Production Requirements

- **Server**: Linux (Ubuntu 22.04 LTS recommended)
- **PHP**: 8.2+ with extensions: mysqli, pdo_mysql, mbstring, xml, curl, zip
- **Database**: MySQL 8.0+ with InnoDB
- **Web Server**: Nginx + PHP-FPM or Apache
- **SSL**: Let's Encrypt certificates
- **Storage**: Local disk or S3-compatible (MinIO, AWS S3)
- **Backups**: Daily database dumps, offsite storage

### Scaling Strategy

- **Initial**: Single server (10 tenants, 100 agents, 10K customers)
- **Growth**: Separate database server, add application servers behind load balancer
- **Large Scale**: Database read replicas, horizontal sharding by tenant_id if needed

## Security Considerations

- **SQL Injection**: Prepared statements via Eloquent ORM
- **XSS**: Output escaping in Blade templates
- **CSRF**: Token validation on state-changing requests
- **IDOR**: Tenant_id validation on every resource access
- **File Uploads**: MIME type validation, stored outside web root
- **Session Security**: HTTP-only cookies, secure flags
- **Rate Limiting**: Per-IP and per-user limits

## License

Proprietary - All rights reserved

## Support

For implementation questions, contact your development team.
