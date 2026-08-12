from fastapi import APIRouter, Depends, HTTPException, status, Request
from sqlalchemy.orm import Session
from uuid import UUID, uuid4

from app.db.database import get_db
from app.models import Tenant, TenantBranding, User, AuditLog
from app.api.schemas import (
    TenantCreate, TenantResponse, 
    TenantBrandingCreate, TenantBrandingUpdate, TenantBrandingResponse
)
from app.services.auth import get_current_user, get_tenant_from_request, require_role
from app.core.enums import UserRole, AuditAction


router = APIRouter(prefix="/tenants", tags=["Tenants"])


@router.post("/", response_model=TenantResponse, status_code=status.HTTP_201_CREATED)
async def create_tenant(
    tenant_data: TenantCreate,
    current_user: User = Depends(require_role(UserRole.SUPER_ADMIN)),
    db: Session = Depends(get_db)
):
    """Create a new tenant (Super Admin only)."""
    # Check if slug or domain already exists
    existing = db.query(Tenant).filter(
        (Tenant.slug == tenant_data.slug) | (Tenant.domain == tenant_data.domain)
    ).first()
    if existing:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Slug or domain already exists"
        )
    
    # Create tenant
    tenant = Tenant(
        name=tenant_data.name,
        slug=tenant_data.slug,
        domain=tenant_data.domain
    )
    
    db.add(tenant)
    db.commit()
    db.refresh(tenant)
    
    # Create default branding
    branding = TenantBranding(
        tenant_id=tenant.id,
        company_name=tenant_data.name
    )
    db.add(branding)
    db.commit()
    
    # Audit log
    audit_log = AuditLog(
        tenant_id=tenant.id,
        user_id=current_user.id,
        action=AuditAction.TENANT_CONFIG_UPDATED,
        resource_type="tenant",
        resource_id=tenant.id,
        new_values={"name": tenant.name, "slug": tenant.slug, "domain": tenant.domain}
    )
    db.add(audit_log)
    db.commit()
    
    return tenant


@router.get("/", response_model=list[TenantResponse])
async def list_tenants(
    skip: int = 0,
    limit: int = 100,
    current_user: User = Depends(require_role(UserRole.SUPER_ADMIN)),
    db: Session = Depends(get_db)
):
    """List all tenants (Super Admin only)."""
    tenants = db.query(Tenant).offset(skip).limit(limit).all()
    return tenants


@router.get("/me", response_model=TenantResponse)
async def get_current_tenant(
    request: Request,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Get tenant from current request context."""
    if current_user.role in [UserRole.L2_AGENT, UserRole.L3_AGENT, UserRole.SUPER_ADMIN]:
        # These roles don't belong to a specific tenant
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="User does not belong to a specific tenant"
        )
    
    if not current_user.tenant_id:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tenant not found"
        )
    
    tenant = db.query(Tenant).filter(Tenant.id == current_user.tenant_id).first()
    if not tenant:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tenant not found"
        )
    
    return tenant


@router.get("/{tenant_id}", response_model=TenantResponse)
async def get_tenant(
    tenant_id: UUID,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Get a specific tenant by ID."""
    from app.services.auth import can_access_tenant
    
    tenant = db.query(Tenant).filter(Tenant.id == tenant_id).first()
    if not tenant:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tenant not found"
        )
    
    # Check access
    if not can_access_tenant(current_user, tenant.id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied to this tenant"
        )
    
    return tenant


@router.put("/{tenant_id}", response_model=TenantResponse)
async def update_tenant(
    tenant_id: UUID,
    tenant_data: TenantCreate,
    current_user: User = Depends(require_role(UserRole.SUPER_ADMIN)),
    db: Session = Depends(get_db)
):
    """Update a tenant (Super Admin only)."""
    tenant = db.query(Tenant).filter(Tenant.id == tenant_id).first()
    if not tenant:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tenant not found"
        )
    
    # Update fields
    update_data = tenant_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(tenant, field, value)
    
    db.commit()
    db.refresh(tenant)
    
    # Audit log
    audit_log = AuditLog(
        tenant_id=tenant.id,
        user_id=current_user.id,
        action=AuditAction.TENANT_CONFIG_UPDATED,
        resource_type="tenant",
        resource_id=tenant.id,
        new_values=update_data
    )
    db.add(audit_log)
    db.commit()
    
    return tenant


# Branding endpoints
@router.get("/{tenant_id}/branding", response_model=TenantBrandingResponse)
async def get_tenant_branding(
    tenant_id: UUID,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Get tenant branding configuration."""
    from app.services.auth import can_access_tenant
    
    branding = db.query(TenantBranding).filter(TenantBranding.tenant_id == tenant_id).first()
    if not branding:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Branding configuration not found"
        )
    
    # Check access
    tenant = db.query(Tenant).filter(Tenant.id == tenant_id).first()
    if tenant and not can_access_tenant(current_user, tenant.id):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Access denied"
        )
    
    return branding


@router.put("/{tenant_id}/branding", response_model=TenantBrandingResponse)
async def update_tenant_branding(
    tenant_id: UUID,
    branding_data: TenantBrandingUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """Update tenant branding configuration."""
    from app.services.auth import can_access_tenant
    
    # Check access - only tenant admins or super admins
    if current_user.role not in [UserRole.SUPER_ADMIN, UserRole.TENANT_ADMIN]:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Insufficient permissions"
        )
    
    if current_user.role == UserRole.TENANT_ADMIN and current_user.tenant_id != tenant_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Can only update own tenant branding"
        )
    
    branding = db.query(TenantBranding).filter(TenantBranding.tenant_id == tenant_id).first()
    if not branding:
        # Create new branding if doesn't exist
        branding = TenantBranding(tenant_id=tenant_id)
        db.add(branding)
    
    # Update fields
    update_data = branding_data.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(branding, field, value)
    
    db.commit()
    db.refresh(branding)
    
    # Audit log
    audit_log = AuditLog(
        tenant_id=tenant_id,
        user_id=current_user.id,
        action=AuditAction.BRANDING_UPDATED,
        resource_type="branding",
        resource_id=branding.id,
        new_values=update_data
    )
    db.add(audit_log)
    db.commit()
    
    return branding


@router.get("/by-domain/{domain}", response_model=TenantResponse)
async def get_tenant_by_domain(
    domain: str,
    db: Session = Depends(get_db)
):
    """Get tenant by domain (public endpoint for white-label portal)."""
    tenant = db.query(Tenant).filter(Tenant.domain == domain).first()
    if not tenant or not tenant.is_active:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tenant not found"
        )
    
    return tenant
