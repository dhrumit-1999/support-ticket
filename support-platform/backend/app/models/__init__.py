from sqlalchemy import Column, Integer, String, Text, DateTime, ForeignKey, Boolean, Enum as SQLEnum, Float, Index, UniqueConstraint
from sqlalchemy.dialects.postgresql import JSONB, UUID, TSVECTOR
from sqlalchemy.sql import func
from sqlalchemy.orm import relationship
from app.db.database import Base
from app.core.enums import UserRole, TicketStatus, TicketPriority, TicketEscalationLevel, AuditAction
import uuid


class Tenant(Base):
    """Tenant model representing a client company."""
    __tablename__ = "tenants"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    name = Column(String(255), nullable=False)
    slug = Column(String(100), unique=True, nullable=False, index=True)  # URL-friendly identifier
    domain = Column(String(255), unique=True, nullable=False, index=True)  # Custom domain
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    users = relationship("User", back_populates="tenant", cascade="all, delete-orphan")
    tickets = relationship("Ticket", back_populates="tenant", cascade="all, delete-orphan")
    customers = relationship("Customer", back_populates="tenant", cascade="all, delete-orphan")
    knowledge_articles = relationship("KnowledgeArticle", back_populates="tenant", cascade="all, delete-orphan")
    branding = relationship("TenantBranding", back_populates="tenant", uselist=False, cascade="all, delete-orphan")
    email_config = relationship("TenantEmailConfig", back_populates="tenant", uselist=False, cascade="all, delete-orphan")
    audit_logs = relationship("AuditLog", back_populates="tenant", cascade="all, delete-orphan")
    
    __table_args__ = (
        Index('idx_tenant_slug', 'slug'),
        Index('idx_tenant_domain', 'domain'),
    )


class TenantBranding(Base):
    """White-label branding configuration for a tenant."""
    __tablename__ = "tenant_branding"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, unique=True)
    company_name = Column(String(255))
    logo_url = Column(String(500))
    favicon_url = Column(String(500))
    primary_color = Column(String(7), default="#007bff")  # Hex color
    secondary_color = Column(String(7), default="#6c757d")
    portal_title = Column(String(255))
    portal_description = Column(Text)
    email_sender_name = Column(String(255))
    support_email = Column(String(255))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    tenant = relationship("Tenant", back_populates="branding")


class TenantEmailConfig(Base):
    """Email configuration for a tenant."""
    __tablename__ = "tenant_email_configs"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, unique=True)
    smtp_host = Column(String(255))
    smtp_port = Column(Integer)
    smtp_username = Column(String(255))
    smtp_password = Column(String(255))  # Should be encrypted
    from_email = Column(String(255))
    from_name = Column(String(255))
    use_tls = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    tenant = relationship("Tenant", back_populates="email_config")


class User(Base):
    """User model for all user types (agents, admins, customers)."""
    __tablename__ = "users"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=True, index=True)
    email = Column(String(255), nullable=False, index=True)
    password_hash = Column(String(255), nullable=False)
    first_name = Column(String(100))
    last_name = Column(String(100))
    role = Column(SQLEnum(UserRole), nullable=False)
    is_active = Column(Boolean, default=True)
    is_verified = Column(Boolean, default=False)
    last_login_at = Column(DateTime(timezone=True))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    tenant = relationship("Tenant", back_populates="users")
    assigned_tickets = relationship("Ticket", foreign_keys="Ticket.assigned_to_id", back_populates="assigned_agent")
    created_tickets = relationship("Ticket", foreign_keys="Ticket.created_by_id", back_populates="created_by")
    messages = relationship("TicketMessage", back_populates="sender", cascade="all, delete-orphan")
    
    __table_args__ = (
        UniqueConstraint('tenant_id', 'email', name='uq_tenant_email'),
        Index('idx_user_tenant_email', 'tenant_id', 'email'),
    )


class Customer(Base):
    """Customer model - end users who create tickets."""
    __tablename__ = "customers"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, index=True)
    email = Column(String(255), nullable=False, index=True)
    first_name = Column(String(100))
    last_name = Column(String(100))
    phone = Column(String(50))
    company = Column(String(255))
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    tenant = relationship("Tenant", back_populates="customers")
    tickets = relationship("Ticket", back_populates="customer", cascade="all, delete-orphan")
    
    __table_args__ = (
        UniqueConstraint('tenant_id', 'email', name='uq_tenant_customer_email'),
        Index('idx_customer_tenant_email', 'tenant_id', 'email'),
    )


