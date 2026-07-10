# Import Source Compliance Report

**Project:** NeoGiga Enterprise Catalog Import Center  
**Date:** 2026-07-10  
**Phase:** 1 — Import Source Compliance Audit  

---

## Executive Summary

This report assesses the compliance requirements and legal considerations for importing catalog data from various sources including Mouser Electronics API, manufacturer feeds, distributor feeds, and admin-uploaded files. It identifies required safeguards, attribution requirements, and licensing constraints.

---

## 1. Approved Import Sources

### 1.1 Primary Sources (Pre-Approved)

| Source | Type | Authentication | Rate Limits | License Status |
|--------|------|----------------|-------------|----------------|
| Mouser Search API | REST API | API Key | Yes, per documentation | Commercial license required |
| Manufacturer CSV/XML/JSON | File/API | Varies | Varies | Direct agreement required |
| Authorized Distributor Feeds | File/API | Credentials | Varies | Distribution agreement |
| Admin CSV Upload | File | Admin auth | N/A | Internal use |
| Official Datasheets | URL/File | None | robots.txt | Fair use / link only |

### 1.2 Prohibited Sources

| Source | Reason | Risk Level |
|--------|--------|------------|
| Mouser website scraping | Violates ToS, no permission | CRITICAL |
| Unauthorized distributor sites | Copyright violation | HIGH |
| Competitor price scraping | Legal liability | HIGH |
| User-generated content sites | Unreliable, copyright | HIGH |
| Any source without explicit permission | Legal uncertainty | MEDIUM-HIGH |

---

## 2. Mouser API Compliance Assessment

### 2.1 API Terms Review

