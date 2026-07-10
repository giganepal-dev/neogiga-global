<?php

namespace App\Models\CatalogImport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * CatalogSourceCredential Model
 * 
 * Stores encrypted API credentials for catalog sources.
 * Credentials are AES-256 encrypted and never logged.
 */
class CatalogSourceCredential extends Model
{
    protected $fillable = [
        'catalog_source_id',
        'credential_type',
        'credential_name',
        'encrypted_value',
        'encryption_version',
        'expires_at',
        'active',
        'metadata',
        'created_by',
        'last_rotated_by',
        'last_rotated_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'last_rotated_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'encrypted_value',
    ];

    /**
     * Credential type constants
     */
    const TYPE_API_KEY = 'api_key';
    const TYPE_SECRET = 'secret';
    const TYPE_USERNAME = 'username';
    const TYPE_PASSWORD = 'password';
    const TYPE_TOKEN = 'token';
    const TYPE_CERTIFICATE = 'certificate';

    public function source(): BelongsTo
    {
        return $this->belongsTo(CatalogSource::class, 'catalog_source_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function lastRotator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'last_rotated_by');
    }

    /**
     * Get the decrypted credential value
     */
    public function getDecryptedValue(): ?string
    {
        try {
            return Crypt::decryptString($this->encrypted_value);
        } catch (DecryptException $e) {
            \Log::error('Failed to decrypt credential', [
                'credential_id' => $this->id,
                'credential_type' => $this->credential_type,
            ]);
            return null;
        }
    }

    /**
     * Set and encrypt a credential value
     */
    public function setEncryptedValue(string $value): void
    {
        $this->encrypted_value = Crypt::encryptString($value);
        $this->encryption_version = 'v1'; // Update if encryption scheme changes
    }

    /**
     * Check if credential is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Record credential rotation
     */
    public function recordRotation(?int $userId = null): void
    {
        $this->update([
            'last_rotated_at' => now(),
            'last_rotated_by' => $userId,
        ]);
    }

    /**
     * Scope to get only active, non-expired credentials
     */
    public function scopeValid($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
