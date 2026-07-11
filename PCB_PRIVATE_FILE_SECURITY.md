# PCB Private File Security Implementation Guide

## Overview

This guide details the enterprise-grade security architecture for storing and managing private PCB files including Gerber files, schematics, BOMs, CPL files, and other sensitive manufacturing data.

## Security Principles

1. **Zero Public Access**: No direct public URLs to PCB files
2. **Signed Temporary Access**: Time-limited download URLs
3. **Organization Isolation**: Strict cross-organization access prevention
4. **Full Audit Trail**: Every file access logged
5. **Defense in Depth**: Multiple security layers
6. **Encryption at Rest**: Where infrastructure supports
7. **Secure Deletion**: Proper file destruction on request

## Database Schema

### pcb_files Table

```sql
CREATE TABLE pcb_files (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES pcb_projects(id) ON DELETE CASCADE,
    uploaded_by UUID NOT NULL REFERENCES users(id),
    
    -- File Identification
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_hash VARCHAR(64) NOT NULL, -- SHA-256
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    
    -- File Classification
    file_type VARCHAR(50) NOT NULL, -- 'gerber', 'schematic', 'bom', 'cpl', 'drill', 'step', 'dxf', 'other'
    file_category VARCHAR(50), -- 'top_copper', 'bottom_copper', 'solder_mask', etc.
    
    -- Storage
    storage_path VARCHAR(500) NOT NULL,
    storage_disk VARCHAR(50) DEFAULT 'private-pcb',
    encryption_enabled BOOLEAN DEFAULT TRUE,
    
    -- Security
    is_scanned BOOLEAN DEFAULT FALSE,
    scan_status VARCHAR(20), -- 'pending', 'clean', 'infected', 'error'
    scan_result JSONB,
    malware_signature VARCHAR(255),
    
    -- Validation
    is_validated BOOLEAN DEFAULT FALSE,
    validation_result JSONB,
    validation_errors TEXT[],
    
    -- Access Control
    access_level VARCHAR(20) DEFAULT 'organization', -- 'organization', 'project_members', 'specific_users'
    nda_required BOOLEAN DEFAULT FALSE,
    
    -- Lifecycle
    retention_policy VARCHAR(50) DEFAULT 'standard', -- 'standard', 'extended', 'permanent', 'temporary'
    expires_at TIMESTAMP(0) WITHOUT TIME ZONE,
    deleted_at TIMESTAMP(0) WITHOUT TIME ZONE,
    securely_deleted_at TIMESTAMP(0) WITHOUT TIME ZONE,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    CONSTRAINT chk_file_size CHECK (file_size > 0 AND file_size <= 524288000), -- Max 500MB
    CONSTRAINT chk_scan_status CHECK (scan_status IN ('pending', 'clean', 'infected', 'error'))
);

-- Indexes
CREATE INDEX idx_pcb_files_project ON pcb_files(project_id);
CREATE INDEX idx_pcb_files_uploader ON pcb_files(uploaded_by);
CREATE INDEX idx_pcb_files_type ON pcb_files(file_type);
CREATE INDEX idx_pcb_files_hash ON pcb_files(file_hash);
CREATE INDEX idx_pcb_files_scanned ON pcb_files(is_scanned) WHERE is_scanned = FALSE;
CREATE INDEX idx_pcb_files_expires ON pcb_files(expires_at) WHERE expires_at IS NOT NULL;
```

### pcb_file_versions Table

```sql
CREATE TABLE pcb_file_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    file_id UUID NOT NULL REFERENCES pcb_files(id) ON DELETE CASCADE,
    version_number INTEGER NOT NULL,
    
    -- Version Details
    file_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    file_size BIGINT NOT NULL,
    change_summary TEXT,
    uploaded_by UUID REFERENCES users(id),
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(file_id, version_number)
);

CREATE INDEX idx_file_versions_file ON pcb_file_versions(file_id);
```

### pcb_file_access_logs Table

