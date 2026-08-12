# PHASE 2: Compare Existing Open-Source Solutions

## Executive Summary

This document evaluates existing open-source ticketing/helpdesk systems against our multi-tenant white-label requirements. We examine Zammad, FreeScout, UVdesk, osTicket, Frappe Helpdesk, GLPI, and other candidates to determine the best foundation for our platform.

---

## Evaluation Criteria

Each solution is evaluated against these key criteria (weighted by importance):

| Criterion | Weight | Description |
|-----------|--------|-------------|
| **Multi-Tenancy** | 25% | True tenant isolation, not just "organizations" |
| **White-Label Portal** | 20% | Custom branding, custom domains, no vendor branding |
| **Agent Permissions** | 15% | Role hierarchy, cross-tenant access capability |
| **Email Integration** | 10% | Per-tenant email, incoming/outgoing |
| **Knowledge Base** | 10% | Tenant-isolated KB, internal/external articles |
| **API & Extensibility** | 10% | REST API, webhooks, plugin architecture |
| **Self-Hosting** | 5% | Easy deployment, no proprietary dependencies |
| **License** | 5% | Open-source license suitability for commercial use |

---

## 1. Zammad

### Overview
- **Website**: https://zammad.org
- **License**: AGPL-3.0
- **Language**: Ruby on Rails (backend), Vue.js (frontend)
- **Database**: PostgreSQL, MySQL, SQLite
- **First Release**: 2016
- **Current Version**: 6.x (as of 2024)
- **GitHub Stars**: 4,000+

### Multi-Tenancy Evaluation

**Official Stance**: Zammad does NOT have built-in multi-tenancy in the core product.

**Available Approaches**:
1. **Organizations**: Zammad has an "Organization" feature that groups customers, but:
   - Organizations share the same agent pool
   - No data isolation at database level
   - Agents can see all organizations by default
   - Not designed for true SaaS multi-tenancy
   - More like "companies" within a single organization's helpdesk

2. **Zammad Multi-Tenancy Plugin** (Community):
   - Third-party plugins exist but are not officially supported
   - Varying quality and maintenance status
   - May not cover all isolation requirements

3. **Separate Instances**:
   - Run separate Zammad instances per tenant
   - Prohibitive operational overhead
   - No cross-tenant reporting
   - L2/L3 support would need multiple logins

**Verdict**: ❌ **Does not meet multi-tenancy requirements out-of-the-box**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Logo upload (system-wide)
- ✓ Primary color configuration (system-wide)
- ✓ Email sender name/address (system-wide)
- ✓ Custom domain support (via reverse proxy)
- ✗ Per-tenant branding (requires multi-tenancy first)
- ✗ Per-tenant logo/colors (requires multi-tenancy first)
- ⚠️ "Powered by Zammad" can be removed (requires configuration)

**Customer Portal**:
- Modern, responsive design
- Ticket creation and viewing
- Knowledge base integration
- Search functionality
- Profile management

**Verdict**: ⚠️ **Good portal features but no per-tenant branding without customization**

### Agent Permissions Evaluation

**Built-in Roles**:
- Customer (no agent access)
- Agent (basic agent permissions)
- Admin (full system access)

**Permission Groups**:
- Ticket ownership (own, shared, all)
- Read/write/delete permissions per group
- Custom roles can be created
- Granular permissions available

**Limitations**:
- No native concept of "tenant-scoped agents"
- Cannot restrict agents to specific organizations completely
- No built-in L1/L2/L3 escalation model
- Cross-tenant access not applicable (no multi-tenancy)

**Verdict**: ⚠️ **Good permission system but not designed for multi-tenant hierarchy**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3 inbound email
- ✓ SMTP outbound email
- ✓ Email threading (Message-ID based)
- ✓ Attachment handling
- ✓ Multiple email addresses supported
- ✓ Email templates customizable
- ✗ Per-tenant email configuration (requires multi-tenancy)
- ✓ Postmaster mailbox for undeliverables

**Email Features**:
- Automatic ticket creation from emails
- Reply detection and threading
- Attachment support
- Signature support
- Canned responses (text modules)

**Verdict**: ✓ **Excellent email integration, but global configuration only**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Internal knowledge base (agents only)
- ✓ External knowledge base (public)
- ✓ Categories and tags
- ✓ Rich text editor
- ✓ Attachments in articles
- ✓ Article versioning
- ✓ Search functionality
- ✗ Tenant-isolated knowledge bases (requires multi-tenancy)

