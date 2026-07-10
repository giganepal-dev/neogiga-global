# NeoGiga Phase 3 Implementation: Identity & Security Foundation

## Executive Summary

**Status:** Ready to implement  
**Priority:** P0 Critical  
**Estimated Duration:** 2 weeks  
**Risk Level:** High (security foundation)

## Current State Assessment

### ✅ Existing Foundation

1. **Laravel 11.x** - Modern framework version
2. **User Model** - Basic authentication structure exists
3. **Role System** - Roles table and basic permission checking
4. **API Token Field** - `api_token_hash` field exists in users table
5. **Custom Middleware** - `api.token` middleware implemented
6. **Seller/Distributor Auth** - Separate auth controllers exist
7. **Audit Logs** - `audit_logs` table exists
8. **Policies** - 6 basic policies implemented (seller-focused)

### ❌ Critical Gaps (P0)

1. **No Laravel Sanctum** - Using custom token authentication instead of industry-standard Sanctum
2. **No Two-Factor Authentication** - No TOTP/2FA implementation for admin/seller accounts
3. **Incomplete Policy Coverage** - Only 6 policies; missing policies for Product, Order, Inventory, etc.
4. **No Tenant Isolation** - No global scopes enforcing organization/country isolation
5. **No Encryption for Sensitive Fields** - Bank details, tax IDs stored in plain text
6. **No Rate Limiting Configuration** - Default Laravel rate limiting only
7. **No Session/Device Management** - Cannot view or revoke active sessions
8. **No Login History** - No tracking of login attempts, locations, devices
9. **No Account Suspension Workflow** - No soft-delete or suspension mechanism
10. **No Secure File Upload Validation** - Missing MIME validation, virus scanning interface

## Implementation Plan

### Week 1: Authentication & Authorization

#### Day 1-2: Laravel Sanctum Integration
- [ ] Install Laravel Sanctum via Composer
- [ ] Publish Sanctum configuration
- [ ] Run Sanctum migrations
- [ ] Update User model to use HasApiTokens trait
- [ ] Create Sanctum service provider configuration
- [ ] Migrate existing api_token users to Sanctum tokens
- [ ] Update API routes to use Sanctum middleware
- [ ] Create token management endpoints (create, list, revoke)
- [ ] Add token abilities/permissions system
- [ ] Write tests for token authentication

#### Day 3-4: Two-Factor Authentication
- [ ] Install bacon/bacon-qr-code for QR generation
- [ ] Install pragmarx/google2fa-laravel for TOTP
- [ ] Create 2FA migration (secret, enabled_at, recovery_codes)
- [ ] Create TwoFactorAuthentication trait
- [ ] Create 2FA service (generateSecret, enable, disable, verify)
- [ ] Create 2FA controller endpoints
- [ ] Implement recovery code generation and validation
- [ ] Add 2FA to admin/seller login flows
- [ ] Create 2FA settings UI components
- [ ] Write comprehensive 2FA tests

#### Day 5: Resource Policies Expansion
- [ ] Audit all models requiring policies
- [ ] Create ProductPolicy with CRUD permissions
- [ ] Create OrderPolicy with status-based permissions
- [ ] Create InventoryPolicy with warehouse scoping
- [ ] Create VendorPolicy with approval workflow
- [ ] Create WarehousePolicy with regional scoping
- [ ] Create CountryPolicy with localization permissions
- [ ] Create OrganizationPolicy with tenant isolation
- [ ] Register all policies in AuthServiceProvider
- [ ] Add policy gates to existing controllers
- [ ] Write policy authorization tests

### Week 2: Security Hardening

#### Day 6-7: Tenant Isolation & Global Scopes
- [ ] Create BelongsToOrganization trait
- [ ] Create OrganizationScope global scope
- [ ] Apply scope to Organization-related models
- [ ] Create CountryScope for country-specific data
- [ ] Apply scope to marketplace models
- [ ] Create WarehouseScope for inventory isolation
- [ ] Test tenant isolation thoroughly
- [ ] Add tenant violation logging
- [ ] Write isolation bypass prevention tests

#### Day 8: Encryption & Sensitive Data
- [ ] Create Encryptable trait for model attributes
- [ ] Identify sensitive fields (bank_account, tax_id, national_id)
- [ ] Create migration to encrypt existing sensitive data
- [ ] Apply Encryptable trait to User, Vendor, Distributor models
- [ ] Create secure field accessors/mutators
- [ ] Implement field-level encryption keys
- [ ] Add encryption key rotation mechanism
- [ ] Write encryption/decryption tests

#### Day 9: Session & Device Management
- [ ] Create device_fingerprints table migration
- [ ] Create session_devices table migration
- [ ] Create DeviceSession model
- [ ] Create DeviceFingerprintService
- [ ] Track device on login (browser, OS, IP, location)
- [ ] Create session management endpoints (list, revoke)
- [ ] Add concurrent session limits by role
- [ ] Implement "remember me" secure handling
- [ ] Write session management tests

#### Day 10: Login History & Security Monitoring
- [ ] Create login_history table migration
- [ ] Create LoginHistory model
- [ ] Create LoginListener for login events
- [ ] Track successful/failed login attempts
- [ ] Implement suspicious activity detection
- [ ] Create security alert notifications
- [ ] Add login history API endpoint
- [ ] Implement account lockout after failed attempts
- [ ] Write security monitoring tests

## Database Changes

### New Tables

1. **personal_access_tokens** (Sanctum)
   - tokenable_type, tokenable_id
   - name, token (hashed)
   - abilities, last_used_at, expires_at

2. **two_factor_authentications**
   - user_id (FK)
   - secret (encrypted)
   - enabled_at
   - recovery_codes (encrypted JSON)
   - created_at, updated_at

