# PCB Security and File Storage Audit

## Executive Summary

This audit examines security requirements for PCB file handling, storage architecture, access controls, and data protection measures essential for protecting customer intellectual property.

**Audit Date:** 2024-07-11  
**Risk Level:** 🔴 CRITICAL - PCB files contain valuable IP requiring maximum protection  
**Compliance Requirements:** GDPR, Customer NDA, Trade Secret Protection

---

## 1. Threat Model

### 1.1 Assets to Protect

| Asset | Sensitivity | Impact if Compromised |
|-------|-------------|----------------------|
| Gerber files | 🔴 Critical | Manufacturing IP theft, competitor cloning |
| Schematic source files | 🔴 Critical | Complete circuit design exposure |
| PCB layout files (KiCad/Altium) | 🔴 Critical | Design methodology, component placement IP |
| BOM with pricing | 🟡 High | Cost structure exposure, supplier relationships |
| CPL/Pick-and-Place | 🟡 High | Assembly process knowledge |
| Customer part numbers | 🟡 High | Supply chain intelligence |
| Project requirements | 🟠 Medium | Product roadmap leakage |
| Engineering communications | 🟠 Medium | Negotiation position weakness |
| Quality reports | 🟠 Medium | Manufacturing defect patterns |

### 1.2 Threat Actors

| Actor | Motivation | Attack Vectors |
|-------|------------|----------------|
| Competitors | Steal designs, undercut pricing | Insider threats, compromised suppliers |
| Malicious suppliers | Resell designs, leak IP | Authorized access abuse |
| Hackers | Ransomware, data sale | Web vulnerabilities, credential theft |
| Disgruntled employees | Revenge, competitive advantage | Legitimate access misuse |
| Nation-state actors | Industrial espionage | Advanced persistent threats |

### 1.3 Attack Scenarios

1. **Direct URL Guessing** - Attacker guesses private file URLs
2. **Authorization Bypass** - User accesses another organization's files
3. **Supplier Access Creep** - Supplier retains access after project completion
4. **ZIP Bomb** - Malicious archive consumes server resources
5. **Path Traversal** - Attacker escapes upload directory
6. **Malware Upload** - Executable disguised as Gerber file
7. **CSV Injection** - Malicious formulas in BOM files
8. **SSRF via URL** - Fetch internal resources through file parser
9. **Session Hijacking** - Steal authenticated session
10. **Insider Threat** - Employee downloads customer designs

---

## 2. Current Storage Architecture Audit

### 2.1 Existing File Storage

**Current Implementation:**
```
NeoGiga uses Laravel Storage facade with:
- Public disk: /storage/app/public (symlinked to /public/storage)
- Private disk: /storage/app/private
- S3-compatible object storage (configured)
- CDN for public assets
```

**Files Referenced:**
- `config/filesystems.php`
- `app/Services/FileUploadService.php`
- `app/Services/MediaStorageService.php`
- `app/Models/MediaFile.php`

### 2.2 Security Gaps for PCB Files

| Gap | Severity | Current State | Required State |
|-----|----------|---------------|----------------|
| Public symlink | 🔴 Critical | `/public/storage` accessible | No public access for PCB files |
| URL predictability | 🔴 Critical | Sequential IDs | UUIDs + signed URLs |
| Access logging | 🟡 High | Basic media logs | Detailed PCB file access audit |
| Virus scanning | ❌ Missing | None | ClamAV or cloud AV service |
| ZIP bomb protection | ❌ Missing | None | Compression ratio checks |
| MIME validation | 🟡 High | Basic checks | File signature validation |
| Access expiry | ❌ Missing | Permanent links | Time-limited supplier access |
| Encryption at rest | 🟡 High | Optional | Mandatory for PCB files |
| Secure deletion | ❌ Missing | Soft delete only | Cryptographic shredding |
| Download rate limiting | ❌ Missing | None | Prevent bulk exfiltration |

---

## 3. Required Security Architecture

### 3.1 Storage Topology