**Verdict**: ✓ **Strong KB features, but not tenant-isolated**

### API & Extensibility Evaluation

**REST API**:
- ✓ Comprehensive REST API
- ✓ Token-based authentication
- ✓ WebSocket support for real-time updates
- ✓ Rate limiting
- ✓ API documentation available
- ✓ Webhooks for events

**Extensibility**:
- Plugin architecture exists
- Custom app development possible (Ruby on Rails)
- Theme customization limited
- Can modify source code (AGPL allows with conditions)

**Verdict**: ✓ **Excellent API and extensibility**

### Self-Hosting Evaluation

**Deployment Options**:
- Docker containers available
- Package installation (DEB/RPM)
- Source installation
- Cloud-hosted option available

**Requirements**:
- PostgreSQL/MySQL
- Redis (required)
- Elasticsearch (optional, for search)
- nginx/Apache (reverse proxy)
- Minimum 2GB RAM recommended

**Complexity**: Moderate
- Well-documented installation
- Active community support
- Regular updates

**Verdict**: ✓ **Good self-hosting support**

### License Evaluation

**License**: AGPL-3.0

**Implications**:
- ✓ Can use commercially
- ✓ Can modify source code
- ⚠️ Must release modifications if distributed
- ⚠️ Network use counts as distribution (AGPL clause)
- ⚠️ If you offer as SaaS, must provide source to users

**For Our Use Case**:
- We're self-hosting and not distributing software
- Users are customers of our service, not software recipients
- Legal interpretation varies; consult attorney
- May need to open-source our modifications

**Verdict**: ⚠️ **Acceptable but requires legal review for commercial SaaS**

### Zammad Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 1/5 | Not supported natively; requires significant customization |
| White-Label Portal | 3/5 | Good portal, but no per-tenant branding |
| Agent Permissions | 3/5 | Good granularity, no tenant scoping |
| Email Integration | 5/5 | Excellent email features |
| Knowledge Base | 4/5 | Strong KB, not tenant-isolated |
| API & Extensibility | 5/5 | Excellent API and plugin system |
| Self-Hosting | 4/5 | Well-documented, moderate complexity |
| License | 3/5 | AGPL requires careful consideration |

**Overall Score**: 3.5/5

**Recommendation**: Zammad has excellent core ticketing features but lacks multi-tenancy. Would require building a multi-tenant layer on top, essentially using Zammad as a ticket engine accessed via API.

---

## 2. FreeScout

### Overview
- **Website**: https://freescout.net
- **License**: AGPL-3.0
- **Language**: PHP (Laravel framework)
- **Database**: MySQL/MariaDB
- **First Release**: 2018
- **Current Version**: 1.x (as of 2024)
- **GitHub Stars**: 2,000+

### Multi-Tenancy Evaluation

**Official Stance**: FreeScout does NOT have built-in multi-tenancy.

**Available Approaches**:
1. **Mailboxes as Tenants** (Workaround):
   - Each tenant gets a separate mailbox
   - Agents can be assigned to specific mailboxes
   - Limited isolation; agents can still see other mailboxes if permitted
   - Not true tenant isolation

2. **Separate Instances**:
   - Same limitations as Zammad
   - Operational overhead

3. **Custom Development**:
   - Laravel makes customization easier
   - No official multi-tenancy package from FreeScout

**Verdict**: ❌ **Does not meet multi-tenancy requirements**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Logo upload
- ✓ Color customization
- ✓ Custom domain support
- ✓ Remove "Powered by FreeScout" (paid module)
- ✗ Per-tenant branding (requires multi-tenancy)
- ✓ Email templates customizable

**Customer Portal**:
- Basic portal for ticket viewing
- Email-based interaction primary
- Less sophisticated than Zammad's portal

**Verdict**: ⚠️ **Basic white-label features, no per-tenant branding**

### Agent Permissions Evaluation

**Built-in Roles**:
- Customer
- Agent
- Admin

**Permission Features**:
- Mailbox-level permissions
- Department assignments
- Custom roles (paid module)
- Limited granularity compared to Zammad

**Verdict**: ⚠️ **Basic permission system**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3 inbound
- ✓ SMTP outbound
- ✓ Email threading
- ✓ Attachment handling
- ✓ Multiple mailboxes
- ⚠️ Per-mailbox configuration (could map to tenants)