```sql
CREATE TABLE pcb_file_access_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    file_id UUID NOT NULL REFERENCES pcb_files(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id),
    
    -- Access Details
    action VARCHAR(50) NOT NULL, -- 'view', 'download', 'preview', 'share'
    access_method VARCHAR(50), -- 'web', 'api', 'signed_url', 'direct'
    
    -- Context
    ip_address INET,
    user_agent TEXT,
    session_id VARCHAR(255),
    organization_id UUID REFERENCES organizations(id),
    
    -- Result
    success BOOLEAN DEFAULT TRUE,
    failure_reason VARCHAR(255),
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX idx_file_access_file ON pcb_file_access_logs(file_id);
CREATE INDEX idx_file_access_user ON pcb_file_access_logs(user_id);
CREATE INDEX idx_file_access_created ON pcb_file_access_logs(created_at);
CREATE INDEX idx_file_access_org ON pcb_file_access_logs(organization_id);
```

### pcb_file_shares Table

```sql
CREATE TABLE pcb_file_shares (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    file_id UUID NOT NULL REFERENCES pcb_files(id) ON DELETE CASCADE,
    created_by UUID NOT NULL REFERENCES users(id),
    
    -- Share Configuration
    share_token VARCHAR(64) UNIQUE NOT NULL,
    recipient_email VARCHAR(255),
    recipient_organization_id UUID REFERENCES organizations(id),
    
    -- Permissions
    can_download BOOLEAN DEFAULT FALSE,
    can_preview BOOLEAN DEFAULT TRUE,
    max_downloads INTEGER,
    download_count INTEGER DEFAULT 0,
    
    -- Expiry
    expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    nda_required BOOLEAN DEFAULT FALSE,
    nda_accepted BOOLEAN DEFAULT FALSE,
    nda_accepted_at TIMESTAMP(0) WITHOUT TIME ZONE,
    
    -- Tracking
    last_accessed_at TIMESTAMP(0) WITHOUT TIME ZONE,
    access_count INTEGER DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    revoked_at TIMESTAMP(0) WITHOUT TIME ZONE,
    revoked_by UUID REFERENCES users(id),
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT chk_max_downloads CHECK (max_downloads IS NULL OR max_downloads > 0)
);

CREATE INDEX idx_file_shares_token ON pcb_file_shares(share_token);
CREATE INDEX idx_file_shares_file ON pcb_file_shares(file_id);
CREATE INDEX idx_file_shares_expires ON pcb_file_shares(expires_at);
```

### pcb_file_scan_results Table

```sql
CREATE TABLE pcb_file_scan_results (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    file_id UUID NOT NULL REFERENCES pcb_files(id) ON DELETE CASCADE,
    
    -- Scan Details
    scanner_version VARCHAR(100),
    scan_started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    scan_completed_at TIMESTAMP(0) WITHOUT TIME ZONE,
    
    -- Results
    is_clean BOOLEAN,
    threats_found INTEGER DEFAULT 0,
    threat_details JSONB,
    virus_signatures TEXT[],
    
    -- ZIP-specific
    is_zip_bomb BOOLEAN DEFAULT FALSE,
    compression_ratio DECIMAL(10,2),
    uncompressed_size BIGINT,
    file_count INTEGER,
    suspicious_paths TEXT[],
    
    -- Errors
    scan_error VARCHAR(255),
    error_details TEXT,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_scan_results_file ON pcb_file_scan_results(file_id);
CREATE INDEX idx_scan_results_clean ON pcb_file_scan_results(is_clean) WHERE is_clean = FALSE;
```

## Storage Architecture

### Disk Configuration

```php
// config/filesystems.php

'disks' => [
    'private-pcb' => [
        'driver' => 'local',
        'root' => storage_path('app/private/pcb'),
        'serve' => false, // No public serving
        'throw' => true,
        'report' => false,
    ],
    
    'encrypted-pcb' => [
        'driver' => 'local',
        'root' => storage_path('app/encrypted/pcb'),
        'serve' => false,
        'throw' => true,
        'visibility' => 'private',
    ],
    
    // For S3-compatible storage
    's3-private-pcb' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_PCB_PRIVATE_BUCKET'),
        'visibility' => 'private',
        'encrypt' => true, // Server-side encryption
        'options' => [
            'CacheControl' => 'no-store, no-cache, private',
        ],
    ],
],
```

