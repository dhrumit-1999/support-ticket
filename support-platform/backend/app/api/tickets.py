from fastapi import APIRouter, Depends, HTTPException, status, Request, UploadFile, File
from sqlalchemy.orm import Session
from uuid import UUID
from typing import List, Optional
from datetime import datetime

from app.db.database import get_db
from app.models import Ticket, TicketMessage, Customer, User, Tenant, Attachment, AuditLog
from app.api.schemas import (
    TicketCreate, TicketUpdate, TicketResponse,
    TicketMessageCreate, TicketMessageResponse
)
from app.services.auth import get_current_user, can_access_tenant, can_view_ticket, can_modify_ticket
from app.core.enums import UserRole, TicketStatus, AuditAction


router = APIRouter(prefix="/tickets", tags=["Tickets"])


def generate_ticket_number() -> str:
    """Generate a human-readable ticket number."""
    import random
    timestamp = datetime.now().strftime("%Y%m")
    random_part = "".join([str(random.randint(0, 9)) for _ in range(6)])
    return f"TKT-{timestamp}-{random_part}"


@router.post("/", response_model=TicketResponse, status_code=status.HTTP_201_CREATED)
async def create_ticket(
    ticket_data: TicketCreate,
    request: Request,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Create a new ticket."""
    # Determine tenant
    tenant = None
    
    # For customers, get tenant from user
    if current_user.role == UserRole.CUSTOMER:
        customer = db.query(Customer).filter(Customer.id == current_user.id).first()
        if not customer:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Customer record not found"
            )
        tenant_id = customer.tenant_id
        tenant = db.query(Tenant).filter(Tenant.id == tenant_id).first()
    else:
        # For agents/admins, get from user or request
        tenant_id = current_user.tenant_id
        if tenant_id:
            tenant = db.query(Tenant).filter(Tenant.id == tenant_id).first()
    
    if not tenant:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Tenant context not found"
        )
    
    # Create ticket
    ticket = Ticket(
        ticket_number=generate_ticket_number(),
        tenant_id=tenant.id,
        subject=ticket_data.subject,
        description=ticket_data.description,
        priority=ticket_data.priority,
        category=ticket_data.category,
        tags=ticket_data.tags,
        status=TicketStatus.OPEN,
        created_by_id=current_user.id
    )
    
    # Set customer if creator is a customer
    if current_user.role == UserRole.CUSTOMER:
        customer = db.query(Customer).filter(Customer.id == current_user.id).first()
        if customer:
            ticket.customer_id = customer.id
    
    db.add(ticket)
    db.commit()
    db.refresh(ticket)
    
    # Audit log
    audit_log = AuditLog(
        tenant_id=tenant.id,
        ticket_id=ticket.id,
        user_id=current_user.id,
        action=AuditAction.TICKET_CREATED,
        resource_type="ticket",
        resource_id=ticket.id,
        new_values={"subject": ticket.subject, "priority": ticket.priority.value}
    )
    db.add(audit_log)
    db.commit()
    
    return ticket


@router.get("/", response_model=List[TicketResponse])
async def list_tickets(
    skip: int = 0,
    limit: int = 50,
    status_filter: Optional[TicketStatus] = None,
    priority_filter: Optional[str] = None,
    assigned_to: Optional[UUID] = None,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """List tickets with filtering based on user role."""
    query = db.query(Ticket)
    
    # Apply tenant isolation
    if current_user.role == UserRole.SUPER_ADMIN:
        pass  # Can see all tickets
    elif current_user.role in [UserRole.L2_AGENT, UserRole.L3_AGENT]:
        pass  # Can see all tickets across tenants
    elif current_user.role == UserRole.CUSTOMER:
        # Customers can only see their own tickets
        customer = db.query(Customer).filter(Customer.id == current_user.id).first()
        if not customer:
            return []
        query = query.filter(Ticket.customer_id == customer.id)
    else:
        # Tenant agents/admins can only see their tenant's tickets
        if current_user.tenant_id:
            query = query.filter(Ticket.tenant_id == current_user.tenant_id)
        else:
            return []
    
    # Apply filters
    if status_filter:
        query = query.filter(Ticket.status == status_filter)
    
    if priority_filter:
        query = query.filter(Ticket.priority == priority_filter)
    
    if assigned_to:
        query = query.filter(Ticket.assigned_to_id == assigned_to)
    
    tickets = query.order_by(Ticket.created_at.desc()).offset(skip).limit(limit).all()
    return tickets


@router.get("/{ticket_id}", response_model=TicketResponse)
async def get_ticket(
    ticket_id: UUID,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Get a specific ticket."""
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    # Check access
    if not can_view_ticket(current_user, ticket.tenant_id, ticket.customer_id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied to this ticket"
        )
    
    # Special check for customers
    if current_user.role == UserRole.CUSTOMER:
        customer = db.query(Customer).filter(Customer.id == current_user.id).first()
        if not customer or ticket.customer_id != customer.id:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Access denied to this ticket"
            )
    
    return ticket