class Ticket(Base):
    """Ticket model for support requests."""
    __tablename__ = "tickets"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ticket_number = Column(String(50), unique=True, nullable=False, index=True)  # Human-readable number
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, index=True)
    customer_id = Column(UUID(as_uuid=True), ForeignKey("customers.id", ondelete="SET NULL"), index=True)
    subject = Column(String(500), nullable=False)
    description = Column(Text, nullable=False)
    status = Column(SQLEnum(TicketStatus), default=TicketStatus.OPEN)
    priority = Column(SQLEnum(TicketPriority), default=TicketPriority.MEDIUM)
    escalation_level = Column(SQLEnum(TicketEscalationLevel), default=TicketEscalationLevel.L1)
    assigned_to_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"), index=True)
    created_by_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"))
    category = Column(String(100))
    tags = Column(JSONB, default=list)
    sla_due_at = Column(DateTime(timezone=True))
    first_response_at = Column(DateTime(timezone=True))
    resolved_at = Column(DateTime(timezone=True))
    closed_at = Column(DateTime(timezone=True))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Search vector for full-text search
    search_vector = Column(TSVECTOR)
    
    # Relationships
    tenant = relationship("Tenant", back_populates="tickets")
    customer = relationship("Customer", back_populates="tickets")
    assigned_agent = relationship("User", foreign_keys=[assigned_to_id], back_populates="assigned_tickets")
    created_by = relationship("User", foreign_keys=[created_by_id], back_populates="created_tickets")
    messages = relationship("TicketMessage", back_populates="ticket", cascade="all, delete-orphan", order_by="TicketMessage.created_at")
    attachments = relationship("Attachment", back_populates="ticket", cascade="all, delete-orphan")
    audit_logs = relationship("AuditLog", back_populates="ticket", cascade="all, delete-orphan")
    
    __table_args__ = (
        Index('idx_ticket_tenant_status', 'tenant_id', 'status'),
        Index('idx_ticket_tenant_created', 'tenant_id', 'created_at'),
        Index('idx_ticket_search', 'search_vector', postgresql_using='gin'),
    )


class TicketMessage(Base):
    """Messages within a ticket (replies, internal notes)."""
    __tablename__ = "ticket_messages"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ticket_id = Column(UUID(as_uuid=True), ForeignKey("tickets.id", ondelete="CASCADE"), nullable=False, index=True)
    sender_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"), index=True)
    message_type = Column(String(50), nullable=False)  # 'customer_reply', 'agent_reply', 'internal_note'
    content = Column(Text, nullable=False)
    is_internal = Column(Boolean, default=False)  # Internal notes not visible to customer
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    
    # Relationships
    ticket = relationship("Ticket", back_populates="messages")
    sender = relationship("User", back_populates="messages")
    attachments = relationship("Attachment", back_populates="message", cascade="all, delete-orphan")


class Attachment(Base):
    """File attachments for tickets and messages."""
    __tablename__ = "attachments"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ticket_id = Column(UUID(as_uuid=True), ForeignKey("tickets.id", ondelete="CASCADE"), nullable=False, index=True)
    message_id = Column(UUID(as_uuid=True), ForeignKey("ticket_messages.id", ondelete="SET NULL"), index=True)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, index=True)
    filename = Column(String(255), nullable=False)
    file_path = Column(String(500), nullable=False)  # S3 key or local path
    file_size = Column(Integer)
    mime_type = Column(String(100))
    uploaded_by_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    
    # Relationships
    ticket = relationship("Ticket", back_populates="attachments")
    message = relationship("TicketMessage", back_populates="attachments")
    uploaded_by = relationship("User")


class KnowledgeArticle(Base):
    """Knowledge base articles."""
    __tablename__ = "knowledge_articles"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, index=True)
    title = Column(String(500), nullable=False)
    content = Column(Text, nullable=False)
    summary = Column(Text)
    category = Column(String(100))
    tags = Column(JSONB, default=list)
    is_published = Column(Boolean, default=False)
    is_internal = Column(Boolean, default=False)  # Internal only articles
    views_count = Column(Integer, default=0)
    helpful_count = Column(Integer, default=0)
    not_helpful_count = Column(Integer, default=0)
    author_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"))
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    published_at = Column(DateTime(timezone=True))
    
    # Search vector
    search_vector = Column(TSVECTOR)
    
    # Relationships
    tenant = relationship("Tenant", back_populates="knowledge_articles")
    
    __table_args__ = (
        Index('idx_article_tenant_published', 'tenant_id', 'is_published'),
        Index('idx_article_search', 'search_vector', postgresql_using='gin'),
    )


class AuditLog(Base):
    """Audit log for tracking important actions."""
    __tablename__ = "audit_logs"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=True, index=True)
    ticket_id = Column(UUID(as_uuid=True), ForeignKey("tickets.id", ondelete="SET NULL"), index=True)
    user_id = Column(UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"))
    action = Column(SQLEnum(AuditAction), nullable=False)
    resource_type = Column(String(100))
    resource_id = Column(UUID(as_uuid=True))
    old_values = Column(JSONB)
    new_values = Column(JSONB)
    ip_address = Column(String(45))
    user_agent = Column(String(500))
    created_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)
    
    # Relationships
    tenant = relationship("Tenant", back_populates="audit_logs")
    ticket = relationship("Ticket", back_populates="audit_logs")
    
    __table_args__ = (
        Index('idx_audit_tenant_action', 'tenant_id', 'action'),
        Index('idx_audit_created', 'created_at'),
    )


class SLAPolicy(Base):
    """SLA policies for tenants."""
    __tablename__ = "sla_policies"
    
    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tenant_id = Column(UUID(as_uuid=True), ForeignKey("tenants.id", ondelete="CASCADE"), nullable=False, index=True)
    name = Column(String(255), nullable=False)
    description = Column(Text)
    first_response_hours = Column(Float)
    resolution_hours = Column(Float)
    priority_multiplier = Column(JSONB)  # Different multipliers per priority
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
