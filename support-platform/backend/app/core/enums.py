from enum import Enum


class UserRole(str, Enum):
    """User roles in the system."""
    SUPER_ADMIN = "super_admin"
    TENANT_ADMIN = "tenant_admin"
    TENANT_AGENT = "tenant_agent"
    L2_AGENT = "l2_agent"
    L3_AGENT = "l3_agent"
    CUSTOMER = "customer"


class TicketStatus(str, Enum):
    """Ticket status values."""
    OPEN = "open"
    IN_PROGRESS = "in_progress"
    PENDING_CUSTOMER = "pending_customer"
    RESOLVED = "resolved"
    CLOSED = "closed"


class TicketPriority(str, Enum):
    """Ticket priority levels."""
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"
    URGENT = "urgent"


class TicketEscalationLevel(str, Enum):
    """Ticket escalation levels."""
    L1 = "l1"  # Client customer care
    L2 = "l2"  # Internal support
    L3 = "l3"  # Engineering team


class AuditAction(str, Enum):
    """Audit log actions."""
    LOGIN = "login"
    LOGOUT = "logout"
    TICKET_CREATED = "ticket_created"
    TICKET_UPDATED = "ticket_updated"
    TICKET_ASSIGNED = "ticket_assigned"
    TICKET_ESCALATED = "ticket_escalated"
    TICKET_STATUS_CHANGED = "ticket_status_changed"
    CUSTOMER_CREATED = "customer_created"
    CUSTOMER_UPDATED = "customer_updated"
    AGENT_CREATED = "agent_created"
    AGENT_UPDATED = "agent_updated"
    TENANT_CONFIG_UPDATED = "tenant_config_updated"
    BRANDING_UPDATED = "branding_updated"
    ATTACHMENT_UPLOADED = "attachment_uploaded"
    ATTACHMENT_ACCESSED = "attachment_accessed"
    PERMISSION_CHANGED = "permission_changed"