**Strengths**:
- Email-first design (inherits from Help Scout concept)
- Excellent email threading
- Collision avoidance (agents see when others viewing)
- Shared drafts

**Verdict**: ✓ **Excellent email integration**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Knowledge base available (paid module)
- ✓ Categories
- ✓ Public/private articles
- ✗ Tenant isolation not applicable

**Verdict**: ⚠️ **KB is a paid add-on, basic features**

### API & Extensibility Evaluation

**REST API**:
- ✓ REST API available
- ✓ API authentication
- ⚠️ Less comprehensive than Zammad
- ✓ Webhooks available

**Extensibility**:
- ✓ Module system (plugins)
- ✓ Many official modules (paid)
- ✓ Custom module development possible
- Laravel ecosystem benefits

**Verdict**: ⚠️ **Good extensibility, API less mature**

### Self-Hosting Evaluation

**Deployment**:
- ✓ Docker available
- ✓ Traditional LAMP stack
- ✓ Shared hosting possible (PHP advantage)

**Requirements**:
- MySQL/MariaDB
- PHP 7.4+
- Web server (nginx/Apache)
- Simpler than Zammad (no Redis required for basic operation)

**Verdict**: ✓ **Easy to self-host**

### License Evaluation

**License**: AGPL-3.0

**Additional Considerations**:
- Core is free
- Many useful features are paid modules
- Commercial modules have separate licensing
- Total cost of ownership higher than apparent

**Verdict**: ⚠️ **AGPL + paid modules model**

### FreeScout Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 1/5 | Not supported |
| White-Label Portal | 3/5 | Basic features, some paid |
| Agent Permissions | 2/5 | Limited granularity |
| Email Integration | 5/5 | Excellent, email-first design |
| Knowledge Base | 2/5 | Paid add-on, basic |
| API & Extensibility | 3/5 | Good but less mature |
| Self-Hosting | 5/5 | Very easy (PHP) |
| License | 3/5 | AGPL + paid modules |

**Overall Score**: 3.0/5

**Recommendation**: FreeScout is simpler than Zammad but also less capable. Better for email-centric support but worse for multi-tenancy requirements.

---

## 3. UVdesk

### Overview
- **Website**: https://www.uvdesk.com
- **License**: MIT License
- **Language**: PHP (Symfony framework)
- **Database**: MySQL/MariaDB
- **First Release**: 2017
- **Current Version**: 1.x (as of 2024)
- **GitHub Stars**: 1,000+

### Multi-Tenancy Evaluation

**Official Stance**: UVdesk Community Edition does NOT have multi-tenancy.

**Note**: UVdesk has a commercial SaaS version with multi-tenancy, but the open-source version does not include this feature.

**Available Approaches**:
- Same limitations as other solutions
- Would require custom development

**Verdict**: ❌ **No multi-tenancy in open-source version**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Theme customization
- ✓ Logo upload
- ✓ Color customization
- ✓ Custom domain support
- ✗ Per-tenant branding

**Verdict**: ⚠️ **Good single-instance white-label**

### Agent Permissions Evaluation

**Built-in Roles**:
- Customer
- Agent
- Supervisor
- Admin

**Features**:
- Department-based organization
- Group assignments
- Permission sets
- More granular than FreeScout

**Verdict**: ⚠️ **Good permission system for single tenant**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3
- ✓ SMTP
- ✓ Email piping
- ✓ Templates
- ✓ Multiple mailboxes

**Verdict**: ✓ **Good email integration**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Built-in knowledge base
- ✓ Categories
- ✓ Public/private
- ✓ Feedback system
- ✓ SEO-friendly

**Verdict**: ✓ **Strong KB features**

### API & Extensibility Evaluation

**REST API**:
- ✓ REST API available
- ⚠️ Documentation limited
- ✓ Webhooks

**Extensibility**:
- ✓ Bundle system (Symfony bundles)
- ✓ Marketplace for extensions
- ⚠️ Smaller ecosystem than competitors

**Verdict**: ⚠️ **Adequate API and extensibility**

### Self-Hosting Evaluation

**Deployment**:
- ✓ Docker available
- ✓ Traditional LAMP/LEMP stack
- ✓ Composer-based installation

**Requirements**:
- MySQL/MariaDB
- PHP 7.4+
- Symfony dependencies

