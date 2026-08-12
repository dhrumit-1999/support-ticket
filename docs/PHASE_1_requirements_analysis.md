# PHASE 1: Detailed Requirements Analysis

## Executive Summary

This document provides a comprehensive requirements analysis for building a production-ready, open-source, self-hosted, multi-tenant white-label customer support/ticketing platform.

---

## 1. Business Context & Operating Model

### 1.1 Platform Owner Structure

```
Platform Owner (You)
│
├── Tenant A (Client Company A)
│   ├── End Customers (A1, A2, A3...)
│   └── L1 Support Agents (Client's own team)
│
├── Tenant B (Client Company B)
│   ├── End Customers (B1, B2, B3...)
│   └── L1 Support Agents (Client's own team)
│
├── Tenant C (Client Company C)
│   ├── End Customers (C1, C2, C3...)
│   └── L1 Support Agents (Client's own team)
│
└── Internal Support Team (Platform Owner)
    ├── L2 Support Agents (Cross-tenant access)
    └── L3 Support Agents (Engineering/Escalation)
```

### 1.2 Key Business Requirements

1. **Centralized Operation**: Single platform instance serving multiple clients
2. **Complete White-Labeling**: End customers must never see platform owner branding
3. **Tenant Isolation**: Strict data segregation between clients
4. **Tiered Support Model**: L1 (client) → L2 (internal) → L3 (engineering) escalation
5. **Custom Domain Support**: Each tenant can use their own domain
6. **Self-Hosted**: No dependency on proprietary cloud services

---

## 2. Functional Requirements

### 2.1 Multi-Tenancy (FR-MT)

| ID | Requirement | Priority | Security Critical |
|--------|-------------|----------|-------------------|
| FR-MT-01 | Each tenant is a completely isolated logical entity | P0 | YES |
| FR-MT-02 | Tenant data isolation at database level | P0 | YES |
| FR-MT-03 | No cross-tenant data visibility without explicit permission | P0 | YES |
| FR-MT-04 | Tenant-aware API endpoints | P0 | YES |
| FR-MT-05 | Tenant resolution from domain/subdomain | P0 | YES |
| FR-MT-06 | Backend enforcement of tenant boundaries (not just frontend) | P0 | YES |

**Isolation Scope:**
- Customers
- Tickets
- Conversations/Messages
- Attachments
- Knowledge Base articles
- Agents/Users
- Reports/Analytics
- Customer portals
- Email configurations
- Notifications
- Search results
- APIs
- Background jobs
- Audit logs (tenant-specific portions)

### 2.2 White-Label Customer Portal (FR-WL)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-WL-01 | Custom branding per tenant (logo, favicon, colors) | P0 |
| FR-WL-02 | Custom company name display | P0 |
| FR-WL-03 | Custom email sender name and address | P0 |
| FR-WL-04 | Custom portal title and metadata | P0 |
| FR-WL-05 | Custom domain support (CNAME configuration) | P0 |
| FR-WL-06 | No platform owner branding visible to end customers | P0 |
| FR-WL-07 | Tenant-specific login page branding | P1 |
| FR-WL-08 | Configurable portal URL structure | P1 |

**Branding Elements Per Tenant:**
- Company name
- Logo (multiple sizes: header, favicon, email)
- Primary color (hex)
- Secondary color (hex)
- Email sender name
- Email sender address
- Support email address
- Portal title/tagline
- Custom CSS (optional advanced feature)
- Terms of service / Privacy policy links

### 2.3 Customer Ticketing (FR-CT)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-CT-01 | Create new tickets | P0 |
| FR-CT-02 | View own tickets only | P0 |
| FR-CT-03 | Reply to existing tickets | P0 |
| FR-CT-04 | Upload attachments | P0 |
| FR-CT-05 | View ticket history/conversation thread | P0 |
| FR-CT-06 | Receive email notifications | P0 |
| FR-CT-07 | Search own tickets | P1 |
| FR-CT-08 | Close/reopen tickets (configurable) | P1 |
| FR-CT-09 | View relevant knowledge base articles | P1 |
| FR-CT-10 | Rate/survey after ticket closure | P2 |

**Customer Constraints:**
- Must only see tickets they created or are CC'd on
- Must only see knowledge base articles published to customers in their tenant
- Must receive emails with tenant branding, not platform branding
- Cannot access other tenants' data under any circumstance