3. **device_sessions**
   - user_id (FK)
   - device_fingerprint
   - ip_address, user_agent
   - browser, os, device_type
   - location_data (JSON)
   - is_active, last_activity_at
   - created_at, revoked_at

4. **login_history**
   - user_id (FK)
   - ip_address, user_agent
   - login_status (success/failed)
   - failure_reason
   - location_data (JSON)
   - created_at

### Modified Tables

1. **users**
   - Add: two_factor_enabled (boolean)
   - Add: two_factor_secret (encrypted)
   - Add: two_factor_recovery_codes (encrypted)
   - Add: account_status (active/suspended/deleted)
   - Add: suspended_at, suspension_reason
   - Add: deleted_at (soft delete)

2. **vendors**
   - Add: bank_account_number (encrypted)
   - Add: tax_identification_number (encrypted)

3. **distributors**
   - Add: bank_account_number (encrypted)
   - Add: tax_identification_number (encrypted)

## API Endpoints

### Authentication
```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/register
GET    /api/v1/auth/me
POST   /api/v1/auth/refresh
POST   /api/v1/auth/2fa/enable
POST   /api/v1/auth/2fa/verify
POST   /api/v1/auth/2fa/disable
POST   /api/v1/auth/2fa/recovery
```

### Token Management
```
GET    /api/v1/tokens              List all tokens
POST   /api/v1/tokens              Create new token
DELETE /api/v1/tokens/{id}         Revoke token
DELETE /api/v1/tokens              Revoke all tokens
```

### Session Management
```
GET    /api/v1/sessions            List active sessions
DELETE /api/v1/sessions/{id}       Revoke session
DELETE /api/v1/sessions            Revoke other sessions
```

### Security
```
GET    /api/v1/security/login-history
GET    /api/v1/security/devices
POST   /api/v1/security/lock-account
```

## File Structure

```
app/
├── Models/
│   ├── Traits/
│   │   ├── HasTwoFactorAuthentication.php
│   │   ├── Encryptable.php
│   │   └── BelongsToOrganization.php
│   ├── DeviceSession.php
│   ├── LoginHistory.php
│   └── TwoFactorAuthentication.php
├── Policies/
│   ├── ProductPolicy.php
│   ├── OrderPolicy.php
│   ├── InventoryPolicy.php
│   ├── VendorPolicy.php
│   ├── WarehousePolicy.php
│   ├── CountryPolicy.php
│   └── OrganizationPolicy.php
├── Services/
│   ├── Auth/
│   │   ├── TwoFactorService.php
│   │   ├── TokenService.php
│   │   └── SessionService.php
│   └── Security/
│   │   ├── DeviceFingerprintService.php
│   │   ├── LoginHistoryService.php
│   │   └── EncryptionService.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/Auth/
│   │   │   ├── TwoFactorController.php
│   │   │   └── SessionController.php
│   │   └── Api/Security/
│   │       └── SecurityController.php
│   └── Middleware/
│       ├── Ensure2FAEnabled.php
│       ├── CheckAccountStatus.php
│       └── ApplyTenantScope.php
└── Scopes/
    ├── OrganizationScope.php
    ├── CountryScope.php
    └── WarehouseScope.php
```

## Testing Requirements

### Unit Tests
- [ ] TwoFactorServiceTest
- [ ] TokenServiceTest
- [ ] SessionServiceTest
- [ ] EncryptionServiceTest
- [ ] DeviceFingerprintServiceTest

### Feature Tests
- [ ] AuthenticationFlowTest
- [ ] TwoFactorAuthenticationTest
- [ ] TokenManagementTest
- [ ] SessionManagementTest
- [ ] TenantIsolationTest
- [ ] PolicyAuthorizationTest

### Security Tests
- [ ] BruteForceProtectionTest
- [ ] SQLInjectionPreventionTest
- [ ] XSSPreventionTest
- [ ] CSRFProtectionTest
- [ ] MassAssignmentProtectionTest

## Risk Mitigation

1. **Backward Compatibility**
   - Maintain existing api_token field during migration
   - Support both old and new authentication temporarily
   - Provide migration script for existing tokens

2. **Data Migration**
   - Create reversible migration for encryption
   - Test encryption on staging first
   - Keep backup of unencrypted data temporarily

3. **Performance**
   - Index all new foreign keys
   - Cache frequently accessed permissions
   - Use database-level encryption where possible

4. **Rollback Plan**
   - Document rollback procedures
   - Keep old authentication working during transition
   - Test rollback on staging environment

## Success Criteria

1. ✅ All API endpoints protected by Sanctum authentication
2. ✅ Admin and seller accounts can enable 2FA
3. ✅ All resources have appropriate policies
4. ✅ Tenant isolation enforced at database query level
5. ✅ Sensitive fields encrypted at rest
6. ✅ Users can view and manage active sessions
7. ✅ Login history tracked for all users
8. ✅ Suspicious activity triggers alerts
9. ✅ All new features have passing tests
10. ✅ Documentation updated

## Dependencies

- PHP 8.2+
- Laravel 11.x
- bacon/bacon-qr-code ^2.0
- pragmarx/google2fa-laravel ^2.0
- Laravel Sanctum (built-in)

## Next Steps After Completion

1. Phase 4: Multi-Country Platform
2. Phase 5: Product Information Management Enhancement
3. Phase 6: Marketplace Offers Architecture
4. Continue with remaining phases per roadmap

---

**Document Version:** 1.0  
**Last Updated:** 2026-07-10  
**Author:** NeoGiga Architecture Team  
**Status:** Approved for Implementation
