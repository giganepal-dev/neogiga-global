<?php

namespace NeoGiga\Models\Traits;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * List of attributes that should be encrypted.
     * Override this in your model.
     *
     * @var array
     */
    protected $encryptable = [];

    /**
     * Get the encryption algorithm to use.
     * Override this in your model if needed.
     *
     * @return string
     */
    protected function getEncryptionAlgorithm(): string
    {
        return config('app.cipher', 'AES-256-CBC');
    }

    /**
     * Encrypt attributes before saving.
     *
     * @param  mixed  $value
     * @param  string  $key
     * @return string|null
     */
    public function setEncryptableAttribute($key, $value): ?string
    {
        if (in_array($key, $this->getEncryptableAttributes())) {
            if (is_null($value)) {
                return null;
            }

            return Crypt::encryptString((string) $value);
        }

        return $value;
    }

    /**
     * Decrypt attributes when accessing.
     *
     * @param  string  $value
     * @param  string  $key
     * @return mixed
     */
    public function getEncryptableAttribute($key, $value)
    {
        if (in_array($key, $this->getEncryptableAttributes())) {
            if (is_null($value)) {
                return null;
            }

            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Log the error and return null for invalid encrypted data
                \Log::warning('Failed to decrypt attribute', [
                    'model' => static::class,
                    'attribute' => $key,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $value;
    }

    /**
     * Get the list of encryptable attributes.
     *
     * @return array
     */
    public function getEncryptableAttributes(): array
    {
        return property_exists($this, 'encryptable') ? $this->encryptable : [];
    }

    /**
     * Check if an attribute is encryptable.
     *
     * @param  string  $key
     * @return bool
     */
    public function isEncryptable(string $key): bool
    {
        return in_array($key, $this->getEncryptableAttributes());
    }

    /**
     * Encrypt all encryptable attributes in bulk.
     * Useful for migration scripts.
     *
     * @return void
     */
    public function encryptAllAttributes(): void
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            $value = $this->getOriginal($attribute);

            if (!is_null($value) && !empty($value)) {
                $this->setAttribute($attribute, $value);
            }
        }
    }
}
