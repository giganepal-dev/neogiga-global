<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketplace_id',
        'channel_email',
        'channel_push',
        'channel_sms',
        'allow_marketing',
        'allow_price_alerts',
        'allow_back_in_stock',
        'allow_product_updates',
        'allow_seller_messages',
        'allow_promotions',
        'updated_at',
    ];

    protected $casts = [
        'channel_email' => 'boolean',
        'channel_push' => 'boolean',
        'channel_sms' => 'boolean',
        'allow_marketing' => 'boolean',
        'allow_price_alerts' => 'boolean',
        'allow_back_in_stock' => 'boolean',
        'allow_product_updates' => 'boolean',
        'allow_seller_messages' => 'boolean',
        'allow_promotions' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace\Marketplace::class);
    }

    public function allowsNotification(string $type, string $channel): bool
    {
        // Mandatory notifications (security, transactional) cannot be disabled
        $mandatoryTypes = ['security_alert', 'order_confirmation', 'payment_confirmation', 'password_reset', 'otp'];
        if (in_array($type, $mandatoryTypes)) {
            return true;
        }

        // Check channel permission
        $channelKey = "channel_{$channel}";
        if (!$this->$channelKey) {
            return false;
        }

        // Check type-specific permissions
        switch ($type) {
            case 'marketing':
                return $this->allow_marketing;
            case 'price_alert':
                return $this->allow_price_alerts;
            case 'back_in_stock':
                return $this->allow_back_in_stock;
            case 'product_update':
                return $this->allow_product_updates;
            case 'seller_message':
                return $this->allow_seller_messages;
            case 'promotion':
                return $this->allow_promotions;
            default:
                return true; // Allow unknown types by default
        }
    }
}
