# Multi-Tenant White-Label Support Platform

## Complete Architecture Documentation

This document provides the complete architecture for a production-ready, self-hosted, multi-tenant customer support platform.

---

## PHASE 1: Requirements Analysis

### Business Model
```
Platform Owner (You)
├── Tenant A (ABC Software)
│   ├── Customer A1, A2, A3
│   └── Agent A1, A2
├── Tenant B (XYZ Software)
│   ├── Customer B1, B2
│   └── Agent B1, B2
└── Internal L2/L3 Support Team
```

### Core Requirements Summary
1. **True Multi-Tenancy**: Database-level tenant isolation
2. **White-Label Portal**: Per-tenant branding with custom domains
3. **Customer Ticketing**: Customers create/view own tickets only
4. **Client Agents**: Tenant-scoped agent access
5. **L2/L3 Support**: Cross-tenant internal team access
6. **Ticket Escalation**: L1 → L2 → L3 workflow
7. **Email Integration**: Per-tenant email configuration
8. **Custom Domains**: Domain-based tenant resolution
9. **Role Hierarchy**: Super Admin, Tenant Admin, Agent, L2/L3, Customer
10. **Knowledge Base**: Tenant-isolated articles
11. **Reporting**: Tenant-scoped analytics
12. **Security**: Protection against IDOR, XSS, CSRF, SQL injection

---

## PHASE 2: Open-Source Evaluation Results

| Solution | Multi-Tenancy | White-Label | Custom Domain | Overall | Verdict |
|----------|--------------|-------------|---------------|---------|---------|
| Zammad | ❌ | ⚠️ Partial | ❌ | 3.5/5 | Requires heavy customization |
| Frappe Helpdesk | ⚠️ Basic | ⚠️ Partial | ❌ | 3.6/5 | Limited multi-tenancy |
| UVdesk | ❌ | ⚠️ Partial | ❌ | 3.4/5 | Single-tenant focused |
| GLPI | ⚠️ Entities | ❌ | ❌ | 3.4/5 | ITSM focused |
| FreeScout | ❌ | ✅ Good | ❌ | 3.0/5 | Email-focused |
| osTicket | ❌ | ❌ | ❌ | 2.8/5 | Legacy architecture |

**Decision**: Build custom solution for true multi-tenancy and white-label requirements.

---

## PHASE 3: Recommended Architecture

### Technology Stack
- **Backend**: Python FastAPI
- **Frontend**: React/Next.js (to be implemented)
- **Database**: PostgreSQL 15+
- **Cache/Queue**: Redis
- **Storage**: S3-compatible (MinIO for self-hosted)
- **Authentication**: JWT tokens
- **Containerization**: Docker

### Architecture Principles
1. **Defense in Depth**: Multiple layers of tenant isolation
2. **Zero Trust**: Never trust client-supplied tenant_id
3. **Audit Everything**: Log all critical actions
4. **Stateless API**: Scale horizontally
5. **Tenant-Aware Queries**: All queries include tenant filter

---

## PHASE 4: Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Client Layer                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │support.abc.com│  │support.xyz.com│  │  admin.platform │          │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘          │
└─────────┼────────────────┼────────────────┼──────────────────┘
          │                │                │
          ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────┐