```
┌─────────────────────────────────────────────────────────────┐
│                    PCB File Storage Architecture            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Public Assets (CDN)          Private PCB Files             │
│  ┌─────────────────┐          ┌─────────────────────────┐   │
│  │ - Landing pages │          │ /storage/pcb-private/   │   │
│  │ - Images        │          │   {org_id}/             │   │
│  │ - CSS/JS        │          │   {project_uuid}/       │   │
│  │ - Datasheets    │          │   {file_uuid}.ext       │   │
│  └─────────────────┘          └─────────────────────────┘   │
│         │                              │                     │
│         │ CDN                          │ No CDN              │
│         │ Public                       │ Signed URLs Only    │
│         ▼                              ▼                     │
│  ┌─────────────────┐          ┌─────────────────────────┐   │
│  │ CloudFront/     │          │ Laravel Storage Gate    │   │
│  │ Cloudflare      │          │ → Temporary Signed URL  │   │
│  └─────────────────┘          │ → Access Control Check  │   │
│                               │ → Audit Log Entry       │   │
│                               └─────────────────────────┘   │
│                                                             │
│  Object Storage Alternative:                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ S3 Bucket: neogiga-pcb-private                      │   │
│  │ - Block all public access                           │   │
│  │ - Server-side encryption (SSE-S3 or SSE-KMS)        │   │
│  │ - Pre-signed URLs with 15-minute expiry             │   │
│  │ - Bucket policy: Deny non-HTTPS                     │   │
│  │ - Versioning enabled                                │   │
│  │ - Lifecycle: Archive after 2 years                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Database Schema for PCB Files

```sql
-- Core file metadata (never contains actual file data)
CREATE TABLE pcb_files (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE NOT NULL DEFAULT gen_random_uuid(),
    pcb_project_id BIGINT NOT NULL REFERENCES pcb_projects(id),
    file_type VARCHAR(50) NOT NULL, -- gerber, schematic, bom, cpl, etc.
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL, -- UUID.ext
    mime_type VARCHAR(100) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    file_hash_sha256 CHAR(64) NOT NULL,
    storage_location VARCHAR(255) NOT NULL, -- path or S3 key
    storage_driver VARCHAR(50) NOT NULL DEFAULT 'private',
    uploaded_by BIGINT NOT NULL REFERENCES users(id),
    organization_id BIGINT NOT NULL REFERENCES organizations(id),
    marketplace_id BIGINT REFERENCES marketplaces(id),
    status VARCHAR(50) NOT NULL DEFAULT 'pending_scan',
    scan_status VARCHAR(50) DEFAULT 'pending',
    scan_result JSONB,
    processing_status VARCHAR(50) DEFAULT 'pending',
    processing_result JSONB,
    version INTEGER NOT NULL DEFAULT 1,
    parent_version_id BIGINT REFERENCES pcb_files(id),
    is_latest_version BOOLEAN DEFAULT true,
    retention_policy_id BIGINT REFERENCES pcb_file_retention_policies(id),
    nda_required BOOLEAN DEFAULT false,
    nda_accepted_at TIMESTAMP WITH TIME ZONE,
    nda_accepted_by BIGINT REFERENCES users(id),
    encryption_key_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE,
    
    INDEX idx_pcb_files_project (pcb_project_id),
    INDEX idx_pcb_files_org (organization_id),
    INDEX idx_pcb_files_type (file_type),
    INDEX idx_pcb_files_status (status),
    INDEX idx_pcb_files_uploaded_by (uploaded_by)
);

-- File versioning history
CREATE TABLE pcb_file_versions (
    id BIGSERIAL PRIMARY KEY,
    pcb_file_id BIGINT NOT NULL REFERENCES pcb_files(id),
    version INTEGER NOT NULL,
    change_summary TEXT,
    changed_by BIGINT NOT NULL REFERENCES users(id),
    changed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(pcb_file_id, version)
);