**Verdict**: ✓ **Good self-hosting support**

### License Evaluation

**License**: MIT License

**Implications**:
- ✓ Very permissive
- ✓ Can use commercially
- ✓ Can modify without releasing source
- ✓ Best license for commercial use

**Verdict**: ✓ **Excellent license for our use case**

### UVdesk Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 1/5 | Not in open-source version |
| White-Label Portal | 3/5 | Good single-instance |
| Agent Permissions | 3/5 | Good granularity |
| Email Integration | 4/5 | Solid features |
| Knowledge Base | 4/5 | Strong KB |
| API & Extensibility | 3/5 | Adequate |
| Self-Hosting | 4/5 | Good support |
| License | 5/5 | MIT - very permissive |

**Overall Score**: 3.4/5

**Recommendation**: UVdesk has the best license for commercial use but lacks multi-tenancy. Similar customization burden as others.

---

## 4. osTicket

### Overview
- **Website**: https://osticket.com
- **License**: GPL-2.0
- **Language**: PHP
- **Database**: MySQL/MariaDB
- **First Release**: 2007
- **Current Version**: 1.18.x (as of 2024)
- **GitHub Stars**: 3,000+

### Multi-Tenancy Evaluation

**Official Stance**: osTicket does NOT have built-in multi-tenancy.

**Available Approaches**:
- Organizations exist but are not isolated
- No tenant scoping
- Would require extensive customization

**Verdict**: ❌ **No multi-tenancy**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Logo upload
- ✓ Color customization
- ✓ Custom pages
- ✓ Email templates
- ✗ Per-tenant branding
- ⚠️ UI feels dated compared to modern alternatives

**Verdict**: ⚠️ **Functional but dated**

### Agent Permissions Evaluation

**Built-in Roles**:
- Customizable roles
- Department-based access
- Team assignments
- Good granularity

**Verdict**: ⚠️ **Good for single tenant**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3
- ✓ SMTP
- ✓ Email piping
- ✓ Fetching via cron
- ✓ Multiple departments with different emails

**Verdict**: ✓ **Mature email integration**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Built-in FAQ system
- ✓ Categories
- ✓ Public/private
- ⚠️ Basic features compared to modern KBs

**Verdict**: ⚠️ **Basic KB**

### API & Extensibility Evaluation

**REST API**:
- ✓ API available
- ⚠️ API less modern (reflects age of project)
- ✓ Webhooks via plugins

**Extensibility**:
- ✓ Plugin system
- ✓ Many community plugins
- ⚠️ Variable quality
- ✓ Can modify source (GPL)

**Verdict**: ⚠️ **Adequate but showing age**

### Self-Hosting Evaluation

**Deployment**:
- ✓ Very easy (traditional PHP app)
- ✓ Shared hosting compatible
- ✓ Large install base
- ✓ Extensive documentation

**Verdict**: ✓ **Very easy to self-host**

### License Evaluation

**License**: GPL-2.0

**Implications**:
- ✓ Can use commercially
- ⚠️ Must release modifications if distributed
- ⚠️ Less clear on SaaS/network use vs AGPL

**Verdict**: ⚠️ **Acceptable but GPL considerations**

### osTicket Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 1/5 | Not supported |
| White-Label Portal | 2/5 | Dated UI |
| Agent Permissions | 3/5 | Good granularity |
| Email Integration | 4/5 | Mature |
| Knowledge Base | 2/5 | Basic |
| API & Extensibility | 2/5 | Showing age |
| Self-Hosting | 5/5 | Very easy |
| License | 3/5 | GPL-2.0 |

**Overall Score**: 2.8/5

**Recommendation**: osTicket is mature and stable but showing its age. Not recommended for modern multi-tenant SaaS.

---

## 5. Frappe Helpdesk

### Overview
- **Website**: https://frappe.io/helpdesk
- **License**: GPL-3.0
- **Language**: Python (backend), Vue.js (frontend)
- **Database**: MariaDB/PostgreSQL
- **Framework**: Frappe Framework
- **First Release**: 2021 (modern version)
- **Current Version**: 1.x (as of 2024)
- **GitHub Stars**: 500+

### Multi-Tenancy Evaluation

**Official Stance**: Frappe Helpdesk itself does NOT have multi-tenancy.

**Important Note**: The underlying Frappe Framework DOES have multi-tenancy support!

