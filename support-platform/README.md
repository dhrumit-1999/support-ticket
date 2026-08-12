# Support Platform - Multi-Tenant White-Label Ticketing System

## Overview
A production-ready, self-hosted, multi-tenant customer support platform with complete white-label capabilities.

## Architecture
- **Backend**: Python FastAPI
- **Frontend**: React/Next.js
- **Database**: PostgreSQL with Row Level Security
- **Cache/Queue**: Redis
- **Storage**: S3-compatible object storage
- **Containerization**: Docker

## Directory Structure
```
support-platform/
├── backend/
│   ├── app/
│   │   ├── api/          # API routes
│   │   ├── core/         # Core configuration
│   │   ├── db/           # Database connections
│   │   ├── models/       # SQLAlchemy models
│   │   ├── services/     # Business logic
│   │   └── utils/        # Utilities
│   └── tests/
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── hooks/
│   │   ├── context/
│   │   ├── utils/
│   │   └── styles/
│   └── public/
├── docker/
└── scripts/
```

## Key Features
- True multi-tenancy with database-level isolation
- White-label customer portals per tenant
- Custom domain support
- L1/L2/L3 ticket escalation
- Tenant-specific branding
- Secure attachment handling
- Comprehensive audit logging

## Quick Start
See individual README files in backend/ and frontend/ directories.