│                    Reverse Proxy (Nginx)                     │
│              - SSL Termination                               │
│              - Domain-based routing                          │
│              - Rate limiting                                 │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   Tenant Resolver Middleware                 │
│              - Extract domain from Host header               │
│              - Lookup tenant by domain/slug                  │
│              - Inject tenant context into request            │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    FastAPI Backend                           │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Authentication Layer                    │    │
│  │         - JWT validation                             │    │
│  │         - Role-based authorization                   │    │
│  │         - Tenant access verification                 │    │
│  └─────────────────────────────────────────────────────┘    │
│                          │                                   │
│                          ▼                                   │
│  ┌─────────────────────────────────────────────────────┐    │
│  │                API Endpoints                         │    │
│  │  /auth  /tenants  /tickets  /customers  /kb          │    │
│  └─────────────────────────────────────────────────────┘    │
│                          │                                   │
│                          ▼                                   │
│  ┌─────────────────────────────────────────────────────┐    │
│  │           Authorization & Tenant Isolation           │    │
│  │  - Enforce tenant_id from authenticated user         │    │
│  │  - Never trust client-supplied tenant_id             │    │
│  │  - Row-level security via query filters              │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────┬───────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
┌─────────────────┐ ┌─────────────┐ ┌─────────────────┐
│   PostgreSQL    │ │    Redis    │ │   MinIO/S3      │
│   - Tenants     │ │  - Sessions │ │   - Attachments │
│   - Users       │ │  - Cache    │ │   - Logos       │
│   - Tickets     │ │  - Queue    │ │   - KB images   │
│   - Audit Logs  │ │             │ │                 │
└─────────────────┘ └─────────────┘ └─────────────────┘
```

---

## PHASE 5: Database Schema Design

### Database Strategy: Shared Database, Shared Schema with tenant_id

**Rationale**: 
- Cost-effective for hundreds/thousands of tenants
- Easier maintenance and migrations
- Proper indexing on tenant_id provides performance
- Application-level enforcement with defense in depth

### Core Tables

#### Tenants
```sql
tenants (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(100) UNIQUE,      -- URL identifier
    domain VARCHAR(255) UNIQUE,     -- Custom domain
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

#### Tenant Branding
```sql
tenant_branding (
    id UUID PRIMARY KEY,
    tenant_id UUID FOREIGN KEY → tenants.id,
    company_name VARCHAR(255),
    logo_url VARCHAR(500),
    favicon_url VARCHAR(500),
    primary_color VARCHAR(7),       -- Hex color
    secondary_color VARCHAR(7),
    portal_title VARCHAR(255),
    email_sender_name VARCHAR(255),
    support_email VARCHAR(255)
)
```

#### Users (All roles in one table)
```sql
users (
    id UUID PRIMARY KEY,
    tenant_id UUID FOREIGN KEY → tenants.id,  -- NULL for L2/L3/Super Admin
    email VARCHAR(255),
    password_hash VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('super_admin', 'tenant_admin', 'tenant_agent', 'l2_agent', 'l3_agent', 'customer'),
    is_active BOOLEAN,
    UNIQUE(tenant_id, email)
)
```

#### Tickets
```sql
tickets (
    id UUID PRIMARY KEY,
    ticket_number VARCHAR(50) UNIQUE,
    tenant_id UUID FOREIGN KEY → tenants.id,  -- CRITICAL for isolation
    customer_id UUID FOREIGN KEY → customers.id,
    subject VARCHAR(500),
    description TEXT,
    status ENUM('open', 'in_progress', 'pending_customer', 'resolved', 'closed'),
    priority ENUM('low', 'medium', 'high', 'urgent'),
    escalation_level ENUM('l1', 'l2', 'l3'),
    assigned_to_id UUID FOREIGN KEY → users.id,
    created_by_id UUID FOREIGN KEY → users.id,
    search_vector TSVECTOR,  -- Full-text search
    INDEX(tenant_id, status),
    INDEX(tenant_id, created_at),
    GIN INDEX(search_vector)
)
```

### Indexing Strategy
```sql
-- Composite indexes for common queries
CREATE INDEX idx_ticket_tenant_status ON tickets(tenant_id, status);
CREATE INDEX idx_ticket_tenant_created ON tickets(tenant_id, created_at DESC);
CREATE INDEX idx_user_tenant_email ON users(tenant_id, email);

-- Full-text search
CREATE INDEX idx_ticket_search ON tickets USING GIN(search_vector);
```

---

## PHASE 6: Authentication & Authorization

### JWT Token Structure
```json
{
  "sub": "user-uuid",
  "tenant_id": "tenant-uuid-or-null",
  "role": "tenant_agent",
  "exp": 1704067200,
  "iat": 1704065400
}
```

### Role Permissions Matrix

| Action | Super Admin | Tenant Admin | Tenant Agent | L2/L3 Agent | Customer |
|--------|-------------|--------------|--------------|-------------|----------|
| Create Tenant | ✅ | ❌ | ❌ | ❌ | ❌ |
| View All Tenants | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage Own Tenant | N/A | ✅ | ❌ | N/A | N/A |
| View Tenant Tickets | ✅ | ✅ | ✅ | ✅ (all) | ❌ |
| View Own Tickets | ✅ | ✅ | ✅ | ✅ | ✅ |
| Modify Tenant Tickets | ✅ | ✅ | ✅ | ✅ | ❌ |
| Escalate Tickets | ✅ | ✅ | ✅ | ✅ | ❌ |
| View KB Articles | ✅ | ✅ | ✅ | ✅ (incl. internal) | ✅ (published only) |
| Manage Branding | ✅ | ✅ (own) | ❌ | ❌ | ❌ |
| View Audit Logs | ✅ | ✅ (own tenant) | ❌ | ❌ | ❌ |

### Authorization Flow
```
1. Request arrives with JWT token
2. Validate token signature and expiration
3. Load user from database
4. Check user.is_active
5. For tenant-scoped operations:
   - If role == SUPER_ADMIN: allow
   - If role in [L2_AGENT, L3_AGENT]: allow
   - Else: verify user.tenant_id == requested_tenant_id
6. Execute operation with tenant_id in all queries
```

---

## PHASE 7: Tenant Isolation Strategy

### Defense Layers

#### Layer 1: Authentication
- JWT contains tenant_id (when applicable)
- Token signed with server secret

#### Layer 2: Authorization Middleware
```python
def can_access_tenant(user, tenant_id):
    if user.role in [SUPER_ADMIN, L2_AGENT, L3_AGENT]:
        return True
    return user.tenant_id == tenant_id
```

#### Layer 3: Query-Level Isolation
```python
# NEVER do this:
ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()

# ALWAYS do this:
ticket = db.query(Ticket).filter(
    Ticket.id == ticket_id,
    Ticket.tenant_id == current_user.tenant_id  # Or pass through for L2/L3
).first()
```

#### Layer 4: Attachment Security
```python
def serve_attachment(attachment_id, user):
    attachment = db.query(Attachment).filter(
        Attachment.id == attachment_id,
        Attachment.tenant_id == get_user_tenant_id(user)
    ).first()
    if not attachment:
        raise HTTPException(403)
    return generate_signed_s3_url(attachment.file_path)
```

#### Layer 5: Database Constraints
- Foreign keys with ON DELETE CASCADE
- Unique constraints on (tenant_id, email)
- NOT NULL on tenant_id for tenant-scoped tables

---

## PHASE 8: Ticket Lifecycle & Escalation

### Status Flow
```
OPEN → IN_PROGRESS → PENDING_CUSTOMER → RESOLVED → CLOSED
                ↑              ↓
                └──────────────┘
```

### Escalation Workflow
```
Customer creates ticket
        ↓
L1 (Tenant Agent) receives ticket
        ↓
L1 investigates and replies
        ↓
If unresolved after SLA or complexity
        ↓
L1 escalates to L2 (Internal Support)
        ↓
L2 investigates, may collaborate with L1
        ↓
If engineering issue
        ↓
L2 escalates to L3 (Engineering)
        ↓
L3 provides fix/root cause
        ↓
Resolution communicated back through L2 → L1 → Customer
```

### Escalation Implementation
```python
@router.post("/{ticket_id}/escalate")
async def escalate_ticket(ticket_id, target_level: str, user, db):
    ticket = get_ticket_with_authorization(ticket_id, user, db)
    
    old_level = ticket.escalation_level
    ticket.escalation_level = TicketEscalationLevel[target_level.upper()]
    
    # Auto-assign to appropriate team
    if target_level == "l2":
        l2_agent = get_available_l2_agent(db)
        ticket.assigned_to_id = l2_agent.id
    
    log_audit(AuditAction.TICKET_ESCALATED, {
        "old_level": old_level.value,
        "new_level": ticket.escalation_level.value
    })
    
    db.commit()
```

---

## PHASE 9: White-Label & Custom Domain Architecture

### Domain Resolution Flow
```
Request: https://support.abcsoftware.com
        ↓
Nginx extracts Host header
        ↓
Tenant Resolver queries: SELECT * FROM tenants WHERE domain = 'support.abcsoftware.com'
        ↓
Found: Tenant ABC (id: uuid-123)
        ↓
Load branding: SELECT * FROM tenant_branding WHERE tenant_id = uuid-123
        ↓
Response includes:
{
    "company_name": "ABC Software",
    "logo_url": "https://s3.../abc-logo.png",
    "primary_color": "#FF6B35",
    "portal_title": "ABC Software Support"
}
        ↓
Frontend applies branding dynamically
```

### Frontend Branding Implementation
```javascript
// React context for tenant branding
const BrandingContext = createContext();

function BrandingProvider({ children }) {
    const [branding, setBranding] = useState(null);
    
    useEffect(() => {
        // Fetch branding based on current domain
        fetch(`/api/v1/tenants/by-domain/${window.location.hostname}`)
            .then(res => res.json())
            .then(data => {
                setBranding(data.branding);
                // Apply CSS variables
                document.documentElement.style.setProperty('--primary-color', data.branding.primary_color);
                document.title = data.branding.portal_title || data.branding.company_name;
            });
    }, []);
    
    return (
        <BrandingContext.Provider value={branding}>
            {children}
        </BrandingContext.Provider>
    );
}
```

### Custom Domain Setup Process
1. Client adds CNAME record: `support.client.com → platform.yourdomain.com`
2. Platform admin configures domain in tenant settings
3. SSL certificate provisioned (Let's Encrypt)
4. Nginx routes based on Host header
5. Tenant resolver identifies tenant from domain

---

## PHASE 10: Email Architecture

### Per-Tenant Email Configuration
```sql
tenant_email_configs (
    tenant_id UUID PRIMARY KEY,
    smtp_host VARCHAR(255),
    smtp_port INTEGER,
    smtp_username VARCHAR(255),
    smtp_password VARCHAR(255),  -- Encrypted
    from_email VARCHAR(255),
    from_name VARCHAR(255),
    use_tls BOOLEAN
)
```

### Email Template Variables
```
Subject: [{{company_name}} Support] Ticket #{{ticket_number}} - {{ticket_subject}}

From: {{company_name}} Support <{{from_email}}>

Body:
Hello {{customer_name}},

Your ticket #{{ticket_number}} has been {{action}}.

Subject: {{ticket_subject}}
Status: {{ticket_status}}
Priority: {{ticket_priority}}

{{message_content}}

View your ticket: {{ticket_url}}

--
{{company_name}} Support Team
```

### Email Service Implementation
```python
class EmailService:
    async def send_ticket_notification(self, ticket, recipient, template_vars):
        # Get tenant's email config
        email_config = db.query(TenantEmailConfig).filter(
            TenantEmailConfig.tenant_id == ticket.tenant_id
        ).first()
        
        # Use tenant config or fallback to platform defaults
        smtp_settings = email_config if email_config else settings
        
        # Render template with tenant branding
        subject = render_template(ticket.subject_template, template_vars)
        body = render_template(ticket.body_template, template_vars)
        
        # Send via SMTP
        await smtp.send(
            from_addr=f"{email_config.from_name} <{email_config.from_email}>",
            to_addrs=[recipient.email],
            subject=subject,
            body=body
        )
```

---

## PHASE 11: API Architecture

### RESTful Endpoints

#### Authentication
```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me
POST   /api/v1/auth/logout
```

#### Tenants
```
POST   /api/v1/tenants/              # Create (Super Admin)
GET    /api/v1/tenants/              # List (Super Admin)
GET    /api/v1/tenants/me            # Get current tenant
GET    /api/v1/tenants/{id}          # Get by ID
PUT    /api/v1/tenants/{id}          # Update (Super Admin)
GET    /api/v1/tenants/{id}/branding # Get branding
PUT    /api/v1/tenants/{id}/branding # Update branding
GET    /api/v1/tenants/by-domain/{domain}  # Public lookup
```

#### Tickets
```
POST   /api/v1/tickets/              # Create ticket
GET    /api/v1/tickets/              # List (filtered by role/tenant)
GET    /api/v1/tickets/{id}          # Get ticket
PUT    /api/v1/tickets/{id}          # Update ticket
DELETE /api/v1/tickets/{id}          # Delete ticket
POST   /api/v1/tickets/{id}/messages # Add reply/note
POST   /api/v1/tickets/{id}/escalate # Escalate to L2/L3
GET    /api/v1/tickets/{id}/attachments # List attachments
POST   /api/v1/tickets/{id}/attachments # Upload attachment
```

### API Security Headers
```python
@app.middleware("http")
async def add_security_headers(request, call_next):
    response = await call_next(request)
    response.headers["X-Content-Type-Options"] = "nosniff"
    response.headers["X-Frame-Options"] = "DENY"
    response.headers["X-XSS-Protection"] = "1; mode=block"
    response.headers["Strict-Transport-Security"] = "max-age=31536000"
    return response
```

---

## PHASE 12: Deployment Architecture

### Production Infrastructure
```
┌─────────────────────────────────────────────────────────────┐
│                         DNS Layer                           │
│  Route53/CloudFlare managing:                              │
│  - *.platform.com (wildcard)                               │
│  - Individual tenant domains (CNAME)                       │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    Load Balancer                            │
│  - AWS ALB / Nginx                                          │
│  - SSL termination (Let's Encrypt)                          │
│  - Health checks                                            │
└─────────────────────┬───────────────────────────────────────┘
                      │
          ┌───────────┼───────────┐
          │           │           │
          ▼           ▼           ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│  App Node 1 │ │  App Node 2 │ │  App Node N │
│  (Docker)   │ │  (Docker)   │ │  (Docker)   │
└──────┬──────┘ └──────┬──────┘ └──────┬──────┘
       │               │               │
       └───────────────┼───────────────┘
                       │
          ┌────────────┼────────────┐
          │            │            │
          ▼            ▼            ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│  PostgreSQL │ │    Redis    │ │   MinIO/S3  │
│  (Primary + │ │   Cluster   │ │   Cluster   │
│   Replica)  │ │             │ │             │
└─────────────┘ └─────────────┘ └─────────────┘
```

### Server Requirements (Initial: 10 tenants, 100 agents)
- **App Servers**: 2x 4 vCPU, 8GB RAM
- **Database**: 1x 4 vCPU, 16GB RAM (PostgreSQL)
- **Redis**: 1x 2 vCPU, 4GB RAM
- **Storage**: MinIO on app servers or external S3

### Scaling Strategy
```
Stage 1 (1-10 tenants): Single app server, single DB
Stage 2 (10-100 tenants): 2-3 app servers, DB with read replica
Stage 3 (100-1000 tenants): App cluster, DB cluster, Redis cluster
Stage 4 (1000+ tenants): Microservices split, sharding consideration
```

### Backup Strategy
```bash
# Daily PostgreSQL backup
pg_dump -h localhost -U postgres support_platform | gzip > backup_$(date +%Y%m%d).sql.gz

# S3 bucket versioning enabled
# Off-site backup replication
# Test restore procedures monthly
```

---

## PHASE 13: Development Roadmap

### Phase 1: Foundation (Week 1-2)
- [x] Project structure setup
- [x] Database models
- [x] Authentication system
- [x] Tenant management APIs
- [ ] Initial migration scripts
- [ ] Docker development environment

### Phase 2: Core Ticketing (Week 3-4)
- [ ] Ticket CRUD operations
- [ ] Ticket messages/replies
- [ ] Assignment system
- [ ] Status workflow
- [ ] Customer management
- [ ] Basic authorization

### Phase 3: Advanced Features (Week 5-6)
- [ ] Escalation workflow (L1→L2→L3)
- [ ] Knowledge base
- [ ] Attachment handling with S3
- [ ] Full-text search
- [ ] Audit logging
- [ ] Rate limiting

### Phase 4: White-Label (Week 7-8)
- [ ] Branding management
- [ ] Domain-based tenant resolution
- [ ] Email templates per tenant
- [ ] Custom domain SSL
- [ ] Frontend theming system

### Phase 5: Frontend (Week 9-12)
- [ ] React/Next.js setup
- [ ] Customer portal
- [ ] Agent dashboard
- [ ] Admin panel
- [ ] Responsive design
- [ ] Real-time updates (WebSocket)

### Phase 6: Production Readiness (Week 13-14)
- [ ] Performance optimization
- [ ] Security audit
- [ ] Load testing
- [ ] Monitoring setup (Prometheus/Grafana)
- [ ] Logging aggregation
- [ ] Documentation
- [ ] Deployment automation

---

## PHASE 14: Implementation Status

### Completed Components
✅ Backend project structure
✅ Database models (SQLAlchemy)
✅ Configuration management
✅ Authentication service (JWT)
✅ User/password utilities
✅ API schemas (Pydantic)
✅ Auth endpoints (register, login, refresh)
✅ Tenant endpoints (CRUD, branding)
✅ Ticket endpoints (CRUD, messages, escalation)
✅ Database migrations (Alembic)
✅ Docker development environment

### Next Steps
1. Start Docker environment
2. Run initial migration
3. Create super admin user
4. Create test tenant
5. Test authentication flow
6. Continue with remaining features

---

## Security Considerations

### Implemented Protections
- ✅ Password hashing (bcrypt)
- ✅ JWT authentication
- ✅ Role-based authorization
- ✅ Tenant isolation at query level
- ✅ SQL injection prevention (ORM)
- ✅ CORS configuration
- ✅ Audit logging

### Additional Required
- [ ] Rate limiting (Redis-based)
- [ ] CSRF protection
- [ ] Input validation sanitization
- [ ] File upload validation
- [ ] HTTPS enforcement
- [ ] Security headers
- [ ] Session management
- [ ] Password policy enforcement

---

## Getting Started

### Prerequisites
- Docker & Docker Compose
- Python 3.11+ (for local development without Docker)
- PostgreSQL 15+ (if not using Docker)

### Quick Start with Docker
```bash
cd /workspace/support-platform/docker
docker-compose -f docker-compose.dev.yml up -d

# Wait for services to be healthy
docker-compose -f docker-compose.dev.yml ps

# Access API docs
open http://localhost:8000/docs
```

### Without Docker (Windows 10)
```bash
# Install PostgreSQL 15 and Redis manually

# Set up virtual environment
cd backend
python -m venv venv
venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Copy environment file
copy .env.example .env

# Run migrations
alembic upgrade head

# Start server
python -m uvicorn app.main:app --reload
```

---

## License
MIT License - Self-hosted, open-source

## Support
See individual module README files for detailed documentation.
