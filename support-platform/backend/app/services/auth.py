from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from jose import JWTError, jwt
from datetime import datetime, timedelta
from typing import Optional
from uuid import UUID
from sqlalchemy.orm import Session

from app.db.database import get_db
from app.models import User, Tenant
from app.core.config import settings
from app.core.enums import UserRole


security = HTTPBearer()


class TokenData:
    def __init__(self, user_id: str, tenant_id: Optional[str], role: str):
        self.user_id = user_id
        self.tenant_id = tenant_id
        self.role = role


def create_access_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    """Create a JWT access token."""
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)
    
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, settings.SECRET_KEY, algorithm=settings.ALGORITHM)
    return encoded_jwt


def create_refresh_token(data: dict) -> str:
    """Create a JWT refresh token."""
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(days=settings.REFRESH_TOKEN_EXPIRE_DAYS)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, settings.SECRET_KEY, algorithm=settings.ALGORITHM)
    return encoded_jwt


def decode_token(token: str) -> Optional[TokenData]:
    """Decode and validate a JWT token."""
    try:
        payload = jwt.decode(token, settings.SECRET_KEY, algorithms=[settings.ALGORITHM])
        user_id: str = payload.get("sub")
        tenant_id: Optional[str] = payload.get("tenant_id")
        role: str = payload.get("role")
        
        if user_id is None or role is None:
            return None
        
        return TokenData(user_id=user_id, tenant_id=tenant_id, role=role)
    except JWTError:
        return None


async def get_current_user(
    credentials: HTTPAuthorizationCredentials = Depends(security),
    db: Session = Depends(get_db)
) -> User:
    """Get current authenticated user from token."""
    token = credentials.credentials
    token_data = decode_token(token)
    
    if token_data is None:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid authentication credentials",
            headers={"WWW-Authenticate": "Bearer"},
        )
    
    user = db.query(User).filter(User.id == UUID(token_data.user_id)).first()
    if user is None or not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="User not found or inactive",
            headers={"WWW-Authenticate": "Bearer"},
        )
    
    return user


async def get_tenant_from_request(request: Request, db: Session) -> Optional[Tenant]:
    """Extract tenant from request (domain, header, or user)."""
    # Try to get tenant from host header
    host = request.headers.get("host", "")
    domain = host.split(":")[0]  # Remove port if present
    
    # Check for custom domain
    tenant = db.query(Tenant).filter(Tenant.domain == domain).first()
    if tenant and tenant.is_active:
        return tenant
    
    # Try X-Tenant-ID header
    tenant_id = request.headers.get("X-Tenant-ID")
    if tenant_id:
        try:
            tenant = db.query(Tenant).filter(Tenant.id == UUID(tenant_id)).first()
            if tenant and tenant.is_active:
                return tenant
        except ValueError:
            pass
    
    # Try subdomain
    if "." in domain:
        subdomain = domain.split(".")[0]
        if subdomain != "www" and subdomain != "api":
            tenant = db.query(Tenant).filter(Tenant.slug == subdomain).first()
            if tenant and tenant.is_active:
                return tenant
    
    return None


def require_role(*allowed_roles: UserRole):
    """Dependency to check if user has one of the allowed roles."""
    async def role_checker(
        current_user: User = Depends(get_current_user)
    ) -> User:
        if current_user.role not in allowed_roles:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Insufficient permissions"
            )
        return current_user
    return role_checker


def can_access_tenant(user: User, tenant_id: UUID) -> bool:
    """Check if user can access a specific tenant."""
    # Super admins can access all tenants
    if user.role == UserRole.SUPER_ADMIN:
        return True
    
    # L2 and L3 agents can access all tenants
    if user.role in [UserRole.L2_AGENT, UserRole.L3_AGENT]:
        return True
    
    # Other users can only access their own tenant
    return user.tenant_id == tenant_id


def can_view_ticket(user: User, ticket_tenant_id: UUID, ticket_customer_id: Optional[UUID] = None) -> bool:
    """Check if user can view a specific ticket."""
    # First check tenant access
    if not can_access_tenant(user, ticket_tenant_id):
        return False
    
    # Customers can only view their own tickets
    if user.role == UserRole.CUSTOMER:
        # Note: This requires checking against customer records
        # Implementation depends on how customer-user relationship is structured
        return False  # Additional check needed
    
    # Agents and admins can view tickets in their tenant
    return True


def can_modify_ticket(user: User, ticket_tenant_id: UUID) -> bool:
    """Check if user can modify a ticket."""
    # Super admins can modify all tickets
    if user.role == UserRole.SUPER_ADMIN:
        return True
    
    # L2 and L3 agents can modify all tickets
    if user.role in [UserRole.L2_AGENT, UserRole.L3_AGENT]:
        return True
    
    # Tenant admins and agents can modify tickets in their tenant
    if user.role in [UserRole.TENANT_ADMIN, UserRole.TENANT_AGENT]:
        return user.tenant_id == ticket_tenant_id
    
    # Customers cannot modify tickets (only reply)
    return False