Based on Mouser API documentation (https://api.mouser.com/api/docs/ui/index):

**Permitted Uses:**
- ✅ Search products by MPN
- ✅ Search products by keyword
- ✅ Retrieve product details for searched items
- ✅ Integrate results into internal systems
- ✅ Cache responses for performance (with limits)

**Restricted Uses:**
- ❌ Bulk extraction of entire catalog
- ❌ Reselling or redistributing raw data
- ❌ Creating competing price comparison service
- ❌ Bypassing rate limits
- ❌ Sharing API credentials with third parties

**Attribution Requirements:**
- Must display "Data provided by Mouser Electronics" where feasible
- Cannot imply Mouser endorsement without written permission
- Must respect trademark usage guidelines

### 2.2 Rate Limit Compliance

```php
class MouserRateLimitService
{
    // Based on typical API limits (verify with actual documentation)
    const REQUESTS_PER_MINUTE = 60;
    const REQUESTS_PER_DAY = 10000;
    
    protected $currentMinuteCount = 0;
    protected $currentDayCount = 0;
    protected $lastResetTime;
    
    public function canMakeRequest(): bool
    {
        $this->resetCountersIfNeeded();
        
        if ($this->currentMinuteCount >= self::REQUESTS_PER_MINUTE) {
            return false; // Wait for minute reset
        }
        
        if ($this->currentDayCount >= self::REQUESTS_PER_DAY) {
            return false; // Wait for day reset
        }
        
        return true;
    }
    
    public function recordRequest(): void
    {
        $this->currentMinuteCount++;
        $this->currentDayCount++;
        $this->persistCounts();
    }
    
    public function getRetryAfterSeconds(): int
    {
        if ($this->currentMinuteCount >= self::REQUESTS_PER_MINUTE) {
            return 60 - (time() % 60);
        }
        
        if ($this->currentDayCount >= self::REQUESTS_PER_DAY) {
            return 86400 - (time() % 86400);
        }
        
        return 0;
    }
}
```

### 2.3 API Key Security

**Requirements:**
- Encrypt API key at rest using Laravel encryption
- Never log full API key
- Never expose in client-side code
- Rotate keys periodically
- Use environment variables, not hardcoded values

```env
# .env configuration
MOUSER_API_KEY=enc:AES256:...encrypted_value...
MOUSER_API_BASE_URL=https://api.mouser.com
MOUSER_API_ENABLED=false  # Default disabled until configured
```

---

## 3. Manufacturer Feed Compliance

### 3.1 Required Documentation

For each manufacturer feed source, maintain:

1. **License Agreement**
   - Written permission to import data
   - Scope of permitted use
   - Redistribution rights (if any)
   - Termination conditions

2. **Data Usage Terms**
   - Allowed purposes (internal, customer-facing, etc.)
   - Geographic restrictions
   - Update frequency permissions
   - Caching permissions

3. **Attribution Requirements**
   - Required copyright notices
   - Logo usage permissions
   - Trademark acknowledgments
   - "Data provided by" statements

### 3.2 Source Metadata Tracking

```sql
CREATE TABLE catalog_source_licenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    source_id BIGINT NOT NULL,
    license_type VARCHAR(100) NOT NULL,
    license_text LONGTEXT NULL,
    effective_date DATE NOT NULL,
    expiration_date DATE NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    attribution_required BOOLEAN DEFAULT TRUE,
    attribution_text VARCHAR(500) NULL,
    redistribution_allowed BOOLEAN DEFAULT FALSE,
    commercial_use_allowed BOOLEAN DEFAULT FALSE,
    geographic_restrictions JSON NULL,
    contact_name VARCHAR(255) NULL,
    contact_email VARCHAR(255) NULL,
    signed_document_path VARCHAR(500) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    INDEX idx_expiration (expiration_date),
    INDEX idx_active (is_active)
);
```

---

## 4. Data Attribution Implementation

### 4.1 Database Schema for Attribution

```sql
-- Add to products table
ALTER TABLE products ADD COLUMN source_attribution VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN license_status ENUM('licensed','fair_use','public_domain','unknown') DEFAULT 'unknown';
ALTER TABLE products ADD COLUMN copyright_notice TEXT NULL;

-- Track source for every imported record
CREATE TABLE manufacturer_source_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    manufacturer_id BIGINT NOT NULL,
    source_id BIGINT NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    source_url VARCHAR(500) NULL,
    raw_payload JSON NULL,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_synced_at TIMESTAMP NULL,
    sync_status ENUM('synced','pending','failed','conflict') DEFAULT 'pending',
    
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id),
    FOREIGN KEY (source_id) REFERENCES catalog_sources(id),
    UNIQUE KEY unique_source_record (source_id, external_id),
    INDEX idx_manufacturer (manufacturer_id)
);
```

### 4.2 Frontend Attribution Display

```blade
{{-- Product detail page attribution --}}
@if($product->source_attribution)
<div class="data-attribution text-sm text-gray-500 mt-4">
    <p>Data provided by: {{ $product->source_attribution }}</p>
    @if($product->license_status === 'licensed')
        <p class="text-xs">© {{ date('Y') }} All rights reserved.</p>
    @endif
</div>
@endif

{{-- Footer attribution for imported catalogs --}}
@if(config('catalog.show_source_attribution'))
<div class="catalog-attribution border-t pt-4 mt-8">
    <p class="text-xs text-gray-400">
        Product data sourced from authorized distributors and manufacturers.
        @foreach($activeSources as $source)
            @if($source->attribution_required)
                <span class="ml-2">{{ $source->attribution_text }}</span>
            @endif
        @endforeach
    </p>
</div>
@endif
```

---

## 5. Content Restrictions

### 5.1 Protected Content Types

| Content Type | Default Rule | Exception |
|--------------|--------------|-----------|
| Product images | Link only | If license permits download |
| Datasheets | Link only | If manufacturer allows distribution |
| Descriptions | Rewrite/summarize | If explicitly licensed |
| Specifications | Can import | Facts not copyrightable |
| Pricing | Do not cache long-term | Real-time API only |
| Availability | Real-time only | Short cache (5 min max) |
| Reviews/ratings | Do not import | If from same source with permission |

### 5.2 Image Handling Policy

```php
class ProductImageImportPolicy
{
    public function shouldDownloadImage(Product $product, string $imageUrl): bool
    {
        // Check source license
        $source = $product->source;
        
        if (!$source->images_download_allowed) {
            return false; // Only store URL
        }
        
        // Check file size limit
        $size = $this->getRemoteFileSize($imageUrl);
        if ($size > config('catalog.max_image_size_kb') * 1024) {
            return false;
        }
        
        // Validate MIME type
        $mimeType = $this->getRemoteMimeType($imageUrl);
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
            return false;
        }
        
        return true;
    }
    
    public function storeImageReference(Product $product, string $url): void
    {
        // Always store URL reference
        $product->images()->create([
            'url' => $url,
            'source_id' => $product->source_id,
            'license_status' => 'url_reference',
            'attribution' => $product->source->attribution_text,
        ]);
    }
}
```

### 5.3 Datasheet Handling Policy

```php
class DatasheetImportPolicy
{
    public function handleDatasheet(Product $product, string $datasheetUrl): void
    {
        // Default: store URL only
        $product->datasheets()->create([
            'title' => 'Product Datasheet',
            'url' => $datasheetUrl,
            'file_path' => null, // No download
            'document_type' => 'datasheet',
            'is_public' => true,
            'source_attribution' => $product->source->name,
            'license_status' => 'link_only',
        ]);
        
        // Only download if explicitly permitted
        if ($product->source->datasheets_download_allowed) {
            $this->queueDatasheetDownload($product, $datasheetUrl);
        }
    }
}
```

---

## 6. Credential Management

### 6.1 Encrypted Credential Storage

```php
use Illuminate\Support\Facades\Crypt;

class CatalogSourceCredential extends Model
{
    protected $fillable = [
        'source_id',
        'credential_type',
        'encrypted_value',
        'key_version',
        'expires_at',
        'last_rotated_at',
    ];
    
    protected $hidden = ['encrypted_value'];
    
    public function setValueAttribute(string $value): void
    {
        $this->attributes['encrypted_value'] = Crypt::encryptString($value);
    }
    
    public function getValueAttribute(): ?string
    {
        if (!$this->encrypted_value) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->encrypted_value);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt credential', [
                'source_id' => $this->source_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    public function logAccess(): void
    {
        // Audit trail for credential usage
        DB::table('credential_access_logs')->insert([
            'credential_id' => $this->id,
            'accessed_at' => now(),
            'accessed_by' => auth()->id() ?? 'system',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### 6.2 Credential Access Logging

```sql
CREATE TABLE credential_access_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    credential_id BIGINT NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accessed_by VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    action VARCHAR(100) NULL,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT NULL,
    
    FOREIGN KEY (credential_id) REFERENCES catalog_source_credentials(id) ON DELETE CASCADE,
    INDEX idx_credential (credential_id),
    INDEX idx_accessed_at (accessed_at)
);
```

---

## 7. Admin Compliance UI

### 7.1 Source Configuration Form

```blade
{{-- Admin: Catalog Source Configuration --}}
<form action="/admin/catalog-import/sources/{{ $source->id }}" method="POST">
    @csrf
    
    <div class="form-section">
        <h3>Basic Information</h3>
        <input name="name" value="{{ $source->name }}" required>
        <select name="source_type">
            <option value="api">API</option>
            <option value="csv">CSV File</option>
            <option value="xml">XML File</option>
            <option value="json">JSON File</option>
            <option value="sftp">SFTP</option>
        </select>
    </div>
    
    <div class="form-section">
        <h3>Compliance Settings</h3>
        <label>
            <input type="checkbox" name="attribution_required" 
                   {{ $source->attribution_required ? 'checked' : '' }}>
            Attribution Required
        </label>
        <input name="attribution_text" value="{{ $source->attribution_text }}"
               placeholder="e.g., 'Data provided by Mouser Electronics'">
        
        <label>
            <input type="checkbox" name="images_download_allowed"
                   {{ $source->images_download_allowed ? 'checked' : '' }}>
            Images May Be Downloaded
        </label>
        
        <label>
            <input type="checkbox" name="datasheets_download_allowed"
                   {{ $source->datasheets_download_allowed ? 'checked' : '' }}>
            Datasheets May Be Downloaded
        </label>
    </div>
    
    <div class="form-section">
        <h3>Rate Limiting</h3>
        <input type="number" name="rate_limit_per_minute" 
               value="{{ $source->rate_limit_per_minute }}">
        <p class="help-text">Requests per minute (0 = unlimited)</p>
    </div>
    
    <div class="form-section">
        <h3>Credentials (Encrypted)</h3>
        <input type="password" name="api_key" autocomplete="off"
               placeholder="Leave blank to keep existing">
        <p class="help-text">Stored encrypted, never displayed</p>
    </div>