-- Access control and sharing
CREATE TABLE pcb_file_shares (
    id BIGSERIAL PRIMARY KEY,
    pcb_file_id BIGINT NOT NULL REFERENCES pcb_files(id),
    shared_with_user_id BIGINT REFERENCES users(id),
    shared_with_organization_id BIGINT REFERENCES organizations(id),
    shared_with_supplier_id BIGINT REFERENCES pcb_manufacturers(id),
    share_type VARCHAR(50) NOT NULL, -- user, org, supplier
    permission_level VARCHAR(50) NOT NULL DEFAULT 'view', -- view, download
    granted_by BIGINT NOT NULL REFERENCES users(id),
    granted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    access_count INTEGER DEFAULT 0,
    max_access_count INTEGER,
    last_accessed_at TIMESTAMP WITH TIME ZONE,
    revoked_at TIMESTAMP WITH TIME ZONE,
    revoked_by BIGINT REFERENCES users(id),
    revoke_reason TEXT,
    
    INDEX idx_pcb_file_shares_file (pcb_file_id),
    INDEX idx_pcb_file_shares_expires (expires_at)
);

-- Comprehensive access audit log
CREATE TABLE pcb_file_access_logs (
    id BIGSERIAL PRIMARY KEY,
    pcb_file_id BIGINT NOT NULL REFERENCES pcb_files(id),
    user_id BIGINT REFERENCES users(id),
    action VARCHAR(50) NOT NULL, -- view, download, share, revoke
    ip_address INET,
    user_agent TEXT,
    organization_id BIGINT REFERENCES organizations(id),
    supplier_id BIGINT REFERENCES pcb_manufacturers(id),
    access_granted BOOLEAN NOT NULL,
    denial_reason VARCHAR(255),
    signed_url_token VARCHAR(255),
    accessed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_pcb_file_access_logs_file (pcb_file_id),
    INDEX idx_pcb_file_access_logs_user (user_id),
    INDEX idx_pcb_file_access_logs_time (accessed_at)
);

-- Malware scan results
CREATE TABLE pcb_file_scan_results (
    id BIGSERIAL PRIMARY KEY,
    pcb_file_id BIGINT NOT NULL REFERENCES pcb_files(id),
    scan_engine VARCHAR(100) NOT NULL, -- ClamAV, VirusTotal, etc.
    scan_version VARCHAR(100),
    scan_signature_hash VARCHAR(255),
    scan_status VARCHAR(50) NOT NULL, -- clean, infected, error, pending
    threat_names JSONB, -- Array of detected threats
    scan_duration_ms INTEGER,
    scanned_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    next_scan_due TIMESTAMP WITH TIME ZONE,
    
    INDEX idx_pcb_file_scan_results_file (pcb_file_id),
    INDEX idx_pcb_file_scan_results_status (scan_status)
);