**Frappe Framework Multi-Tenancy**:
- Sites concept: each site is a separate tenant
- Separate databases per site
- `bench new-site` creates new tenant
- Used by ERPNext for multi-tenancy

**Approach for Our Use Case**:
- Could run each tenant as a separate Frappe site
- OR customize Helpdesk to add tenant_id-based multi-tenancy
- Framework provides tools for both approaches

**Trade-offs**:
- Separate sites = better isolation but harder cross-tenant queries
- Shared schema = easier cross-tenant but more customization

**Verdict**: ⚠️ **Framework supports multi-tenancy; Helpdesk needs customization**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Branding customization
- ✓ Logo upload
- ✓ Color themes
- ✓ Email templates
- ✗ Per-tenant branding (needs customization)
- ✓ Modern UI

**Verdict**: ⚠️ **Good foundation, needs customization**

### Agent Permissions Evaluation

**Built-in Roles**:
- Customer
- Agent
- Manager
- Administrator
- Custom roles supported

**Features**:
- Role-based permissions
- User type restrictions
- DocType-level permissions (Frappe feature)
- Field-level permissions

**Verdict**: ✓ **Strong permission system via Frappe**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3
- ✓ SMTP
- ✓ Email account per doctype
- ✓ Auto-reply
- ✓ Email tracking
- ✓ Templates

**Verdict**: ✓ **Good email integration**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Knowledge base included
- ✓ Categories
- ✓ Public/private
- ✓ Rich text
- ✓ Search

**Verdict**: ✓ **Good KB**

### API & Extensibility Evaluation

**REST API**:
- ✓ Auto-generated REST API for all DocTypes
- ✓ GraphQL available
- ✓ Authentication (JWT, session)
- ✓ Rate limiting
- ✓ Excellent documentation

**Extensibility**:
- ✓ Frappe app system
- ✓ Server scripts
- ✓ Client scripts
- ✓ Custom DocTypes
- ✓ Hooks system
- ✓ Python + JavaScript extensibility

**Verdict**: ✓✓ **Excellent - best in class**

### Self-Hosting Evaluation

**Deployment**:
- ✓ Docker available
- ✓ Bench CLI for management
- ✓ Production setup scripts

**Requirements**:
- MariaDB/PostgreSQL
- Redis
- Python 3.8+
- Node.js (for frontend)

**Complexity**: Moderate to High
- Frappe Framework has learning curve
- More complex than simple PHP apps
- But well-documented

**Verdict**: ⚠️ **Good support but steeper learning curve**

### License Evaluation

**License**: GPL-3.0

**Implications**:
- ✓ Can use commercially
- ⚠️ Must release modifications if distributed
- ⚠️ Network use considerations (similar to AGPL)

**Verdict**: ⚠️ **Acceptable with legal review**

### Frappe Helpdesk Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 3/5 | Framework supports it; needs customization |
| White-Label Portal | 3/5 | Good foundation |
| Agent Permissions | 4/5 | Strong via Frappe |
| Email Integration | 4/5 | Good |
| Knowledge Base | 4/5 | Good |
| API & Extensibility | 5/5 | Best in class |
| Self-Hosting | 3/5 | Moderate complexity |
| License | 3/5 | GPL-3.0 |

**Overall Score**: 3.6/5

**Recommendation**: Frappe Helpdesk has the best extensibility and Python backend (matches your preference). Framework multi-tenancy is a significant advantage. However, requires learning Frappe Framework.

---

## 6. GLPI

### Overview
- **Website**: https://glpi-project.org
- **License**: GPL-2.0
- **Language**: PHP
- **Database**: MySQL/MariaDB
- **First Release**: 2003
- **Current Version**: 10.x (as of 2024)
- **GitHub Stars**: 4,000+

### Context

GLPI is primarily an IT Service Management (ITSM) tool, not just a helpdesk. It includes:
- Asset management
- Configuration management database (CMDB)
- Project management
- Knowledge base
- Helpdesk/ticketing

### Multi-Tenancy Evaluation

**Official Stance**: GLPI has "Entities" which provide hierarchical multi-tenancy.

**Entity System**:
- Recursive entity structure
- Users can be assigned to specific entities
- Tickets belong to entities
- Data isolation by entity
- Can view sub-entity data (configurable)