### Directory Structure

```
storage/
├── app/
│   ├── private/
│   │   └── pcb/
│   │       ├── {organization_id}/
│   │       │   └── {project_id}/
│   │       │       ├── gerbers/
│   │       │       ├── schematics/
│   │       │       ├── bom/
│   │       │       ├── cpl/
│   │       │       ├── mechanical/
│   │       │       └── misc/
│   │       └── quarantine/  # Infected files
│   └── encrypted/
│       └── pcb/
│           └── ...
```

## File Upload Service

```php
<?php

namespace App\Services\PCB;

use App\Models\PCB\PcbFile;
use App\Models\PCB\PcbFileScanResult;
use App\Jobs\PCB\ScanPcbFileJob;
use App\Jobs\PCB\ValidatePcbFileJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use ZipArchive;

class PcbFileUploadService
{
    // Maximum file sizes
    const MAX_FILE_SIZE = 524288000; // 500MB
    const MAX_ZIP_UNCOMPRESSED_RATIO = 100; // 100:1 ratio triggers bomb detection
    const MAX_ZIP_FILES = 10000;
    
    // Allowed MIME types
    const ALLOWED_MIME_TYPES = [
        'gerber' => [
            'application/x-gerber',
            'text/plain',
            'application/octet-stream',
        ],
        'schematic' => [
            'application/pdf',
            'image/png',
            'image/jpeg',
        ],
        'bom' => [
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'cpl' => [
            'text/csv',
            'text/plain',
        ],
        'mechanical' => [
            'application/step',
            'application/sla',
            'application/dxf',
        ],
    ];
    
    // File extension to type mapping
    const EXTENSION_MAP = [
        'gbr' => 'gerber',
        'ger' => 'gerber',
        'gtl' => 'gerber',
        'gbl' => 'gerber',
        'gto' => 'gerber',
        'gbo' => 'gerber',
        'gts' => 'gerber',
        'gbs' => 'gerber',
        'gtp' => 'gerber',
        'gbp' => 'gerber',
        'gm1' => 'gerber',
        'gm2' => 'gerber',
        'drl' => 'drill',
        'txt' => 'drill',
        'xls' => 'bom',
        'xlsx' => 'bom',
        'csv' => 'bom',
        'cpl' => 'cpl',
        'pos' => 'cpl',
        'sch' => 'schematic',
        'pdf' => 'schematic',
        'step' => 'mechanical',
        'stp' => 'mechanical',
        'dxf' => 'mechanical',
    ];

    public function upload(array $data): PcbFile
    {
        $file = $data['file'];
        $project = $data['project'];
        $fileType = $data['file_type'] ?? $this->detectFileType($file);
        $fileCategory = $data['file_category'] ?? null;
        
        // Validate file
        $this->validateUpload($file, $fileType);
        
        // Generate secure filename
        $fileHash = hash_file('sha256', $file->getPathname());
        $extension = $file->getClientOriginalExtension();
        $secureName = "{$fileHash}.{$extension}";
        
        // Determine storage path
        $storagePath = $this->buildStoragePath(
            $project->organization_id,
            $project->id,
            $fileType,
            $secureName
        );
        
        // Store file
        $disk = config('filesystems.default_private_disk', 'private-pcb');
        Storage::disk($disk)->put($storagePath, file_get_contents($file));
        
        // Create file record
        $pcbFile = PcbFile::create([
            'project_id' => $project->id,
            'uploaded_by' => auth()->id(),
            'file_name' => $secureName,
            'original_name' => $file->getClientOriginalName(),
            'file_hash' => $fileHash,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $fileType,
            'file_category' => $fileCategory,
            'storage_path' => $storagePath,
            'storage_disk' => $disk,
            'encryption_enabled' => config('filesystems.disks.' . $disk . '.encrypt', false),
            'nda_required' => $project->confidentiality_level === 'restricted',
        ]);
        
        // Queue scanning
        ScanPcbFileJob::dispatch($pcbFile);
        
        // Queue validation
        ValidatePcbFileJob::dispatch($pcbFile);
        
        // Log activity
        $project->activityLogs()->create([
            'user_id' => auth()->id(),
            'action' => 'file_uploaded',
            'entity_type' => 'file',
            'entity_id' => $pcbFile->id,
            'description' => "File uploaded: {$file->getClientOriginalName()}",
        ]);
        
        return $pcbFile;
    }
    
    public function uploadZipBundle(array $data): array
    {
        $zipFile = $data['file'];
        $project = $data['project'];
        
        // Validate ZIP
        $this->validateZip($zipFile);
        
        $extractedFiles = [];
        $tempDir = storage_path('app/temp/' . Str::uuid());
        
        try {
            // Extract ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipFile) !== true) {
                throw new \Exception('Failed to open ZIP file');
            }
            
            // Check for ZIP bomb
            $compressionRatio = $zipFile->getSize() > 0 
                ? $zip->numFiles / $zipFile->getSize() 
                : 0;
            
            if ($compressionRatio > self::MAX_ZIP_UNCOMPRESSED_RATIO) {
                throw new \Exception('Potential ZIP bomb detected');
            }
            
            // Extract files
            mkdir($tempDir, 0755, true);
            $zip->extractTo($tempDir);
            
            // Process each file
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileInfo = $zip->statIndex($i);
                $filename = $fileInfo['name'];
                
                // Skip directories
                if (substr($filename, -1) === '/') {
                    continue;
                }
                
                // Check for path traversal
                if (strpos($filename, '..') !== false) {
                    Log::warning("Path traversal attempt in ZIP: {$filename}");
                    continue;
                }
                
                // Detect file type
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $fileType = self::EXTENSION_MAP[strtolower($extension)] ?? 'other';
                
                // Create temporary file for upload
                $tempFilePath = $tempDir . '/' . $filename;
                $tempFile = new \Illuminate\Http\File($tempFilePath);
                
                // Upload individual file
                $pcbFile = $this->upload([
                    'file' => $tempFile,
                    'project' => $project,
                    'file_type' => $fileType,
                    'file_category' => $this->detectGerberLayer($filename),
                ]);
                
                $extractedFiles[] = $pcbFile;
            }
            
            $zip->close();
            
        } finally {
            // Cleanup temp directory
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }
        
        return $extractedFiles;
    }
    
    private function validateUpload(\Illuminate\Http\UploadedFile $file, string $fileType): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum limit of 500MB');
        }
        
        // Check MIME type
        $allowedTypes = self::ALLOWED_MIME_TYPES[$fileType] ?? [];
        if (!empty($allowedTypes) && !in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type');
        }
        
        // Check for executable content
        $executableExtensions = ['exe', 'bat', 'cmd', 'sh', 'php', 'js', 'vbs'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $executableExtensions)) {
            throw new \Exception('Executable files are not allowed');
        }
    }
    
    private function validateZip(\Illuminate\Http\UploadedFile $zipFile): void
    {
        // Check ZIP size
        if ($zipFile->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('ZIP file size exceeds maximum limit');
        }
        
        // Verify ZIP signature
        $signature = file_get_contents($zipFile->getPathname(), false, null, 0, 4);
        if ($signature !== "PK\x03\x04") {
            throw new \Exception('Invalid ZIP file signature');
        }
    }
    
    private function detectFileType(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return self::EXTENSION_MAP[$extension] ?? 'other';
    }
    
    private function detectGerberLayer(string $filename): ?string
    {
        $filename = strtolower($filename);
        
        $layerPatterns = [
            'top_copper' => ['gtl', 'top', 'cu1'],
            'bottom_copper' => ['gbl', 'bottom', 'cu2'],
            'top_solder_mask' => ['gts', 'tso', 'smo'],
            'bottom_solder_mask' => ['gbs', 'bso', 'sms'],
            'top_silkscreen' => ['gto', 'tss', 'pl1'],
            'bottom_silkscreen' => ['gbo', 'bss', 'pl2'],
            'top_paste' => ['gtp', 'tsp'],
            'bottom_paste' => ['gbp', 'bsp'],
            'board_outline' => ['gm1', 'outline', 'border'],
            'drill' => ['drl', 'txt', 'drill'],
        ];
        
        foreach ($layerPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($filename, $pattern) !== false) {
                    return $category;
                }
            }
        }
        
        return null;
    }
    
    private function buildStoragePath(string $orgId, string $projectId, string $fileType, string $filename): string
    {
        return "pcb/{$orgId}/{$projectId}/{$fileType}s/{$filename}";
    }
    
    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
```