</form>
```

---

## 8. Audit Trail Requirements

### 8.1 Import Audit Log

```sql
CREATE TABLE import_audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    import_batch_id BIGINT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT NULL,
    previous_values JSON NULL,
    new_values JSON NULL,
    performed_by BIGINT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    notes TEXT NULL,
    
    FOREIGN KEY (import_batch_id) REFERENCES import_batches(id),
    INDEX idx_batch (import_batch_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_performed_at (performed_at)
);
```

### 8.2 Approval Workflow Audit

```php
class ImportApprovalAudit
{
    public function logApproval(ImportBatch $batch, User $approver): void
    {
        DB::table('import_audit_logs')->insert([
            'import_batch_id' => $batch->id,
            'action' => 'batch_approved',
            'entity_type' => 'import_batch',
            'entity_id' => $batch->id,
            'performed_by' => $approver->id,
            'performed_at' => now(),
            'ip_address' => request()->ip(),
            'notes' => $batch->approval_notes,
        ]);
        
        // Log each approved product
        foreach ($batch->stagedProducts as $product) {
            DB::table('import_audit_logs')->insert([
                'import_batch_id' => $batch->id,
                'action' => 'product_approved',
                'entity_type' => 'product',
                'entity_id' => $product->id,
                'performed_by' => $approver->id,
                'performed_at' => now(),
            ]);
        }
    }
}
```

---

## 9. Compliance Checklist

### 9.1 Pre-Import Verification

Before enabling any import source, verify:

- [ ] Written license agreement on file
- [ ] Attribution requirements documented
- [ ] Rate limits configured
- [ ] Credentials encrypted and stored securely
- [ ] Allowed data types specified
- [ ] Geographic restrictions noted
- [ ] Expiration date tracked
- [ ] Contact information recorded
- [ ] Terms of use accepted
- [ ] Legal review completed (if required)

### 9.2 Ongoing Compliance Monitoring

Monthly review tasks:

- [ ] Verify API usage within rate limits
- [ ] Check for license expirations (30-day warning)
- [ ] Review attribution display accuracy
- [ ] Audit credential access logs
- [ ] Verify no prohibited scraping
- [ ] Check for terms of service updates
- [ ] Review failed import attempts
- [ ] Validate data quality scores

---

## 10. Risk Mitigation

### 10.1 Legal Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Copyright infringement | Medium | Critical | Link-only policy for protected content |
| ToS violation | Medium | High | No scraping, API-only for Mouser |
| Rate limit abuse | Low | Medium | Automated rate limiting service |
| Credential leak | Low | Critical | Encryption, access logging, rotation |
| Data misuse | Low | High | Clear internal policies, training |

### 10.2 Technical Safeguards

```php
// Prevent unauthorized domain fetching
class RemoteUrlValidator
{
    protected $allowedDomains = [
        'api.mouser.com',
        'www.mouser.com',
        // Add other authorized domains
    ];
    
