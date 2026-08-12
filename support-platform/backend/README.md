# Support Platform - Backend

## Overview
FastAPI-based backend for the multi-tenant white-label customer support platform.

## Features
- Multi-tenant architecture with tenant isolation
- JWT-based authentication
- Role-based access control (Super Admin, Tenant Admin, Tenant Agent, L2/L3 Agents, Customer)
- Ticket management with escalation workflow
- White-label branding support
- Audit logging
- S3-compatible file storage

## Quick Start

### Using Docker (Recommended)

1. Start all services:
```bash
cd docker
docker-compose -f docker-compose.dev.yml up -d
```

2. Access the API:
- API: http://localhost:8000
- API Docs: http://localhost:8000/docs
- PostgreSQL: localhost:5432
- Redis: localhost:6379
- MinIO Console: http://localhost:9001 (minioadmin/minioadmin)

### Without Docker

1. Install dependencies:
```bash
pip install -r requirements.txt
```

2. Set up environment variables:
```bash
cp .env.example .env
# Edit .env with your configuration
```

3. Start PostgreSQL and Redis (system installation or separate containers)

4. Run database migrations:
```bash
alembic upgrade head
```

5. Start the server:
```bash
python -m uvicorn app.main:app --reload
```

## API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/refresh` - Refresh token
- `GET /api/v1/auth/me` - Get current user

### Tenants
- `POST /api/v1/tenants/` - Create tenant (Super Admin)
- `GET /api/v1/tenants/` - List tenants (Super Admin)
- `GET /api/v1/tenants/me` - Get current tenant
- `GET /api/v1/tenants/{id}` - Get tenant by ID
- `PUT /api/v1/tenants/{id}` - Update tenant (Super Admin)
- `GET /api/v1/tenants/{id}/branding` - Get tenant branding
- `PUT /api/v1/tenants/{id}/branding` - Update tenant branding
- `GET /api/v1/tenants/by-domain/{domain}` - Get tenant by domain (public)

### Tickets
- `POST /api/v1/tickets/` - Create ticket
- `GET /api/v1/tickets/` - List tickets (with filtering)
- `GET /api/v1/tickets/{id}` - Get ticket
- `PUT /api/v1/tickets/{id}` - Update ticket
- `POST /api/v1/tickets/{id}/messages` - Add message/reply
- `POST /api/v1/tickets/{id}/escalate` - Escalate ticket
- `DELETE /api/v1/tickets/{id}` - Delete ticket

## User Roles

| Role | Description | Access |
|------|-------------|--------|
| SUPER_ADMIN | Platform owner | Full access to all tenants |
| TENANT_ADMIN | Client company admin | Full access to own tenant |
| TENANT_AGENT | Client support agent | Access to own tenant tickets |
| L2_AGENT | Internal support level 2 | Cross-tenant access |
| L3_AGENT | Engineering team | Cross-tenant access |
| CUSTOMER | End customer | Own tickets only |

## Database Schema

Key tables:
- `tenants` - Client companies
- `tenant_branding` - White-label configuration
- `users` - All user types (agents, admins, customers)
- `customers` - End customers
- `tickets` - Support tickets
- `ticket_messages` - Ticket conversations
- `attachments` - File attachments
- `knowledge_articles` - Knowledge base
- `audit_logs` - Activity logs

## Security Features

- Tenant isolation at database query level
- JWT token authentication
- Role-based authorization
- Password hashing with bcrypt
- CORS protection
- SQL injection prevention (SQLAlchemy ORM)
- Audit logging for all critical actions

## Next Steps

1. Run database migrations
2. Create a super admin user
3. Create first tenant
4. Configure tenant branding
5. Create tenant admin and agents
6. Start creating tickets

See the main README.md for complete architecture documentation.