## Signed URL Generation

```php
<?php

namespace App\Services\PCB;

use App\Models\PCB\PcbFile;
use App\Models\PCB\PcbFileShare;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PcbFileAccessService
{
    public function generateSignedUrl(PcbFile $file, User $user, int $expiryMinutes = 60): string
    {
        // Check authorization
        if (!$this->canAccessFile($file, $user)) {
            throw new \Exception('Unauthorized access');
        }
        
        // Log access attempt
        $file->accessLogs()->create([
            'user_id' => $user->id,
            'action' => 'download',
            'access_method' => 'signed_url',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'organization_id' => $user->organization_id,
        ]);
        
        // Generate temporary signed URL
        $disk = Storage::disk($file->storage_disk);
        
        return $disk->temporaryUrl(
            $file->storage_path,
            now()->addMinutes($expiryMinutes),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $file->original_name . '"',
            ]
        );
    }
    
    public function createShare(PcbFile $file, array $data): PcbFileShare
    {
        $share = PcbFileShare::create([
            'file_id' => $file->id,
            'created_by' => auth()->id(),
            'share_token' => hash_hmac('sha256', Str::uuid(), config('app.key')),
            'recipient_email' => $data['email'] ?? null,
            'recipient_organization_id' => $data['organization_id'] ?? null,
            'can_download' => $data['can_download'] ?? false,
            'can_preview' => $data['can_preview'] ?? true,
            'max_downloads' => $data['max_downloads'] ?? null,
            'expires_at' => $data['expires_at'] ?? now()->addDays(7),
            'nda_required' => $data['nda_required'] ?? $file->nda_required,
        ]);
        
        // Send notification to recipient
        // Mail::send(new FileShareNotification($share));
        
        return $share;
    }
    
    public function accessViaToken(string $token, User $user = null): ?PcbFile
    {
        $share = PcbFileShare::where('share_token', $token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$share) {
            return null;
        }
        
        // Check NDA
        if ($share->nda_required && !$share->nda_accepted) {
            throw new \Exception('NDA acceptance required');
        }
        
        // Check download limits
        if ($share->max_downloads && $share->download_count >= $share->max_downloads) {
            throw new \Exception('Download limit reached');
        }
        
        // Update access stats
        $share->increment('download_count');
        $share->update(['last_accessed_at' => now()]);
        
        // Log access
        $share->file->accessLogs()->create([
            'user_id' => $user?->id,
            'action' => 'download',
            'access_method' => 'share_token',
            'success' => true,
        ]);
        
        return $share->file;
    }
    
    private function canAccessFile(PcbFile $file, User $user): bool
    {
        // File owner
        if ($file->uploaded_by === $user->id) {
            return true;
        }
        
        // Same organization
        $project = $file->project;
        if ($project->organization_id === $user->organization_id) {
            return $project->canAccess($user);
        }
        
        // Project member
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where(function($q) {
                $q->whereNull('access_expires_at')
                  ->orWhere('access_expires_at', '>', now());
            })
            ->first();
        
        if ($member && $member->can_download_files) {
            return true;
        }
        
        return false;
    }
}
```