    public function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);
        
        if (!isset($parsed['host'])) {
            return false;
        }
        
        return in_array($parsed['host'], $this->allowedDomains);
    }
    
    public function validateAndFetch(string $url): ?string
    {
        if (!$this->isAllowed($url)) {
            Log::warning('Blocked unauthorized URL fetch', ['url' => $url]);
            return null;
        }
        
        // Additional SSRF protection
        if ($this->isPrivateIp(gethostbyname($parsed['host']))) {
            return null;
        }
        
        return file_get_contents($url);
    }
}
```

---

## 11. Conclusion

Compliance with data source licensing and legal requirements is critical for the Catalog Import Center. Key principles:

1. **Never scrape** – Use official APIs and authorized feeds only
2. **Always attribute** – Display required source attributions
3. **Encrypt credentials** – Protect API keys and passwords
4. **Respect rate limits** – Implement automated throttling
5. **Link, don't copy** – For protected content like images/datasheets
6. **Audit everything** – Maintain complete import history
7. **Review regularly** – Monitor compliance continuously

**Next Steps:**
1. Create `catalog_sources` table with compliance fields
2. Implement credential encryption service
3. Build attribution display components
4. Create license tracking system
5. Establish legal review process for new sources

---

**Document Version:** 1.0  
**Author:** Principal Product Data Architect  
**Legal Review:** Required before production deployment  
**Review Status:** Pending stakeholder review
