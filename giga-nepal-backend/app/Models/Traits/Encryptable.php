<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

/**
 * Trait Encryptable
 * 
 * Automatically encrypts and decrypts specified model attributes.
 * Add this trait to models and define $encryptable array with attribute names.
 * 
 * Usage:
 * class User extends Model
 * {
 *     use Encryptable;
 *     
 *     protected $encryptable = [
 *         'bank_account_number',
 *         'tax_identification_number',
 *         'national_id',
 *     ];
 * }
 */
trait Encryptable
{
    /**
     * List of attributes that should be encrypted.
     * Override this in your model.
     */
    protected array $encryptable = [];

    /**
     * Get the encryptable attributes.
     */
    public function getEncryptableAttributes(): array
    {
        return $this->encryptable;
    }

    /**
     * Loop over attributes and encrypt values.
     */
    protected function initializeEncryptable(): void
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            $this->mergeCasts([$attribute => 'encrypted']);
        }
    }

    /**
     * Encrypt an attribute value.
     */
    protected function encryptAttribute($value): string
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt an attribute value.
     */
    protected function decryptAttribute($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, return the original value
            // This handles cases where data was not encrypted yet
            return $value;
        }
    }

    /**
     * Set encrypted attribute mutator.
     */
    protected function setEncryptAttribute(string $key, $value): void
    {
        if (in_array($key, $this->getEncryptableAttributes())) {
            $this->attributes[$key] = $this->encryptAttribute($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Get encrypted attribute accessor.
     */
    protected function getEncryptAttribute(string $key): mixed
    {
        if (in_array($key, $this->getEncryptableAttributes()) && isset($this->attributes[$key])) {
            return $this->decryptAttribute($this->attributes[$key]);
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * Boot the encryptable trait.
     */
    public static function bootEncryptable(): void
    {
        static::retrieved(function ($model) {
            if ($model instanceof self) {
                foreach ($model->getEncryptableAttributes() as $attribute) {
                    if (isset($model->attributes[$attribute])) {
                        $model->attributes[$attribute] = $model->decryptAttribute($model->attributes[$attribute]);
                    }
                }
            }
        });

        static::saving(function ($model) {
            if ($model instanceof self) {
                foreach ($model->getEncryptableAttributes() as $attribute) {
                    if (isset($model->attributes[$attribute]) && $model->isDirty($attribute)) {
                        $model->attributes[$attribute] = $model->encryptAttribute($model->attributes[$attribute]);
                    }
                }
            }
        });
    }
}