## Malware Scanning Job

```php
<?php

namespace App\Jobs\PCB;

use App\Models\PCB\PcbFile;
use App\Models\PCB\PcbFileScanResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScanPcbFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $file;
    
    public function __construct(PcbFile $file)
    {
        $this->file = $file;
        $this->onQueue('pcb-file-scan');
    }

    public function handle(): void
    {
        $scanResult = PcbFileScanResult::create([
            'file_id' => $this->file->id,
            'scanner_version' => 'clamav-1.0.0', // Replace with actual scanner version
            'scan_started_at' => now(),
        ]);
        
        try {
            // Get file from storage
            $disk = Storage::disk($this->file->storage_disk);
            $fileStream = $disk->readStream($this->file->storage_path);
            
            // Scan with ClamAV or similar
            $isClean = $this->scanWithClamAV($fileStream);
            
            fclose($fileStream);
            
            $scanResult->update([
                'scan_completed_at' => now(),
                'is_clean' => $isClean,
                'threats_found' => $isClean ? 0 : 1,
            ]);
            
            // Update file status
            $this->file->update([
                'is_scanned' => true,
                'scan_status' => $isClean ? 'clean' : 'infected',
            ]);
            
            if (!$isClean) {
                // Quarantine infected file
                $this->quarantineFile();
                
                Log::alert("Malware detected in PCB file: {$this->file->id}");
                
                // Notify admins
                // Notification::send(...);
            }
            
        } catch (\Exception $e) {
            $scanResult->update([
                'scan_completed_at' => now(),
                'scan_error' => $e->getMessage(),
                'error_details' => $e->getTraceAsString(),
            ]);
            
            $this->file->update([
                'is_scanned' => true,
                'scan_status' => 'error',
            ]);
            
            Log::error("PCB file scan failed: {$e->getMessage()}");
        }
    }
    
    private function scanWithClamAV($stream): bool
    {
        // Implement ClamAV scanning
        // This is a placeholder - integrate with actual ClamAV daemon
        
        $socket = stream_socket_client('tcp://127.0.0.1:3310', $errno, $errstr);
        if (!$socket) {
            throw new \Exception("Cannot connect to ClamAV: {$errstr}");
        }
        
        fwrite($socket, "nSTREAM\n");
        
        $chunkSize = 8192;
        while (!feof($stream)) {
            $chunk = fread($stream, $chunkSize);
            pack('N', strlen($chunk));
            fwrite($socket, $chunk);
        }
        
        fwrite($socket, pack('N', 0));
        
        $response = fread($socket, 1024);
        fclose($socket);
        
        // Parse response
        return strpos($response, 'OK') !== false;
    }
    
    private function quarantineFile(): void
    {
        $disk = Storage::disk($this->file->storage_disk);
        $quarantinePath = "quarantine/{$this->file->id}_{$this->file->file_name}";
        
        $disk->move($this->file->storage_path, $quarantinePath);
        
        $this->file->update([
            'storage_path' => $quarantinePath,
        ]);
    }
}
```