### 2.4 Client Customer-Care Agents (FR-L1)

| ID | Requirement | Priority | Security Critical |
|--------|-------------|----------|-------------------|
| FR-L1-01 | View all tickets within their tenant | P0 | YES |
| FR-L1-02 | Reply to tickets on behalf of tenant | P0 | YES |
| FR-L1-03 | Assign tickets to other agents in same tenant | P0 | YES |
| FR-L1-04 | Add internal notes (private) | P0 | NO |
| FR-L1-05 | Change ticket status | P0 | NO |
| FR-L1-06 | Escalate tickets to L2 support | P0 | YES |
| FR-L1-07 | View tenant-specific knowledge base | P1 | YES |
| FR-L1-08 | View tenant reports/analytics | P1 | YES |
| FR-L1-09 | CANNOT view other tenants' data | P0 | YES |
| FR-L1-10 | CANNOT escalate to L3 directly (workflow control) | P2 | NO |

**Agent Permissions Matrix:**
```
Action                          | L1 Agent | L2 Agent | L3 Agent | Tenant Admin | Super Admin
--------------------------------|----------|----------|----------|--------------|------------
View own tenant tickets         | ✓        | ✓        | ✓        | ✓            | ✓
View other tenant tickets       | ✗        | ✓        | ✓        | ✗            | ✓
Create ticket                   | ✓        | ✓        | ✓        | ✓            | ✓
Reply to ticket                 | ✓        | ✓        | ✓        | ✓            | ✓
Internal notes                  | ✓        | ✓        | ✓        | ✓            | ✓
Assign within tenant            | ✓        | ✓        | ✓        | ✓            | ✓
Assign cross-tenant             | ✗        | ✓        | ✓        | ✗            | ✓
Escalate to L2                  | ✓        | N/A      | N/A      | ✓            | ✓
Escalate to L3                  | ✗        | ✓        | N/A      | ✗            | ✓
Close ticket                    | ✓        | ✓        | ✓        | ✓            | ✓
Delete ticket                   | ✗        | ✗        | ✗        | ✓            | ✓
View reports (own tenant)       | ✗        | ✗        | ✗        | ✓            | ✓
View reports (all tenants)      | ✗        | ✗        | ✗        | ✗            | ✓
Manage tenant settings          | ✗        | ✗        | ✗        | ✓            | ✓
Manage agents (own tenant)      | ✗        | ✗        | ✗        | ✓            | ✓
Manage all agents               | ✗        | ✗        | ✗        | ✗            | ✓
Access knowledge base (own)     | ✓        | ✓        | ✓        | ✓            | ✓
Access knowledge base (global)  | ✗        | ✓        | ✓        | ✗            | ✓
```

### 2.5 Internal L2/L3 Support Team (FR-L2L3)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-L2L3-01 | Cross-tenant ticket visibility | P0 |
| FR-L2L3-02 | Receive escalated tickets from L1 | P0 |
| FR-L2L3-03 | Escalate from L2 to L3 | P0 |
| FR-L2L3-04 | Add internal notes visible to all support levels | P0 |
| FR-L2L3-05 | Reassign tickets across tenants | P0 |
| FR-L2L3-06 | Access global/internal knowledge base | P1 |
| FR-L2L3-07 | View cross-tenant reports | P1 |
| FR-L2L3-08 | Tag tickets for engineering tracking | P2 |
| FR-L2L3-09 | Link related tickets across tenants (same issue) | P2 |

**L2/L3 Special Capabilities:**
- Work across all tenants without being assigned to specific tenant
- See pattern of issues across multiple tenants
- Access to internal debugging information
- Ability to mark tickets as "known issue" or "bug confirmed"
- Direct communication channel with engineering (L3)

### 2.6 Ticket Escalation Workflow (FR-ESC)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-ESC-01 | Three-tier support levels (L1, L2, L3) | P0 |
| FR-ESC-02 | Formal escalation process with audit trail | P0 |
| FR-ESC-03 | Automatic notification on escalation | P0 |
| FR-ESC-04 | Escalation reasons/categories | P1 |
| FR-ESC-05 | SLA timers reset/pause on escalation (configurable) | P1 |
| FR-ESC-06 | De-escalation capability | P1 |
| FR-ESC-07 | Bulk escalation (for widespread issues) | P2 |
| FR-ESC-08 | Escalation rules/automation | P2 |