**Limitations for Our Use Case**:
- Entities designed for organizational hierarchy, not SaaS tenants
- Complex recursive structure may be overkill
- Some data still shared across entities
- Not designed for complete white-label per entity

**Verdict**: ⚠️ **Has entity-based isolation but not ideal for SaaS multi-tenancy**

### White-Label Portal Evaluation

**Capabilities**:
- ✓ Basic theming
- ✓ Logo upload
- ✗ Limited per-entity branding
- ✗ No custom domain per entity
- ⚠️ UI is functional but not modern

**Verdict**: ⚠️ **Limited white-label capabilities**

### Agent Permissions Evaluation

**Built-in Profiles**:
- Highly customizable profiles
- Over 100 permission options
- Entity-based restrictions
- Very granular

**Verdict**: ✓ **Excellent permission granularity**

### Email Integration Evaluation

**Capabilities**:
- ✓ IMAP/POP3 collectors
- ✓ SMTP
- ✓ Email gateways
- ✓ Rules for ticket assignment
- ✓ Multiple collectors

**Verdict**: ✓ **Comprehensive email integration**

### Knowledge Base Evaluation

**Capabilities**:
- ✓ Full KB system
- ✓ Categories
- ✓ Visibility rules (entity-based)
- ✓ FAQ mode
- ✓ Attachments

**Verdict**: ✓ **Strong KB**

### API & Extensibility Evaluation

**REST API**:
- ✓ REST API available
- ✓ API token authentication
- ✓ Comprehensive coverage
- ✓ Documentation available

**Extensibility**:
- ✓ Plugin system
- ✓ Many official plugins
- ✓ Large community
- ⚠️ Plugin quality varies

**Verdict**: ⚠️ **Good API, extensive but complex plugin system**

### Self-Hosting Evaluation

**Deployment**:
- ✓ Traditional PHP installation
- ✓ Docker available
- ✓ Appliances available
- ✓ Large install base

**Requirements**:
- MySQL/MariaDB
- PHP
- Web server
- Redis (optional, for caching)

**Verdict**: ✓ **Well-supported self-hosting**

### License Evaluation

**License**: GPL-2.0

**Verdict**: ⚠️ **Acceptable**

### GLPI Summary

| Criterion | Score | Notes |
|-----------|-------|-------|
| Multi-Tenancy | 2/5 | Entity system exists but not ideal for SaaS |
| White-Label Portal | 2/5 | Limited |
| Agent Permissions | 5/5 | Excellent granularity |
| Email Integration | 4/5 | Comprehensive |
| Knowledge Base | 4/5 | Strong |
| API & Extensibility | 3/5 | Good but complex |
| Self-Hosting | 4/5 | Well-supported |
| License | 3/5 | GPL-2.0 |

**Overall Score**: 3.4/5

**Recommendation**: GLPI is powerful but overly complex for pure ticketing. Entity system is interesting but not designed for white-label SaaS. Better suited for internal IT departments.

---

## 7. Other Notable Solutions

### Deskpro (On-Premise)
- **License**: Proprietary (not open-source)
- **Multi-Tenancy**: Yes (in enterprise version)
- **Verdict**: ❌ Not open-source, excluded from consideration

### Hesk
- **License**: Proprietary (free version available)
- **Multi-Tenancy**: No
- **Verdict**: ❌ Not truly open-source

### SupportPal
- **License**: Proprietary
- **Multi-Tenancy**: Yes (built for this)
- **Verdict**: ❌ Not open-source

### Znuny (osTicket fork)
- **License**: GPL
- **Multi-Tenancy**: No
- **Verdict**: ⚠️ Similar to osTicket with some improvements

### Request Tracker (RT)
- **License**: Perl-based, old-school
- **Multi-Tenancy**: Limited
- **Verdict**: ⚠️ Powerful but very dated, steep learning curve

---

## Comparative Summary Table

