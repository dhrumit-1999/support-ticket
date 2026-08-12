from pydantic import BaseModel, EmailStr, UUID4
from typing import Optional, List
from datetime import datetime
from app.core.enums import UserRole, TicketStatus, TicketPriority, TicketEscalationLevel


# Auth Schemas
class Token(BaseModel):
    access_token: str
    refresh_token: str
    token_type: str = "bearer"


class TokenData(BaseModel):
    user_id: str
    tenant_id: Optional[str] = None
    role: str


class UserCreate(BaseModel):
    email: EmailStr
    password: str
    first_name: str
    last_name: str
    role: UserRole
    tenant_id: Optional[UUID4] = None


class UserLogin(BaseModel):
    email: EmailStr
    password: str


class UserResponse(BaseModel):
    id: UUID4
    email: EmailStr
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    role: UserRole
    is_active: bool
    is_verified: bool
    tenant_id: Optional[UUID4] = None
    created_at: datetime
    
    class Config:
        from_attributes = True


# Tenant Schemas
class TenantCreate(BaseModel):
    name: str
    slug: str
    domain: str


class TenantBrandingCreate(BaseModel):
    company_name: Optional[str] = None
    logo_url: Optional[str] = None
    favicon_url: Optional[str] = None
    primary_color: str = "#007bff"
    secondary_color: str = "#6c757d"
    portal_title: Optional[str] = None
    portal_description: Optional[str] = None
    email_sender_name: Optional[str] = None
    support_email: Optional[EmailStr] = None


class TenantBrandingUpdate(BaseModel):
    company_name: Optional[str] = None
    logo_url: Optional[str] = None
    favicon_url: Optional[str] = None
    primary_color: Optional[str] = None
    secondary_color: Optional[str] = None
    portal_title: Optional[str] = None
    portal_description: Optional[str] = None
    email_sender_name: Optional[str] = None
    support_email: Optional[EmailStr] = None


class TenantBrandingResponse(BaseModel):
    id: UUID4
    tenant_id: UUID4
    company_name: Optional[str] = None
    logo_url: Optional[str] = None
    favicon_url: Optional[str] = None
    primary_color: str
    secondary_color: str
    portal_title: Optional[str] = None
    portal_description: Optional[str] = None
    email_sender_name: Optional[str] = None
    support_email: Optional[str] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True


class TenantResponse(BaseModel):
    id: UUID4
    name: str
    slug: str
    domain: str
    is_active: bool
    created_at: datetime
    branding: Optional[TenantBrandingResponse] = None
    
    class Config:
        from_attributes = True


# Customer Schemas
class CustomerCreate(BaseModel):
    email: EmailStr
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    phone: Optional[str] = None
    company: Optional[str] = None


class CustomerUpdate(BaseModel):
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    phone: Optional[str] = None
    company: Optional[str] = None
    is_active: Optional[bool] = None


class CustomerResponse(BaseModel):
    id: UUID4
    tenant_id: UUID4
    email: EmailStr
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    phone: Optional[str] = None
    company: Optional[str] = None
    is_active: bool
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True


# Ticket Schemas
class TicketCreate(BaseModel):
    subject: str
    description: str
    priority: TicketPriority = TicketPriority.MEDIUM
    category: Optional[str] = None
    tags: List[str] = []


class TicketUpdate(BaseModel):
    subject: Optional[str] = None
    status: Optional[TicketStatus] = None
    priority: Optional[TicketPriority] = None
    escalation_level: Optional[TicketEscalationLevel] = None
    assigned_to_id: Optional[UUID4] = None
    category: Optional[str] = None
    tags: Optional[List[str]] = None


class TicketMessageCreate(BaseModel):
    content: str
    message_type: str  # 'customer_reply', 'agent_reply', 'internal_note'
    is_internal: bool = False


class TicketResponse(BaseModel):
    id: UUID4
    ticket_number: str
    tenant_id: UUID4
    customer_id: Optional[UUID4] = None
    subject: str
    description: str
    status: TicketStatus
    priority: TicketPriority
    escalation_level: TicketEscalationLevel
    assigned_to_id: Optional[UUID4] = None
    created_by_id: Optional[UUID4] = None
    category: Optional[str] = None
    tags: List[str] = []
    sla_due_at: Optional[datetime] = None
    first_response_at: Optional[datetime] = None
    resolved_at: Optional[datetime] = None
    closed_at: Optional[datetime] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True


class TicketMessageResponse(BaseModel):
    id: UUID4
    ticket_id: UUID4
    sender_id: Optional[UUID4] = None
    message_type: str
    content: str
    is_internal: bool
    created_at: datetime
    updated_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True


# Knowledge Base Schemas
class KnowledgeArticleCreate(BaseModel):
    title: str
    content: str
    summary: Optional[str] = None
    category: Optional[str] = None
    tags: List[str] = []
    is_published: bool = False
    is_internal: bool = False


class KnowledgeArticleUpdate(BaseModel):
    title: Optional[str] = None
    content: Optional[str] = None
    summary: Optional[str] = None
    category: Optional[str] = None
    tags: Optional[List[str]] = None
    is_published: Optional[bool] = None
    is_internal: Optional[bool] = None


class KnowledgeArticleResponse(BaseModel):
    id: UUID4
    tenant_id: UUID4
    title: str
    content: str
    summary: Optional[str] = None
    category: Optional[str] = None
    tags: List[str] = []
    is_published: bool
    is_internal: bool
    views_count: int
    helpful_count: int
    not_helpful_count: int
    author_id: Optional[UUID4] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    published_at: Optional[datetime] = None
    
    class Config:
        from_attributes = True


# Audit Log Schema
class AuditLogResponse(BaseModel):
    id: UUID4
    tenant_id: Optional[UUID4] = None
    ticket_id: Optional[UUID4] = None
    user_id: Optional[UUID4] = None
    action: str
    resource_type: Optional[str] = None
    resource_id: Optional[UUID4] = None
    old_values: Optional[dict] = None
    new_values: Optional[dict] = None
    ip_address: Optional[str] = None
    created_at: datetime
    
    class Config:
        from_attributes = True