**Escalation States:**
```
Ticket Created
    ↓
[L1: Client Agent] ←──────────────┐
    ↓                             │
Cannot Resolve                    │
    ↓                             │
[Escalate to L2]                  │
    ↓                             │
[L2: Internal Support] ←──────────┤
    ↓                             │
Technical/Engineering Issue       │
    ↓                             │
[Escalate to L3]                  │
    ↓                             │
[L3: Engineering Team] ───────────┘
    ↓
Resolution
    ↓
[De-escalate back through chain]
    ↓
Ticket Closed
```

**Escalation Metadata:**
- Escalated from (level)
- Escalated to (level)
- Escalated by (user)
- Escalated at (timestamp)
- Reason for escalation
- Expected resolution time
- Actual resolution time

### 2.7 Email Integration (FR-EMAIL)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-EMAIL-01 | Tenant-specific support email addresses | P0 |
| FR-EMAIL-02 | Incoming email creates ticket under correct tenant | P0 |
| FR-EMAIL-03 | Outgoing emails use tenant branding | P0 |
| FR-EMAIL-04 | Email templates per tenant | P0 |
| FR-EMAIL-05 | No platform branding in emails | P0 |
| FR-EMAIL-06 | Email threading (reply detection) | P0 |
| FR-EMAIL-07 | Attachment handling via email | P1 |
| FR-EMAIL-08 | Multiple email addresses per tenant | P1 |
| FR-EMAIL-09 | IMAP/POP3 integration | P1 |
| FR-EMAIL-10 | SMTP configuration per tenant | P0 |
| FR-EMAIL-11 | Email rate limiting per tenant | P1 |
| FR-EMAIL-12 | Bounce handling | P2 |
| FR-EMAIL-13 | SPF/DKIM guidance per tenant | P2 |

**Email Configuration Per Tenant:**
- SMTP server settings
- SMTP credentials
- From name
- From address
- Reply-to address
- Email signature template
- Ticket creation email template
- Ticket update email template
- Ticket assignment email template
- Ticket closure email template
- Password reset email template
- Welcome email template

### 2.8 Custom Domains (FR-DOMAIN)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-DOMAIN-01 | Support custom domains per tenant | P0 |
| FR-DOMAIN-02 | Tenant resolution from hostname | P0 |
| FR-DOMAIN-03 | SSL certificate management | P0 |
| FR-DOMAIN-04 | CNAME configuration guidance | P0 |
| FR-DOMAIN-05 | Fallback to subdomain if custom domain fails | P1 |
| FR-DOMAIN-06 | Domain verification process | P1 |
| FR-DOMAIN-07 | Multiple domains per tenant (optional) | P2 |

**Domain Resolution Flow:**
```
User Request
    ↓
Reverse Proxy (nginx/traefik)
    ↓
Extract Hostname (support.abc.com)
    ↓
Query: SELECT tenant FROM domains WHERE domain = 'support.abc.com'
    ↓
tenant_id = ABC
    ↓
Load tenant configuration
    ↓
Render portal with tenant branding
```

### 2.9 User Roles & Permissions (FR-ROLE)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-ROLE-01 | SUPER_ADMIN role | P0 |
| FR-ROLE-02 | TENANT_ADMIN role | P0 |
| FR-ROLE-03 | TENANT_AGENT role (L1) | P0 |
| FR-ROLE-04 | L2_AGENT role | P0 |
| FR-ROLE-05 | L3_AGENT role | P0 |
| FR-ROLE-06 | CUSTOMER role | P0 |
| FR-ROLE-07 | Role hierarchy enforcement | P0 |
| FR-ROLE-08 | Granular permissions within roles | P1 |
| FR-ROLE-09 | Custom role creation (per tenant) | P2 |
| FR-ROLE-10 | Role assignment audit logging | P1 |

**Role Definitions:**

**SUPER_ADMIN:**
- Full system access
- Manage all tenants
- Create/delete tenants
- View all tickets across all tenants
- Manage all users
- System configuration
- Billing management (if implemented)
- Global reports and analytics
- Audit log access (all tenants)

**TENANT_ADMIN:**
- Full access within own tenant only
- Manage tenant customers
- Manage tenant agents
- Configure tenant branding
- Configure tenant email settings
- Configure tenant support workflows
- View tenant reports
- Manage tenant knowledge base
- Cannot access other tenants