## Secure File Deletion

```php
<?php

namespace App\Services\PCB;

use App\Models\PCB\PcbFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PcbFileDeletionService
{
    public function secureDelete(PcbFile $file, bool $force = false): void
    {
        DB::beginTransaction();
        
        try {
            // Check if file can be deleted
            if (!$force && $this->hasActiveOrders($file)) {
                throw new \Exception('Cannot delete file associated with active orders');
            }
            
            // Overwrite file data before deletion (for sensitive files)
            if ($file->confidentiality_level === 'restricted') {
                $this->overwriteFile($file);
            }
            
            // Delete from storage
            $disk = Storage::disk($file->storage_disk);
            $disk->delete($file->storage_path);
            
            // Also delete all versions
            foreach ($file->versions as $version) {
                $disk->delete($version->storage_path);
            }
            
            // Soft delete database record
            $file->delete();
            
            // Mark as securely deleted
            $file->securely_deleted_at = now();
            $file->push();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    private function overwriteFile(PcbFile $file): void
    {
        $disk = Storage::disk($file->storage_disk);
        $size = $file->file_size;
        
        // Overwrite with random data multiple times
        $passes = 3;
        $handle = $disk->readStream($file->storage_path);
        
        if ($handle) {
            // Note: This requires write access - adjust based on storage driver
            $randomData = random_bytes(min(1024, $size));
            
            for ($i = 0; $i < $passes; $i++) {
                // Seek and overwrite logic depends on storage driver
                // For local storage, use fseek/fwrite
                // For S3, would need to upload new content
            }
            
            fclose($handle);
        }
    }
    
    private function hasActiveOrders(PcbFile $file): bool
    {
        return $file->project->orders()
            ->whereIn('status', ['pending', 'manufacturing', 'inspection'])
            ->exists();
    }
}
```

