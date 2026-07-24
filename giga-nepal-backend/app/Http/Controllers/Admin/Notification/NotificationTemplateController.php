<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Services\Marketing\TransactionalCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    private string $templatePath = 'resources/views/mail/transactional';

    public function index(): View
    {
        $templates = $this->getTemplates();
        $events = TransactionalCommunicationService::EVENTS;

        return view('admin.notification.templates.index', compact('templates', 'events'));
    }

    public function show(string $template): View
    {
        $file = $this->getTemplatePath($template);
        abort_unless($file, 404);

        $content = File::get($file);
        $events = $this->getEventsForTemplate($template);

        return view('admin.notification.templates.show', [
            'template' => $template,
            'content' => $content,
            'events' => $events,
            'filePath' => $file,
        ]);
    }

    public function edit(string $template): View
    {
        $file = $this->getTemplatePath($template);
        abort_unless($file, 404);

        $content = File::get($file);

        return view('admin.notification.templates.edit', [
            'template' => $template,
            'content' => $content,
        ]);
    }

    public function update(Request $request, string $template): RedirectResponse
    {
        $file = $this->getTemplatePath($template);
        abort_unless($file, 404);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:50000'],
        ]);

        File::put($file, $data['content']);

        return redirect("/admin/notification/templates/{$template}")->with('status', 'Template updated.');
    }

    public function preview(Request $request, string $template): View
    {
        $file = $this->getTemplatePath($template);
        abort_unless($file, 404);

        $content = File::get($file);

        // Render with sample data
        $sampleData = $this->sampleData($template);
        $rendered = view('mail.transactional.layout')->render();
        // Simple preview — just show the raw template content
        return view('admin.notification.templates.preview', [
            'template' => $template,
            'content' => $content,
            'sampleData' => $sampleData,
        ]);
    }

    private function getTemplates(): array
    {
        $path = resource_path('views/mail/transactional');
        $files = File::glob($path . '/*.blade.php');

        $templates = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if ($name === 'layout') {
                continue;
            }
            $templates[$name] = [
                'name' => $name,
                'title' => ucwords(str_replace('-', ' ', $name)),
                'file' => $file,
                'size' => File::size($file),
                'modified' => File::lastModified($file),
            ];
        }

        ksort($templates);

        return $templates;
    }

    private function getTemplatePath(string $template): ?string
    {
        $file = resource_path("views/mail/transactional/{$template}.blade.php");

        return File::exists($file) ? $file : null;
    }

    private function getEventsForTemplate(string $template): array
    {
        return match ($template) {
            'welcome' => ['registration_received', 'email_verification', 'account_activation', 'welcome'],
            'order-confirmation' => ['order_placed', 'order_confirmed'],
            'order-status' => ['order_processing', 'order_shipped', 'order_delivered', 'order_cancelled', 'payment_received', 'payment_failed', 'refund_initiated', 'refund_completed'],
            'password-reset' => ['password_reset'],
            'rfq-received' => ['rfq_received', 'rfq_assigned'],
            'quotation-ready' => ['quotation_ready', 'quotation_expiring', 'quotation_accepted', 'quotation_rejected'],
            'support-updated' => ['support_updated'],
            'invoice-generated' => ['invoice_generated', 'invoice_updated'],
            default => [],
        };
    }

    private function sampleData(string $template): array
    {
        return match ($template) {
            'welcome' => [
                'userName' => 'John Doe',
                'userEmail' => 'john@example.com',
                'regionName' => 'NeoGiga Global',
                'verificationUrl' => url('/email/verify/demo'),
            ],
            'order-confirmation' => [
                'orderNumber' => 'ORD-GIG-2026-00001',
                'orderDate' => '2026-07-24',
                'orderStatus' => 'Confirmed',
                'orderTotal' => '1,250.00',
                'currency' => 'USD',
                'paymentStatus' => 'Pending',
                'products' => [
                    ['name' => 'Arduino Uno R4 WiFi', 'quantity' => 5, 'price' => '27.50'],
                    ['name' => 'USB-C Cable 2m', 'quantity' => 10, 'price' => '4.99'],
                ],
            ],
            'order-status' => [
                'orderNumber' => 'ORD-GIG-2026-00001',
                'statusLabel' => 'Shipped',
                'statusBadge' => 'badge-ok',
                'statusDate' => '2026-07-24',
                'trackingNumber' => 'TRK-123456789',
                'carrier' => 'DHL Express',
            ],
            'password-reset' => [
                'userName' => 'John Doe',
                'passwordResetUrl' => url('/password/reset/demo'),
                'expiryMinutes' => 60,
            ],
            'rfq-received' => [
                'rfqNumber' => 'RFQ-2026-00042',
                'statusDate' => '2026-07-24',
            ],
            'quotation-ready' => [
                'quotationNumber' => 'QT-2026-00042',
                'rfqNumber' => 'RFQ-2026-00042',
                'orderTotal' => '3,500.00',
                'currency' => 'USD',
                'quotationUrl' => url('/en/rfqs/quotations/demo'),
            ],
            'support-updated' => [
                'ticketNumber' => 'SUP-2026-00128',
                'statusLabel' => 'Replied',
                'statusDate' => '2026-07-24',
                'statusMessage' => 'We have reviewed your issue and applied a fix.',
                'supportUrl' => url('/en/account/support/demo'),
            ],
            'invoice-generated' => [
                'invoiceNumber' => 'INV-GIG-2026-00001',
                'orderNumber' => 'ORD-GIG-2026-00001',
                'orderTotal' => '1,250.00',
                'currency' => 'USD',
                'invoiceUrl' => url('/verify/invoice/INV-GIG-2026-00001'),
            ],
            default => [],
        };
    }
}