**TENANT_AGENT (L1):**
- Access own tenant tickets only
- Create/edit/respond to tickets
- Assign tickets within tenant
- Escalate to L2
- View tenant knowledge base
- Cannot access admin settings
- Cannot access other tenants

**L2_AGENT:**
- Cross-tenant ticket access
- Receive escalations from L1
- Escalate to L3
- Access internal knowledge base
- View cross-tenant reports (limited)
- Cannot manage tenant settings

**L3_AGENT:**
- Full cross-tenant access (like L2)
- Engineering-level permissions
- Can mark tickets as bugs/features
- Access to technical debugging info
- Can link related tickets across tenants

**CUSTOMER:**
- View own tickets only
- Create new tickets
- Reply to own tickets
- View customer-facing knowledge base
- Update profile
- Cannot see other customers or their tickets

### 2.10 Knowledge Base (FR-KB)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-KB-01 | Tenant-specific knowledge bases | P0 |
| FR-KB-02 | Customer-facing articles | P0 |
| FR-KB-03 | Internal-only articles | P0 |
| FR-KB-04 | Article categories/tags | P1 |
| FR-KB-05 | Article versioning/history | P1 |
| FR-KB-06 | Search within knowledge base | P1 |
| FR-KB-07 | Article helpfulness rating | P2 |
| FR-KB-08 | Suggest articles during ticket creation | P2 |
| FR-KB-09 | Global/internal KB for support teams | P1 |
| FR-KB-10 | Article draft/publish workflow | P1 |
| FR-KB-11 | Multi-language support (optional) | P3 |

**Knowledge Base Isolation:**
- Tenant A customers see only Tenant A published articles
- Tenant A agents see Tenant A articles + internal articles
- L2/L3 agents see all internal articles + optional global articles
- Super admin sees all knowledge bases

### 2.11 Reporting & Analytics (FR-RPT)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-RPT-01 | Tenant admins see only their tenant data | P0 |
| FR-RPT-02 | Ticket volume reports | P0 |
| FR-RPT-03 | Open/Closed/Pending ticket counts | P0 |
| FR-RPT-04 | SLA compliance reports | P1 |
| FR-RPT-05 | First response time metrics | P1 |
| FR-RPT-06 | Resolution time metrics | P1 |
| FR-RPT-07 | Agent performance reports | P1 |
| FR-RPT-08 | Ticket category breakdown | P1 |
| FR-RPT-09 | Cross-tenant reports for super admin | P0 |
| FR-RPT-10 | Export reports (CSV/PDF) | P2 |
| FR-RPT-11 | Real-time dashboards | P2 |
| FR-RPT-12 | Scheduled report delivery | P2 |

**Report Types:**

**Tenant-Level Reports (Tenant Admin):**
- Total tickets (period)
- Tickets by status
- Tickets by priority
- Tickets by category
- Average first response time
- Average resolution time
- SLA breach count
- Agent workload distribution
- Customer satisfaction (if surveys enabled)
- Ticket trend over time

**Cross-Tenant Reports (Super Admin / L2/L3):**
- All tenant-level reports aggregated
- Per-tenant comparison
- Platform-wide metrics
- Escalation rates per tenant
- Common issues across tenants
- Tenant activity ranking

### 2.12 Audit Logging (FR-AUDIT)

| ID | Requirement | Priority |
|--------|-------------|----------|
| FR-AUDIT-01 | Log all authentication events | P0 |
| FR-AUDIT-02 | Log ticket creation/modification | P0 |
| FR-AUDIT-03 | Log ticket assignment/escalation | P0 |
| FR-AUDIT-04 | Log permission changes | P0 |
| FR-AUDIT-05 | Log tenant configuration changes | P0 |
| FR-AUDIT-06 | Log branding changes | P1 |
| FR-AUDIT-07 | Log customer data changes | P1 |
| FR-AUDIT-08 | Log agent actions | P1 |
| FR-AUDIT-09 | Log attachment access | P1 |
| FR-AUDIT-10 | Log API access | P1 |
| FR-AUDIT-11 | Include user, tenant, action, resource, timestamp, IP | P0 |
| FR-AUDIT-12 | Immutable audit logs | P1 |
| FR-AUDIT-13 | Audit log retention policies | P2 |
| FR-AUDIT-14 | Audit log export | P2 |

