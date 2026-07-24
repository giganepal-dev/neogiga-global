<?php

namespace App\Services\Marketing;

use InvalidArgumentException;

class TransactionalCommunicationService
{
    public const EVENTS = [
        'registration_received', 'email_verification', 'account_activation', 'account_approved', 'account_rejected', 'welcome',
        'seller_application_received', 'seller_application_approved', 'distributor_application_received', 'distributor_application_approved',
        'order_placed', 'order_confirmed', 'payment_received', 'payment_failed', 'order_processing', 'item_backordered',
        'partial_shipment', 'order_shipped', 'tracking_available', 'order_delivered', 'order_cancelled', 'refund_initiated',
        'refund_completed', 'invoice_generated', 'invoice_updated', 'rfq_received', 'rfq_assigned', 'rfq_clarification_requested',
        'quotation_ready', 'quotation_expiring', 'quotation_accepted', 'quotation_rejected', 'bom_complete', 'bom_needs_review',
        'password_reset', 'email_changed', 'password_changed', 'suspicious_login', 'two_factor_code', 'account_locked',
        'account_reactivated', 'support_updated',
    ];

    public function __construct(private EmailQueueService $queue, private RegionalEmailBrandingService $branding) {}

    public function queue(string $event, string $email, array $data = []): int
    {
        if (! in_array($event, self::EVENTS, true)) {
            throw new InvalidArgumentException("Unsupported transactional communication event [{$event}].");
        }
        $marketplaceId = isset($data['marketplace_id']) ? (int) $data['marketplace_id'] : null;
        $brand = $this->branding->context($marketplaceId, 'transactional');
        $subject = $this->subject($event, $data, $brand['marketplace_name']);
        $body = $this->body($event, $data, $brand);
        $relatedType = (string) ($data['related_type'] ?? $this->relatedType($event));
        $relatedId = isset($data['related_id']) ? (int) $data['related_id'] : null;
        $eventId = (string) ($data['event_id'] ?? $data['status'] ?? $data['tracking_number'] ?? $data['invoice_number'] ?? 'v1');

        $metadata = [
            'event_type' => $event, 'related_type' => $relatedType, 'related_id' => $relatedId,
            'marketplace_id' => $marketplaceId, 'country_id' => $data['country_id'] ?? null,
            'idempotency_key' => hash('sha256', implode('|', ['transactional', $event, $relatedType, $relatedId, $eventId, mb_strtolower($email)])),
            'template_variables' => array_intersect_key($data, array_flip(['order_number', 'order_status', 'tracking_number', 'invoice_number', 'rfq_number', 'quotation_number'])),
        ];
        if (in_array($event, ['email_verification', 'account_activation', 'password_reset', 'two_factor_code'], true)) {
            $metadata['sensitive_html'] = $body;
        }

        return $this->queue->queue($email, $subject, $body, 'transactional', $metadata);
    }

    private function subject(string $event, array $data, string $brand): string
    {
        $reference = $data['order_number'] ?? $data['rfq_number'] ?? $data['quotation_number'] ?? $data['invoice_number'] ?? '';
        $label = str($event)->replace('_', ' ')->title();

        return trim($brand.' — '.$label.($reference ? ' '.$reference : ''));
    }

    private function body(string $event, array $data, array $brand): string
    {
        // Try Blade template for known event types
        $template = $this->templateForEvent($event);
        if ($template && view()->exists($template)) {
            try {
                return view($template, $this->templateData($event, $data, $brand))->render();
            } catch (\Throwable) {
                // Fall through to inline rendering on template error
            }
        }

        // Inline fallback for events without templates
        return $this->inlineBody($event, $data, $brand);
    }