-- Retention policies
CREATE TABLE pcb_file_retention_policies (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    retention_days INTEGER NOT NULL,
    archive_after_days INTEGER,
    auto_delete BOOLEAN DEFAULT false,
    applies_to_file_types JSONB, -- ['gerber', 'schematic', ...]
    applies_to_project_types JSONB, -- ['prototype', 'production']
    is_default BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

---

## 4. Upload Security Pipeline

### 4.1 Multi-Layer Validation Flow

```
┌─────────────────────────────────────────────────────────────┐
│                  PCB File Upload Security Pipeline          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. PRE-UPLOAD VALIDATION                                   │
│     ├─ Authentication check (must be logged in)            │
│     ├─ Authorization check (project access)                │
│     ├─ File size limit (configurable per type)             │
│     ├─ File count quota (per project/org)                  │
│     └─ Rate limiting (max uploads per minute)              │
│                                                             │
│  2. CLIENT-SIDE VALIDATION                                  │
│     ├─ File extension whitelist                            │
│     ├─ File size warning                                   │
│     └─ Filename sanitization                               │
│                                                             │
│  3. SERVER-SIDE VALIDATION (Critical)                       │
│     ├─ Generate UUID filename (never use original)         │
│     ├─ Calculate SHA-256 hash                              │
│     ├─ MIME type detection (file signature, not extension) │
│     ├─ Extension vs MIME mismatch check                    │
│     ├─ Magic number validation                             │
│     └─ Path traversal prevention (strip ../)               │
│                                                             │
│  4. MALWARE SCANNING                                        │
│     ├─ Queue to pcb-file-scan                              │
│     ├─ ClamAV scan                                         │
│     ├─ ZIP bomb detection (compression ratio > 1000:1)     │
│     ├─ Nested ZIP depth limit (max 3 levels)               │
│     ├─ Executable detection in archives                    │
│     └─ Quarantine if infected                              │
│                                                             │
│  5. PROCESSING                                              │
│     ├─ Queue to pcb-file-process                           │
│     ├─ Type-specific parsing                               │
│     ├─ Metadata extraction                                 │
│     ├─ Preview generation (if applicable)                  │
│     └─ Store processing result                             │
│                                                             │
│  6. STORAGE                                                 │
│     ├─ Write to private disk only                          │
│     ├─ Apply encryption at rest                            │
│     ├─ Set restrictive permissions (0600)                  │
│     ├─ Record in pcb_files table                           │
│     └─ Trigger access log entry                            │
│                                                             │
│  7. POST-PROCESS                                            │
│     ├─ Notify uploader                                     │
│     ├─ Update project status                               │
│     ├─ Trigger dependent jobs (DFM, quote)                 │
│     └─ Clean temporary files                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 File Type Validation Rules

```php
// config/pcb-file-types.php

return [
    'gerber' => [
        'extensions' => ['gbr', 'ger', 'gtl', 'gbl', 'gts', 'gbs', 'gto', 'gbo', 'gtp', 'gbp', 'gm1', 'gm2', 'drl', 'txt'],
        'mime_types' => ['application/x-gerber', 'text/plain'],
        'max_size_mb' => 100,
        'magic_numbers' => ['G04', '%FSLAX', '%MOIN', '%MOMM'],
        'scan_required' => true,
        'preview_supported' => true,
    ],
    'excellon_drill' => [
        'extensions' => ['drl', 'txt', 'xln'],
        'mime_types' => ['text/plain'],
        'max_size_mb' => 50,
        'magic_numbers' => ['M48', '%'],
        'scan_required' => true,
        'preview_supported' => true,
    ],
    'schematic' => [
        'extensions' => ['kicad_sch', 'sch', 'asc', 'orcad', 'pdf'],
        'mime_types' => ['text/plain', 'application/pdf'],
        'max_size_mb' => 50,
        'scan_required' => true,
        'preview_supported' => false,
    ],
    'pcb_source' => [
        'extensions' => ['kicad_pcb', 'brd', 'pcb', 'lyt'],
        'mime_types' => ['text/plain', 'application/xml'],
        'max_size_mb' => 100,
        'scan_required' => true,
        'preview_supported' => false,
    ],
    'bom' => [
        'extensions' => ['csv', 'xlsx', 'xls', 'pdf'],
        'mime_types' => ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'],
        'max_size_mb' => 20,
        'scan_required' => true,
        'preview_supported' => true,
        'csv_injection_protection' => true,
    ],
    'cpl' => [
        'extensions' => ['csv', 'txt', 'xlsx'],
        'mime_types' => ['text/csv', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'max_size_mb' => 20,
        'scan_required' => true,
        'preview_supported' => true,
    ],
    'step' => [
        'extensions' => ['step', 'stp'],
        'mime_types' => ['application/step', 'model/step'],
        'max_size_mb' => 200,
        'scan_required' => true,
        'preview_supported' => false,
    ],
    'zip_archive' => [
        'extensions' => ['zip'],
        'mime_types' => ['application/zip', 'application/x-zip-compressed'],
        'max_size_mb' => 500,
        'max_compression_ratio' => 1000, // 1000:1 max
        'max_nested_depth' => 3,
        'scan_required' => true,
        'extract_required' => true,
    ],
];
```

---

## 5. Access Control Implementation

### 5.1 Authorization Middleware

```php
// app/Http/Middleware/AuthorizePcbFileAccess.php

namespace App\Http\Middleware;

use Closure;
use App\Models\PcbFile;
use App\Models\PcbProject;
use Illuminate\Support\Facades\Auth;

class AuthorizePcbFileAccess
{
    public function handle($request, Closure $next)
    {
        $fileId = $request->route('file_id');
        $file = PcbFile::findOrFail($fileId);
        
        $user = Auth::user();
        
        // Check 1: File owner
        if ($file->uploaded_by === $user->id) {
            return $next($request);
        }
        
        // Check 2: Same organization
        if ($file->organization_id === $user->organization_id) {
            return $next($request);
        }
        
        // Check 3: Explicit share with expiry
        $share = $file->shares()
            ->where(function($q) use ($user) {
                $q->where('shared_with_user_id', $user->id)
                  ->orWhere('shared_with_organization_id', $user->organization_id);
            })
            ->where('expires_at', '>', now())
            ->whereNull('revoked_at')
            ->first();
            
        if ($share) {
            // Log access
            $this->logAccess($file, $user, 'authorized_share');
            
            // Update access count
            if ($share->max_access_count) {
                $share->increment('access_count');
                if ($share->access_count >= $share->max_access_count) {
                    $share->update(['revoked_at' => now()]);
                }
            }
            
            return $next($request);
        }
        
        // Check 4: Supplier with active RFQ
        if ($user->hasRole('supplier')) {
            $supplierProfile = $user->supplierProfile;
            if ($file->project->rfqs()
                ->where('supplier_id', $supplierProfile->id)
                ->where('status', 'active')
                ->exists()) {
                return $next($request);
            }
        }
        
        // Check 5: Admin override
        if ($user->hasRole('super_admin') || $user->hasPermission('pcb.admin.view')) {
            $this->logAccess($file, $user, 'admin_override');
            return $next($request);
        }
        
        // DENIED - Log attempt
        $this->logAccess($file, $user, 'denied', 'Unauthorized access attempt');
        
        abort(403, 'You do not have permission to access this file.');
    }
    
    private function logAccess($file, $user, $result, $reason = null)
    {
        \App\Models\PcbFileAccessLog::create([
            'pcb_file_id' => $file->id,
            'user_id' => $user->id,
            'action' => 'access_attempt',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'organization_id' => $user->organization_id,
            'access_granted' => $result !== 'denied',
            'denial_reason' => $reason,
        ]);
    }
}
```

### 5.2 Signed URL Generation

```php
// app/Services/PcbFileService.php

namespace App\Services;

use App\Models\PcbFile;
use App\Models\PcbFileShare;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PcbFileService
{
    public function getDownloadUrl(PcbFile $file, $user, $expiryMinutes = 15)
    {
        // Verify authorization first
        $this->authorizeAccess($file, $user);
        
        // Create temporary share record
        $share = PcbFileShare::create([
            'pcb_file_id' => $file->id,
            'shared_with_user_id' => $user->id,
            'share_type' => 'user',
            'permission_level' => 'download',
            'granted_by' => $user->id,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'max_access_count' => 3, // Limit downloads per URL
        ]);
        
        // Generate signed URL
        $url = Storage::disk('pcb-private')->temporaryUrl(
            $file->stored_filename,
            now()->addMinutes($expiryMinutes),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $file->original_filename . '"',
                'ResponseContentType' => $file->mime_type,
            ]
        );
        
        // Log the access
        PcbFileAccessLog::create([
            'pcb_file_id' => $file->id,
            'user_id' => $user->id,
            'action' => 'download_url_generated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'organization_id' => $user->organization_id,
            'access_granted' => true,
            'signed_url_token' => Str::limit($url, 50),
        ]);
        
        return $url;
    }
    
    public function streamFile(PcbFile $file, $user)
    {
        $this->authorizeAccess($file, $user);
        
        // Log access
        PcbFileAccessLog::create([
            'pcb_file_id' => $file->id,
            'user_id' => $user->id,
            'action' => 'stream',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'organization_id' => $user->organization_id,
            'access_granted' => true,
        ]);
        
        return Storage::disk('pcb-private')->response($file->stored_filename);
    }
}
```

---

## 6. Security Hardening Checklist

### 6.1 Infrastructure Security

- [ ] **Private Storage Only** - No public symlinks for PCB files
- [ ] **Encryption at Rest** - AES-256 encryption on disk/S3
- [ ] **SSL/TLS** - HTTPS required for all file transfers
- [ ] **WAF Rules** - Block common file upload attacks
- [ ] **Rate Limiting** - Max 10 uploads/minute per user
- [ ] **IP Whitelisting** - Optional for high-security projects
- [ ] **Network Isolation** - Private subnet for file storage
- [ ] **Backup Encryption** - Encrypted backups with separate keys

### 6.2 Application Security

- [ ] **UUID Filenames** - Never store with original names
- [ ] **SHA-256 Hashing** - Verify file integrity
- [ ] **MIME Validation** - Check magic numbers, not extensions
- [ ] **Path Sanitization** - Strip directory traversal attempts
- [ ] **Filename Sanitization** - Remove special characters
- [ ] **Size Limits** - Enforce per-type maximums
- [ ] **Quota Management** - Per-user, per-org, per-project limits
- [ ] **Antivirus Scanning** - Real-time ClamAV integration
- [ ] **ZIP Bomb Detection** - Compression ratio checks
- [ ] **CSV Injection Prevention** - Escape formulas in BOM files
- [ ] **SVG Sanitization** - Remove scripts from SVG files
- [ ] **Macro Detection** - Flag macro-enabled Office files

### 6.3 Access Control

- [ ] **Authentication Required** - No anonymous access
- [ ] **Organization Isolation** - Cross-org access blocked
- [ ] **Project-Level Permissions** - Granular project access
- [ ] **Time-Limited Shares** - All shares expire automatically
- [ ] **Access Count Limits** - Cap downloads per share
- [ ] **Revocation Support** - Instant access revocation
- [ ] **NDA Enforcement** - Require NDA before sensitive files
- [ ] **Supplier Expiry** - Auto-revoke after project completion
- [ ] **Audit Logging** - Every access logged with IP/user-agent
- [ ] **Admin Override Logging** - Special logging for admin access

### 6.4 Monitoring & Alerting

- [ ] **Failed Access Alerts** - Notify on repeated denials
- [ ] **Bulk Download Detection** - Alert on unusual download patterns
- [ ] **After-Hours Access** - Flag off-hours file access
- [ ] **Cross-Org Attempts** - Alert on cross-organization access attempts
- [ ] **Malware Detection** - Immediate alert on infected upload
- [ ] **Large File Alerts** - Flag unusually large uploads
- [ ] **Share Expiry Notifications** - Warn before shares expire
- [ ] **Retention Policy Alerts** - Notify before auto-deletion

---

## 7. Compliance & Legal

### 7.1 Data Retention

| File Type | Active Period | Archive Period | Delete After |
|-----------|---------------|----------------|--------------|
| Gerber files | 2 years | 5 years | 7 years |
| Schematic source | 2 years | 5 years | 7 years |
| BOM/CPL | 1 year | 3 years | 5 years |
| Quotes | 6 months | 2 years | 3 years |
| Quality reports | 1 year | 5 years | 7 years |
| Communications | 6 months | 2 years | 3 years |

**Note:** Customer can request extended retention or immediate deletion (GDPR right to erasure).

### 7.2 NDA Requirements

Files marked as confidential require:
1. Explicit NDA acceptance before download
2. NDA stored with timestamp and IP
3. Supplier-specific NDA per project
4. Automatic expiry on project completion
5. Audit trail of all NDA acceptances

### 7.3 GDPR Compliance

- **Right to Access:** Export all PCB project data
- **Right to Erasure:** Secure deletion with cryptographic shredding
- **Data Portability:** Standard format export (Gerber, BOM CSV)
- **Consent Management:** Explicit consent for file processing
- **Data Minimization:** Only collect necessary file metadata

---

## 8. Disaster Recovery

### 8.1 Backup Strategy

```yaml
Backup Schedule:
  - Real-time replication: S3 cross-region replication
  - Hourly snapshots: Last 24 hours
  - Daily snapshots: Last 7 days
  - Weekly snapshots: Last 4 weeks
  - Monthly snapshots: Last 12 months

Recovery Objectives:
  RTO (Recovery Time Objective): 4 hours
  RPO (Recovery Point Objective): 1 hour

Backup Encryption:
  - AES-256 encryption
  - Separate KMS key for backups
  - Key rotation every 90 days
```

### 8.2 Secure Deletion Process

```php
public function secureDelete(PcbFile $file)
{
    DB::transaction(function() use ($file) {
        // 1. Revoke all shares
        $file->shares()->update(['revoked_at' => now()]);
        
        // 2. Overwrite file data (cryptographic shredding)
        $path = storage_path('app/pcb-private/' . $file->stored_filename);
        if (file_exists($path)) {
            $fileSize = filesize($path);
            $handle = fopen($path, 'r+');
            fwrite($handle, str_repeat("\0", $fileSize));
            fflush($handle);
            fclose($handle);
            unlink($path);
        }
        
        // 3. Delete from S3 (if applicable)
        Storage::disk('s3-pcb-private')->delete($file->stored_filename);
        
        // 4. Soft delete database record
        $file->delete();
        
        // 5. Log deletion
        AuditLog::create([
            'action' => 'pcb_file_secure_delete',
            'file_uuid' => $file->uuid,
            'deleted_by' => auth()->id(),
            'deletion_reason' => request('reason'),
        ]);
    });
}
```

---

## 9. Testing Requirements

### 9.1 Security Tests

| Test | Description | Frequency |
|------|-------------|-----------|
| Unauthorized URL access | Attempt direct file access without auth | Each deployment |
| Cross-org access | User A tries to access Org B's files | Each deployment |
| Expired share access | Access file after share expiry | Each deployment |
| Path traversal | Upload `../../../etc/passwd` | Each deployment |
| ZIP bomb | Upload highly compressed archive | Each deployment |
| MIME mismatch | Rename `.exe` to `.ger` | Each deployment |
| Oversized upload | Exceed file size limits | Each deployment |
| SQL injection | Inject SQL in filename | Each deployment |
| XSS in metadata | Inject script in file metadata | Each deployment |
| CSRF upload | Forge upload request | Each deployment |

### 9.2 Penetration Testing Scope

Annual third-party penetration test must cover:
1. File upload bypass techniques
2. Authorization bypass scenarios
3. Storage enumeration attacks
4. Signed URL manipulation
5. Quarantine escape attempts
6. Backup access controls
7. Admin privilege escalation
8. API endpoint security

---

## 10. Implementation Priority

### Phase 1 (Critical - Before Any Upload)
1. ✅ Private storage configuration
2. ✅ UUID filename generation
3. ✅ Authorization middleware
4. ✅ Basic MIME validation
5. ✅ Access logging
6. ✅ Signed URL generation

### Phase 2 (High Priority - Week 1)
1. Antivirus integration
2. ZIP bomb protection
3. File type validation
4. Share expiry system
5. Quota management

### Phase 3 (Medium Priority - Month 1)
1. Encryption at rest
2. NDA workflow
3. Advanced audit reporting
4. Retention policies
5. Secure deletion

### Phase 4 (Ongoing)
1. Performance optimization
2. Monitoring dashboards
3. Automated compliance reports
4. Third-party security audits

---

## Conclusion

PCB file security is **non-negotiable** and must be implemented correctly from day one. The cost of a single IP leak far exceeds the development cost of proper security measures.

**Key Principles:**
1. **Defense in Depth** - Multiple layers of security
2. **Zero Trust** - Verify every access request
3. **Least Privilege** - Grant minimum necessary access
4. **Audit Everything** - Comprehensive logging
5. **Fail Secure** - Deny by default, allow explicitly

**Next Steps:**
Proceed with Phase 1 implementation immediately before accepting any customer PCB files.