**Audit Log Schema:**
```json
{
  "id": "uuid",
  "tenant_id": "uuid (nullable for system actions)",
  "user_id": "uuid",
  "user_email": "string",
  "action": "string (e.g., 'ticket.created', 'user.login')",
  "resource_type": "string (e.g., 'ticket', 'user', 'tenant')",
  "resource_id": "uuid",
  "previous_values": "jsonb (what changed from)",
  "new_values": "jsonb (what changed to)",
  "ip_address": "inet",
  "user_agent": "string",
  "timestamp": "timestamptz"
}
```

---

## 3. Non-Functional Requirements

### 3.1 Security Requirements (NFR-SEC)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-SEC-01 | Prevent cross-tenant data leakage | P0 |
| NFR-SEC-02 | Prevent IDOR (Insecure Direct Object Reference) | P0 |
| NFR-SEC-03 | Prevent broken access control | P0 |
| NFR-SEC-04 | Prevent privilege escalation | P0 |
| NFR-SEC-05 | Prevent unauthorized ticket access | P0 |
| NFR-SEC-06 | Prevent unauthorized attachment access | P0 |
| NFR-SEC-07 | Prevent API tenant manipulation | P0 |
| NFR-SEC-08 | Secure session management | P0 |
| NFR-SEC-09 | CSRF protection | P0 |
| NFR-SEC-10 | XSS prevention | P0 |
| NFR-SEC-11 | SQL injection prevention | P0 |
| NFR-SEC-12 | File upload security | P0 |
| NFR-SEC-13 | Rate limiting | P1 |
| NFR-SEC-14 | Encryption at rest | P1 |
| NFR-SEC-15 | Encryption in transit (TLS) | P0 |
| NFR-SEC-16 | Password hashing (bcrypt/argon2) | P0 |
| NFR-SEC-17 | Account lockout after failed attempts | P1 |
| NFR-SEC-18 | Session timeout | P1 |
| NFR-SEC-19 | Secure password reset flow | P0 |
| NFR-SEC-20 | Input validation and sanitization | P0 |

**Security Architecture Principles:**
1. **Zero Trust**: Never trust client-side tenant_id; always verify server-side
2. **Defense in Depth**: Multiple layers of security controls
3. **Least Privilege**: Users get minimum necessary permissions
4. **Fail Secure**: Errors should not expose sensitive information
5. **Secure by Default**: Conservative defaults, opt-in for permissive settings

### 3.2 Performance Requirements (NFR-PERF)

| ID | Requirement | Target | Priority |
|--------|-------------|--------|----------|
| NFR-PERF-01 | Page load time | < 2 seconds | P1 |
| NFR-PERF-02 | API response time (p95) | < 500ms | P1 |
| NFR-PERF-03 | Search response time | < 1 second | P1 |
| NFR-PERF-04 | Concurrent users supported | 10,000+ | P1 |
| NFR-PERF-05 | Database query time | < 100ms (typical) | P1 |
| NFR-PERF-06 | File upload throughput | 100 MB/s aggregate | P2 |
| NFR-PERF-07 | Email delivery latency | < 30 seconds | P1 |
| NFR-PERF-08 | Background job processing | < 5 minutes lag | P2 |

**Scalability Targets:**

**Initial Scale:**
- 10 tenants
- 100 agents
- 10,000 customers
- 50,000 tickets/year

**Future Scale:**
- 1,000 tenants
- 10,000 agents
- 1,000,000 customers
- 5,000,000 tickets/year

### 3.3 Availability Requirements (NFR-AVAIL)

| ID | Requirement | Target | Priority |
|--------|-------------|--------|----------|
| NFR-AVAIL-01 | Uptime SLA | 99.9% | P0 |
| NFR-AVAIL-02 | Backup frequency | Daily (minimum) | P0 |
| NFR-AVAIL-03 | Recovery Time Objective (RTO) | < 4 hours | P1 |
| NFR-AVAIL-04 | Recovery Point Objective (RPO) | < 1 hour | P1 |
| NFR-AVAIL-05 | Disaster recovery plan | Documented | P1 |
| NFR-AVAIL-06 | Zero-downtime deployments | Target | P2 |

### 3.4 Maintainability Requirements (NFR-MAINT)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-MAINT-01 | Comprehensive documentation | P0 |
| NFR-MAINT-02 | Automated testing (unit, integration, E2E) | P0 |
| NFR-MAINT-03 | Code quality standards | P0 |
| NFR-MAINT-04 | Version control and branching strategy | P0 |
| NFR-MAINT-05 | Database migration system | P0 |
| NFR-MAINT-06 | Configuration management | P0 |
| NFR-MAINT-07 | Logging and monitoring | P0 |
| NFR-MAINT-08 | Error tracking | P1 |
| NFR-MAINT-09 | Performance monitoring | P1 |
| NFR-MAINT-10 | Alerting system | P1 |