    private function templateForEvent(string $event): ?string
    {
        return match (true) {
            in_array($event, ['registration_received', 'email_verification', 'account_activation', 'welcome'], true) => 'mail.transactional.welcome',
            in_array($event, ['order_placed', 'order_confirmed'], true) => 'mail.transactional.order-confirmation',
            str_starts_with($event, 'order_'), str_starts_with($event, 'payment_'), str_starts_with($event, 'refund_') => 'mail.transactional.order-status',
            in_array($event, ['password_reset', 'email_changed', 'password_changed'], true) => 'mail.transactional.password-reset',
            in_array($event, ['rfq_received', 'rfq_assigned', 'rfq_clarification_requested'], true) => 'mail.transactional.rfq-received',
            in_array($event, ['quotation_ready', 'quotation_expiring', 'quotation_accepted', 'quotation_rejected'], true) => 'mail.transactional.quotation-ready',
            str_starts_with($event, 'support_') => 'mail.transactional.support-updated',
            in_array($event, ['invoice_generated', 'invoice_updated'], true) => 'mail.transactional.invoice-generated',
            in_array($event, ['suspicious_login', 'two_factor_code', 'account_locked', 'account_reactivated'], true) => 'mail.transactional.password-reset',
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function templateData(string $event, array $data, array $brand): array
    {
        $locale = (string) ($data['locale'] ?? 'en');
        $statusLabels = [
            'order_placed' => 'Order Received', 'order_confirmed' => 'Order Confirmed',
            'order_approved' => 'Approved', 'order_processing' => 'Processing',
            'order_shipped' => 'Shipped', 'order_delivered' => 'Delivered',
            'order_cancelled' => 'Cancelled', 'order_refunded' => 'Refunded',
            'payment_received' => 'Payment Received', 'payment_failed' => 'Payment Failed',
        ];

        return [
            'locale' => $locale,
            'brand' => $brand['marketplace_name'] ?? 'NeoGiga',
            'regionName' => $brand['marketplace_name'] ?? null,
            'subject' => $data['subject'] ?? ($statusLabels[$event] ?? 'NeoGiga update'),
            'securityNote' => 'This is a transactional message related to your account or order. It contains no promotional content.',
            'greeting' => $data['greeting'] ?? 'Welcome to NeoGiga!',
            'userName' => $data['customer_name'] ?? 'Customer',
            'userEmail' => $data['customer_email'] ?? '',
            'loginUrl' => $data['login_url'] ?? ($brand['base_url'] ?? 'https://neogiga.com') . '/en/login',
            'orderNumber' => $data['order_number'] ?? '',
            'orderDate' => $data['order_date'] ?? '',
            'orderStatus' => $data['order_status'] ?? '',
            'orderTotal' => $data['order_total'] ?? '0.00',
            'orderUrl' => $data['order_url'] ?? ($brand['base_url'] ?? 'https://neogiga.com') . '/en/account/orders',
            'currency' => $data['currency'] ?? 'USD',
            'paymentStatus' => $data['payment_status'] ?? '',
            'statusLabel' => $statusLabels[$event] ?? str_replace('_', ' ', $event),
            'statusBadge' => in_array($event, ['order_cancelled', 'order_refunded', 'payment_failed']) ? 'badge-warn' : 'badge-ok',
            'statusDate' => $data['status_date'] ?? date('Y-m-d H:i'),
            'statusMessage' => $data['status_message'] ?? '',
            'nextStep' => $data['next_step'] ?? '',
            'trackingNumber' => $data['tracking_number'] ?? '',
            'carrier' => $data['carrier'] ?? '',
            'customerAction' => $data['customer_action'] ?? '',
            'shippingAddress' => $data['shipping_address'] ?? '',
            'products' => $data['products'] ?? [],
            'verificationUrl' => $data['verification_url'] ?? null,
            'activationUrl' => $data['activation_url'] ?? null,
            'passwordResetUrl' => $data['password_reset_url'] ?? null,
        ];
    }

    private function inlineBody(string $event, array $data, array $brand): string
    {
        $label = e((string) str($event)->replace('_', ' ')->title());
        $name = e((string) ($data['customer_name'] ?? 'Customer'));
        $details = [];
        foreach (['order_number' => 'Order', 'order_status' => 'Status', 'tracking_number' => 'Tracking', 'invoice_number' => 'Invoice', 'rfq_number' => 'RFQ', 'quotation_number' => 'Quotation'] as $key => $title) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $details[] = '<li><strong>'.e($title).':</strong> '.e((string) $data[$key]).'</li>';
            }
        }
        foreach (['verification_url' => 'Verify email', 'activation_url' => 'Activate account', 'password_reset_url' => 'Reset password', 'tracking_url' => 'Track shipment', 'invoice_url' => 'View invoice', 'quotation_url' => 'View quotation', 'support_url' => 'View support request'] as $key => $title) {
            if (! empty($data[$key]) && filter_var($data[$key], FILTER_VALIDATE_URL)) {
                $details[] = '<li><a href="'.e((string) $data[$key]).'">'.e($title).'</a></li>';
            }
        }
        $list = $details ? '<ul>'.implode('', $details).'</ul>' : '';

        return '<p>Hello '.$name.',</p><p>This is your required service update: <strong>'.$label.'</strong>.</p>'.$list.'<p>Visit <a href="'.e($brand['base_url']).'">'.e($brand['marketplace_name']).'</a> for account and order details.</p><p>This transactional message contains no promotional content.</p>';
    }

    private function relatedType(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'order_'), str_starts_with($event, 'payment_'), str_starts_with($event, 'refund_'), str_starts_with($event, 'invoice_'), in_array($event, ['item_backordered', 'partial_shipment', 'tracking_available'], true) => 'order',
            str_starts_with($event, 'rfq_'), str_starts_with($event, 'quotation_'), str_starts_with($event, 'bom_') => 'rfq',
            str_contains($event, 'application') => 'application',
            str_starts_with($event, 'support_') => 'support_ticket',
            default => 'account',
        };
    }
}