@router.put("/{ticket_id}", response_model=TicketResponse)
async def update_ticket(
    ticket_id: UUID,
    ticket_data: TicketUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Update a ticket."""
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    # Check modify permission
    if not can_modify_ticket(current_user, ticket.tenant_id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Cannot modify this ticket"
        )
    
    # Track changes for audit
    old_values = {}
    new_values = {}
    
    update_data = ticket_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        if value is not None:
            old_value = getattr(ticket, field)
            old_values[field] = str(old_value) if old_value else None
            new_values[field] = str(value) if value else None
            setattr(ticket, field, value)
    
    # Update timestamps for specific status changes
    if ticket_data.status:
        if ticket_data.status == TicketStatus.RESOLVED and ticket.status != TicketStatus.RESOLVED:
            ticket.resolved_at = datetime.utcnow()
        elif ticket_data.status == TicketStatus.CLOSED and ticket.status != TicketStatus.CLOSED:
            ticket.closed_at = datetime.utcnow()
        
        # Track first response
        if ticket.first_response_at is None and ticket_data.status == TicketStatus.IN_PROGRESS:
            ticket.first_response_at = datetime.utcnow()
    
    db.commit()
    db.refresh(ticket)
    
    # Audit log
    if old_values:
        audit_log = AuditLog(
            tenant_id=ticket.tenant_id,
            ticket_id=ticket.id,
            user_id=current_user.id,
            action=AuditAction.TICKET_UPDATED,
            resource_type="ticket",
            resource_id=ticket.id,
            old_values=old_values,
            new_values=new_values
        )
        db.add(audit_log)
        db.commit()
    
    return ticket


@router.post("/{ticket_id}/messages", response_model=TicketMessageResponse)
async def add_message(
    ticket_id: UUID,
    message_data: TicketMessageCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Add a message/reply to a ticket."""
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    # Check access
    if not can_view_ticket(current_user, ticket.tenant_id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied to this ticket"
        )
    
    # Create message
    message = TicketMessage(
        ticket_id=ticket.id,
        sender_id=current_user.id,
        message_type=message_data.message_type,
        content=message_data.content,
        is_internal=message_data.is_internal
    )
    
    db.add(message)
    
    # Update ticket status if needed
    if message_data.message_type == "customer_reply" and ticket.status == TicketStatus.PENDING_CUSTOMER:
        ticket.status = TicketStatus.OPEN
    elif message_data.message_type == "agent_reply" and ticket.status == TicketStatus.OPEN:
        ticket.status = TicketStatus.IN_PROGRESS
    
    db.commit()
    db.refresh(message)
    
    return message


@router.post("/{ticket_id}/escalate")
async def escalate_ticket(
    ticket_id: UUID,
    target_level: str,  # 'l2' or 'l3'
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Escalate a ticket to L2 or L3 support."""
    from app.core.enums import TicketEscalationLevel
    
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    # Check escalation permission
    if not can_modify_ticket(current_user, ticket.tenant_id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Cannot escalate this ticket"
        )
    
    # Map string to enum
    level_map = {
        "l2": TicketEscalationLevel.L2,
        "l3": TicketEscalationLevel.L3
    }
    
    if target_level not in level_map:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Invalid escalation level"
        )
    
    old_level = ticket.escalation_level
    ticket.escalation_level = level_map[target_level]
    
    # Auto-assign to L2/L3 team if needed
    if target_level == "l2":
        # Find an available L2 agent
        l2_agent = db.query(User).filter(
            User.role == UserRole.L2_AGENT,
            User.is_active == True
        ).first()
        if l2_agent:
            ticket.assigned_to_id = l2_agent.id
    
    db.commit()
    
    # Audit log
    audit_log = AuditLog(
        tenant_id=ticket.tenant_id,
        ticket_id=ticket.id,
        user_id=current_user.id,
        action=AuditAction.TICKET_ESCALATED,
        resource_type="ticket",
        resource_id=ticket.id,
        old_values={"escalation_level": old_level.value},
        new_values={"escalation_level": ticket.escalation_level.value}
    )
    db.add(audit_log)
    db.commit()
    
    return {"message": f"Ticket escalated to {target_level.upper()}"}


@router.delete("/{ticket_id}")
async def delete_ticket(
    ticket_id: UUID,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Delete a ticket."""
    ticket = db.query(Ticket).filter(Ticket.id == ticket_id).first()
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found"
        )
    
    # Only super admins or tenant admins can delete
    if current_user.role not in [UserRole.SUPER_ADMIN, UserRole.TENANT_ADMIN]:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Insufficient permissions to delete tickets"
        )
    
    if current_user.role == UserRole.TENANT_ADMIN and ticket.tenant_id != current_user.tenant_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Can only delete tickets from own tenant"
        )
    
    db.delete(ticket)
    db.commit()
    
    return {"message": "Ticket deleted successfully"}