### 3.5 Usability Requirements (NFR-USE)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-USE-01 | Intuitive user interface | P0 |
| NFR-USE-02 | Mobile-responsive design | P0 |
| NFR-USE-03 | Accessibility (WCAG 2.1 AA target) | P1 |
| NFR-USE-04 | Multi-language support (i18n) | P2 |
| NFR-USE-05 | Keyboard navigation | P1 |
| NFR-USE-06 | Clear error messages | P0 |
| NFR-USE-07 | Help documentation/tooltips | P1 |
| NFR-USE-08 | Onboarding for new users | P2 |

---

## 4. Technical Requirements

### 4.1 Technology Stack (NFR-TECH)

| Component | Requirement | Notes |
|-----------|-------------|-------|
| Backend Language | Python | Preferred |
| Database | PostgreSQL | Required |
| Cache | Redis | Required |
| Message Queue | Redis/Celery | Preferred |
| Object Storage | S3-compatible | Required |
| Search Engine | PostgreSQL full-text / Elasticsearch | P2 |
| Frontend | Modern JS framework (React/Vue) | P0 |
| Containerization | Docker | Recommended |
| Reverse Proxy | nginx/traefik | P0 |

### 4.2 Database Architecture (NFR-DB)

**Multi-Tenancy Strategy Evaluation:**

| Approach | Pros | Cons | Recommendation |
|----------|------|------|----------------|
| **Shared DB, Shared Schema, tenant_id column** | - Simplest to operate<br>- Easy cross-tenant queries for L2/L3<br>- Cost effective<br>- Easy backups | - Requires careful application-level isolation<br>- Risk of accidental cross-tenant queries<br>- Noisy neighbor problems | **RECOMMENDED** for this use case |
| **Shared DB, Separate Schema per tenant** | - Better isolation<br>- Easier per-tenant backups<br>- Can restore individual tenants | - More complex migrations<br>- Harder cross-tenant queries<br>- Schema proliferation at scale | Consider for 100+ tenants |
| **Separate Database per tenant** | - Maximum isolation<br>- Per-tenant customization<br>- Easy to move tenants | - Very complex operations<br>- Expensive<br>- Overkill for most SaaS | Not recommended unless required by compliance |

**Recommended Approach: Shared Database with tenant_id**

**Rationale:**
1. Operational simplicity for initial scale (10-100 tenants)
2. Efficient cross-tenant queries for L2/L3 support
3. Cost-effective storage
4. Can migrate to schema-per-tenant later if needed
5. Application-level enforcement is sufficient with proper architecture

**Mitigation Strategies:**
- Row Level Security (RLS) in PostgreSQL as additional safeguard
- Comprehensive testing for tenant isolation
- Code review checklist for tenant-aware queries
- ORM middleware to automatically inject tenant_id filters
- Audit logging for all data access

### 4.3 API Requirements (NFR-API)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-API-01 | RESTful API design | P0 |
| NFR-API-02 | Authentication (JWT or session-based) | P0 |
| NFR-API-03 | Rate limiting | P0 |
| NFR-API-04 | Versioning strategy | P1 |
| NFR-API-05 | Comprehensive error responses | P0 |
| NFR-API-06 | Pagination for list endpoints | P0 |
| NFR-API-07 | Filtering and sorting | P1 |
| NFR-API-08 | Webhook support | P1 |
| NFR-API-09 | API documentation (OpenAPI/Swagger) | P1 |
| NFR-API-10 | Tenant context in every request | P0 |

**API Security:**
- Never accept tenant_id from client; derive from auth token/session
- Validate user has permission for requested resource
- Return 404 (not 403) for resources user shouldn't know exist
- Implement proper CORS policies
- Use HTTPS exclusively

### 4.4 File Storage (NFR-FILE)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-FILE-01 | S3-compatible object storage | P0 |
| NFR-FILE-02 | Tenant-isolated file paths | P0 |
| NFR-FILE-03 | Authorization before file serving | P0 |
| NFR-FILE-04 | Virus scanning on upload | P1 |
| NFR-FILE-05 | File type validation | P0 |
| NFR-FILE-06 | Size limits per file and total | P0 |
| NFR-FILE-07 | Presigned URLs for secure access | P1 |
| NFR-FILE-08 | CDN integration (optional) | P2 |