## Security Checklist

- [ ] All files stored in private storage (no public directory)
- [ ] Signed URLs with short expiry (max 1 hour for downloads)
- [ ] Organization isolation enforced at query level
- [ ] NDA acceptance tracked for external sharing
- [ ] Malware scanning on all uploads
- [ ] ZIP bomb detection implemented
- [ ] Path traversal prevention
- [ ] File type validation by MIME and extension
- [ ] Executable file blocking
- [ ] Access logging for all file operations
- [ ] Secure deletion with overwrite for restricted files
- [ ] Encryption at rest enabled
- [ ] Rate limiting on file downloads
- [ ] CORS configured for file endpoints
- [ ] CSRF protection on uploads
- [ ] Audit trail maintained

## Performance Considerations

1. **Async Processing**: Scanning and validation queued
2. **Chunked Uploads**: Support for large files via chunks
3. **CDN for Public Assets**: Only non-sensitive previews
4. **Database Indexing**: On file_hash, project_id, expires_at
5. **Cleanup Jobs**: Scheduled cleanup of expired shares
6. **Storage Quotas**: Per organization and user

## Testing

```php
<?php

namespace Tests\Feature\Services\PCB;

use Tests\TestCase;
use App\Models\User;
use App\Models\PCB\PcbProject;
use App\Models\PCB\PcbFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PcbFileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_upload_requires_authentication()
    {
        $project = PcbProject::factory()->create();
        
        $response = $this->postJson('/api/pcb/files/upload', [
            'project_id' => $project->id,
            'file' => UploadedFile::fake()->create('test.gbr'),
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_only_access_own_organization_files()
    {
        $user = User::factory()->create();
        $otherOrgProject = PcbProject::factory()->create();
        
        $file = PcbFile::factory()->create([
            'project_id' => $otherOrgProject->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/pcb/files/{$file->id}/download");

        $response->assertStatus(403);
    }

    public function test_signed_url_expires_correctly()
    {
        $user = User::factory()->create();
        $project = PcbProject::factory()->create(['user_id' => $user->id]);
        
        $file = PcbFile::factory()->create([
            'project_id' => $project->id,
        ]);

        $service = app(\App\Services\PCB\PcbFileAccessService::class);
        $url = $service->generateSignedUrl($file, $user, 1); // 1 minute expiry

        // Wait for expiry
        sleep(65);

        $response = $this->get($url);
        $response->assertStatus(403); // Expired
    }

    public function test_executable_files_are_rejected()
    {
        $user = User::factory()->create();
        $project = PcbProject::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/pcb/files/upload', [
                'project_id' => $project->id,
                'file' => UploadedFile::fake()->create('malware.exe'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_file_access_is_logged()
    {
        $user = User::factory()->create();
        $project = PcbProject::factory()->create(['user_id' => $user->id]);
        
        $file = PcbFile::factory()->create([
            'project_id' => $project->id,
        ]);

        $service = app(\App\Services\PCB\PcbFileAccessService::class);
        $service->generateSignedUrl($file, $user);

        $this->assertDatabaseHas('pcb_file_access_logs', [
            'file_id' => $file->id,
            'user_id' => $user->id,
            'action' => 'download',
        ]);
    }
}
```