| Solution | Multi-Tenancy | White-Label | Permissions | Email | KB | API | Self-Host | License | Overall |
|----------|--------------|-------------|-------------|-------|-----|-----|-----------|---------|---------|
| **Zammad** | 1/5 | 3/5 | 3/5 | 5/5 | 4/5 | 5/5 | 4/5 | 3/5 | **3.5/5** |
| **FreeScout** | 1/5 | 3/5 | 2/5 | 5/5 | 2/5 | 3/5 | 5/5 | 3/5 | **3.0/5** |
| **UVdesk** | 1/5 | 3/5 | 3/5 | 4/5 | 4/5 | 3/5 | 4/5 | 5/5 | **3.4/5** |
| **osTicket** | 1/5 | 2/5 | 3/5 | 4/5 | 2/5 | 2/5 | 5/5 | 3/5 | **2.8/5** |
| **Frappe Helpdesk** | 3/5 | 3/5 | 4/5 | 4/5 | 4/5 | 5/5 | 3/5 | 3/5 | **3.6/5** |
| **GLPI** | 2/5 | 2/5 | 5/5 | 4/5 | 4/5 | 3/5 | 4/5 | 3/5 | **3.4/5** |

---

## Key Findings

### 1. No Perfect Fit

**Critical Finding**: NO evaluated open-source solution meets all requirements out-of-the-box, specifically:
- True multi-tenancy with tenant isolation
- Per-tenant white-label branding
- Custom domain per tenant
- L1/L2/L3 escalation model with cross-tenant access

### 2. Multi-Tenancy Gap

All solutions lack proper SaaS-style multi-tenancy:
- Most have "organizations" or "entities" but these are not isolated tenants
- None support per-tenant branding out-of-the-box
- None support custom domains per tenant
- None designed for platform-owner scenario (cross-tenant support team)

### 3. Best Candidates

**Top 3 Recommendations**:

1. **Frappe Helpdesk** (Score: 3.6/5)
   - Pros: Python backend, framework multi-tenancy, excellent API/extensibility
   - Cons: Learning curve, needs customization
   - Best for: Teams comfortable with Python/Frappe

2. **Zammad** (Score: 3.5/5)
   - Pros: Modern UI, excellent email, great API, strong KB
   - Cons: Ruby backend, AGPL license, no multi-tenancy
   - Best for: Teams wanting polished UX, can handle Ruby

3. **UVdesk** (Score: 3.4/5)
   - Pros: MIT license (best for commercial), decent features
   - Cons: Smaller community, less mature
   - Best for: Teams prioritizing license flexibility

### 4. Build vs Customize Decision

Given the gaps, you have three options:

**Option A: Customize Existing Solution**
- Pick Zammad/Frappe/UVdesk
- Build multi-tenant layer on top
- Use their API for ticket operations
- Build custom white-label portal

**Option B: Build From Scratch**
- Use their concepts/features as inspiration
- Design multi-tenancy from ground up
- Full control over architecture
- Higher development effort

**Option C: Hybrid Approach (RECOMMENDED)**
- Use existing solution as ticket ENGINE only
- Build multi-tenant white-label layer separately
- Communicate via API
- Best balance of speed and customization

---

## Recommendation

### Recommended Approach: **Hybrid with Zammad as Engine**

**Rationale**:

1. **Zammad Strengths**:
   - Best-in-class email integration
   - Excellent API
   - Modern, polished UI (can learn from)
   - Strong knowledge base
   - Good permission system
   - Active development and community

2. **Architecture**:
   ```
   White-Label Portal (Custom)
           ↓
   Tenant Resolution Layer (Custom)
           ↓
   Multi-Tenant API Gateway (Custom)
           ↓
   Zammad Instance(s) (Existing)
   ```

3. **What We Build**:
   - Multi-tenant user management
   - Tenant resolution from domain
   - Per-tenant branding storage
   - White-label customer portal
   - Tenant-aware API gateway
   - Cross-tenant L2/L3 support interface
   - Custom domain management
   - Per-tenant email configuration wrapper

4. **What Zammad Provides**:
   - Ticket CRUD operations
   - Email integration (incoming/outgoing)
   - Knowledge base engine
   - Agent interface (internal use)
   - Workflow automation
   - Reporting engine

5. **Mitigations**:
   - AGPL license: Keep modifications separate; communicate via API
   - No multi-tenancy: Implement in our layer
   - Single instance limitation: Can run multiple Zammad instances if needed

### Alternative: **Frappe Helpdesk if Python is Mandatory**

If you strongly prefer Python backend throughout:
- Frappe Helpdesk provides Python backend
- Framework has multi-tenancy concepts
- More customization needed but same language stack
- Trade-off: Less polished than Zammad initially

---

## Next Steps

Proceed to **PHASE 3: Recommend the Best Architecture** for detailed architecture recommendation based on this evaluation.