**File Path Structure:**
```
s3://bucket/tenants/{tenant_id}/attachments/{attachment_id}/{filename}
s3://bucket/tenants/{tenant_id}/logos/{tenant_id}_header.png
s3://bucket/tenants/{tenant_id}/favicons/{tenant_id}.ico
```

### 4.5 Email Architecture (NFR-EMAIL-TECH)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-EMAIL-TECH-01 | SMTP relay configuration | P0 |
| NFR-EMAIL-TECH-02 | IMAP/POP3 mailbox monitoring | P0 |
| NFR-EMAIL-TECH-03 | Email parsing (multipart, attachments) | P0 |
| NFR-EMAIL-TECH-04 | Template engine for emails | P0 |
| NFR-EMAIL-TECH-05 | Email queue for reliability | P0 |
| NFR-EMAIL-TECH-06 | Bounce handling | P1 |
| NFR-EMAIL-TECH-07 | Unsubscribe handling | P1 |
| NFR-EMAIL-TECH-08 | Email deliverability best practices | P1 |

**Email Flow:**
```
Outbound:
Application → Email Queue → SMTP Relay → Recipient

Inbound:
Mailbox → IMAP Poller → Email Parser → Ticket Creator → Application
```

### 4.6 Authentication & Authorization (NFR-AUTH)

| ID | Requirement | Priority |
|--------|-------------|----------|
| NFR-AUTH-01 | Secure password storage (bcrypt/argon2) | P0 |
| NFR-AUTH-02 | Session management | P0 |
| NFR-AUTH-03 | JWT tokens (if using stateless auth) | P1 |
| NFR-AUTH-04 | OAuth2/OIDC integration (optional) | P2 |
| NFR-AUTH-05 | Multi-factor authentication (optional) | P2 |
| NFR-AUTH-06 | Password reset flow | P0 |
| NFR-AUTH-07 | Account lockout policy | P1 |
| NFR-AUTH-08 | Session timeout | P1 |
| NFR-AUTH-09 | Remember me functionality | P2 |
| NFR-AUTH-10 | Single Sign-On (SSO) per tenant (optional) | P3 |

---

## 5. Compliance & Legal Considerations

### 5.1 Data Protection

| Requirement | Notes |
|-------------|-------|
| GDPR compliance | If serving EU customers |
| Data residency | Some tenants may require data in specific regions |
| Right to erasure | Customer data deletion capabilities |
| Data portability | Export customer data on request |
| Privacy policy | Required for each tenant's portal |
| Terms of service | Required for each tenant's portal |
| Cookie consent | If using cookies for tracking/analytics |

### 5.2 Industry-Specific Requirements

| Industry | Additional Requirements |
|----------|------------------------|
| Healthcare (HIPAA) | Encryption, audit logs, BA agreements |
| Finance (SOC 2) | Access controls, monitoring, change management |
| Government | Data residency, enhanced security |
| Education (FERPA) | Student data protection |

---

## 6. Gap Analysis: Existing Solutions vs Requirements

### 6.1 Key Gaps in Most Open-Source Ticketing Systems

Based on preliminary research, most open-source ticketing systems have the following gaps:

1. **True Multi-Tenancy**: Most have "organizations" but not true tenant isolation
2. **White-Label Portals**: Limited or no custom domain support
3. **Per-Tenant Branding**: Basic logo upload, but not comprehensive theming
4. **Cross-Tenant Support Teams**: Not designed for platform-owner scenario
5. **Custom Domain Resolution**: Rarely supported out-of-the-box
6. **Tenant-Specific Email**: Usually global email configuration only
7. **Hierarchical Escalation**: Basic assignment, not formal L1/L2/L3 model

### 6.2 Conclusion

A hybrid approach is likely necessary:
- Use an existing open-source ticketing engine for core ticket functionality
- Build a multi-tenant white-label layer on top
- Implement custom tenant resolution and branding
- Add cross-tenant support team capabilities

---

## 7. Next Steps

Proceed to **PHASE 2: Compare Existing Open-Source Solutions** for detailed evaluation of Zammad, FreeScout, UVdesk, osTicket, Frappe Helpdesk, GLPI, and other candidates.
