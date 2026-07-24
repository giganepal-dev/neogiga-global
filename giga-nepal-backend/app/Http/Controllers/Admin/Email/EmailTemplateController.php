<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Services\Marketing\EmailTemplateService;
use App\Services\Marketing\EmailTemplateValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_templates');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('event_key', 'ilike', "%{$search}%");
            });
        }

        $templates = $query->orderByDesc('updated_at')->paginate(20);
        $types = DB::table('email_templates')->distinct()->pluck('type')->filter()->sort()->values();

        return view('admin.email.templates.index', compact('templates', 'types'));
    }

    public function create(): View
    {
        $blocks = $this->blockLibrary();

        return view('admin.email.templates.create', compact('blocks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'event_key' => ['required', 'string', 'max:100', 'unique:email_templates,event_key'],
            'type' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:190'],
            'body_html' => ['required', 'string', 'max:50000'],
            'body_text' => ['nullable', 'string', 'max:20000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_transactional' => ['nullable', 'boolean'],
        ]);

        $validator = app(EmailTemplateValidator::class);
        $validation = $validator->validate($data, ! ($data['is_transactional'] ?? false));

        if (! $validation['valid']) {
            return back()->withInput()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }

        $templateId = DB::transaction(function () use ($data, $validation): int {
            $id = DB::table('email_templates')->insertGetId([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'event_key' => $data['event_key'],
                'type' => $data['type'] ?? 'marketing',
                'subject' => $data['subject'],
                'html_body' => $data['body_html'],
                'text_body' => $data['body_text'] ?? null,
                'description' => $data['description'] ?? null,
                'variables' => json_encode($validation['variables']),
                'is_transactional' => (bool) ($data['is_transactional'] ?? false),
                'is_active' => true,
                'is_default' => false,
                'version' => 1,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('email_template_versions')->insert([
                'email_template_id' => $id,
                'version' => 1,
                'subject' => $data['subject'],
                'html_body' => $data['body_html'],
                'text_body' => $data['body_text'] ?? null,
                'variables' => json_encode($validation['variables']),
                'validation_results' => json_encode($validation),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });

        return redirect("/email/templates/{$templateId}")->with('status', 'Template created.');
    }

    public function show(int $template): View
    {
        $row = DB::table('email_templates')->find($template);
        abort_unless($row, 404);

        $versions = DB::table('email_template_versions')
            ->where('email_template_id', $template)
            ->orderByDesc('version')
            ->get();

        $rendered = app(EmailTemplateService::class)->render($row->html_body, [
            'first_name' => 'John',
            'contact_name' => 'John Doe',
            'customer_name' => 'John Doe',
            'company_name' => 'Acme Corp',
            'email' => 'john@example.com',
            'marketplace_name' => 'NeoGiga',
            'marketplace_url' => url('/'),
            'current_year' => date('Y'),
            'order_number' => 'ORD-001',
            'invoice_number' => 'INV-001',
            'unsubscribe_url' => url('/email/unsubscribe/demo'),
            'preferences_url' => url('/email/preference/demo'),
        ]);

        return view('admin.email.templates.show', [
            'template' => $row,
            'versions' => $versions,
            'rendered' => $rendered,
        ]);
    }

    public function edit(int $template): View
    {
        $row = DB::table('email_templates')->find($template);
        abort_unless($row, 404);

        $blocks = $this->blockLibrary();

        return view('admin.email.templates.edit', [
            'template' => $row,
            'blocks' => $blocks,
        ]);
    }

    public function update(Request $request, int $template): RedirectResponse
    {
        $row = DB::table('email_templates')->find($template);
        abort_unless($row, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'event_key' => ['required', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:190'],
            'body_html' => ['required', 'string', 'max:50000'],
            'body_text' => ['nullable', 'string', 'max:20000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validator = app(EmailTemplateValidator::class);
        $validation = $validator->validate($data, true);

        if (! $validation['valid']) {
            return back()->withInput()->with('error', 'Template validation failed: '.implode(', ', $validation['errors']));
        }

        $newVersion = ((int) $row->version) + 1;

        DB::transaction(function () use ($data, $validation, $template, $newVersion): void {
            DB::table('email_templates')->where('id', $template)->update([
                'name' => $data['name'],
                'event_key' => $data['event_key'],
                'type' => $data['type'] ?? $data['type'],
                'subject' => $data['subject'],
                'html_body' => $data['body_html'],
                'text_body' => $data['body_text'] ?? null,
                'description' => $data['description'] ?? null,
                'variables' => json_encode($validation['variables']),
                'is_active' => $request->boolean('is_active', true),
                'version' => $newVersion,
                'updated_at' => now(),
            ]);

            DB::table('email_template_versions')->insert([
                'email_template_id' => $template,
                'version' => $newVersion,
                'subject' => $data['subject'],
                'html_body' => $data['body_html'],
                'text_body' => $data['body_text'] ?? null,
                'variables' => json_encode($validation['variables']),
                'validation_results' => json_encode($validation),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect("/email/templates/{$template}")->with('status', "Template updated (v{$newVersion}).");
    }

    public function destroy(int $template): RedirectResponse
    {
        DB::table('email_templates')->where('id', $template)->delete();

        return redirect('/email/templates')->with('status', 'Template deleted.');
    }

    public function duplicate(int $template): RedirectResponse
    {
        $row = DB::table('email_templates')->find($template);
        abort_unless($row, 404);

        $newId = DB::table('email_templates')->insertGetId([
            'name' => "{$row->name} (Copy)",
            'slug' => $this->uniqueSlug("{$row->name} (Copy)"),
            'event_key' => $row->event_key.'_copy_'.time(),
            'type' => $row->type,
            'subject' => $row->subject,
            'html_body' => $row->html_body,
            'text_body' => $row->text_body,
            'description' => $row->description,
            'variables' => $row->variables,
            'is_transactional' => $row->is_transactional,
            'is_active' => false,
            'is_default' => false,
            'version' => 1,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/templates/{$newId}/edit")->with('status', 'Template duplicated.');
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 2;
        while (DB::table('email_templates')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function blockLibrary(): array
    {
        return [
            'header' => [
                'name' => 'Header',
                'description' => 'Logo and top navigation bar',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a2e;">
  <tr>
    <td style="padding:20px 40px;text-align:center;">
      <a href="{{marketplace_url}}" style="color:#e94560;font-size:24px;font-weight:700;text-decoration:none;font-family:Arial,sans-serif;">NeoGiga</a>
    </td>
  </tr>
</table>
HTML,
            ],
            'hero' => [
                'name' => 'Hero Banner',
                'description' => 'Full-width hero section with heading and CTA',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f3460;">
  <tr>
    <td style="padding:40px;text-align:center;">
      <h1 style="color:#ffffff;font-size:28px;font-family:Arial,sans-serif;margin:0 0 16px;">Welcome to NeoGiga</h1>
      <p style="color:#cccccc;font-size:16px;font-family:Arial,sans-serif;margin:0 0 24px;">Your electronics engineering marketplace</p>
      <a href="{{marketplace_url}}" style="display:inline-block;padding:14px 32px;background:#e94560;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;border-radius:6px;font-family:Arial,sans-serif;">Get Started</a>
    </td>
  </tr>
</table>
HTML,
            ],
            'text_block' => [
                'name' => 'Text Block',
                'description' => 'Simple paragraph with heading',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:24px 40px;font-family:Arial,sans-serif;">
      <h2 style="color:#1a1a2e;font-size:20px;margin:0 0 12px;">Section Heading</h2>
      <p style="color:#555555;font-size:15px;line-height:1.6;margin:0;">Your content goes here. Use {{first_name}} to personalize with the recipient's name.</p>
    </td>
  </tr>
</table>
HTML,
            ],
            'product_card' => [
                'name' => 'Product Card',
                'description' => 'Product listing with image, name, and price',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:16px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
        <tr>
          <td style="width:120px;padding:16px;background:#f5f5f5;text-align:center;">
            <img src="{{product_image}}" alt="{{product_name}}" width="100" style="max-width:100px;height:auto;">
          </td>
          <td style="padding:16px;font-family:Arial,sans-serif;">
            <h3 style="color:#1a1a2e;font-size:16px;margin:0 0 8px;">{{product_name}}</h3>
            <p style="color:#e94560;font-size:18px;font-weight:700;margin:0;">{{product_price}}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML,
            ],
            'order_summary' => [
                'name' => 'Order Summary',
                'description' => 'Order details with items and total',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:24px 40px;">
      <h2 style="color:#1a1a2e;font-size:20px;margin:0 0 16px;font-family:Arial,sans-serif;">Order #{{order_number}}</h2>
      <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-family:Arial,sans-serif;">
        <tr style="background:#f5f5f5;">
          <td style="border:1px solid #e0e0e0;font-weight:600;">Item</td>
          <td style="border:1px solid #e0e0e0;font-weight:600;text-align:right;">Qty</td>
          <td style="border:1px solid #e0e0e0;font-weight:600;text-align:right;">Price</td>
        </tr>
        <tr>
          <td style="border:1px solid #e0e0e0;">{{product_name}}</td>
          <td style="border:1px solid #e0e0e0;text-align:right;">1</td>
          <td style="border:1px solid #e0e0e0;text-align:right;">{{order_total}}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML,
            ],
            'cta_button' => [
                'name' => 'CTA Button',
                'description' => 'Centered call-to-action button',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:24px 40px;text-align:center;">
      <a href="{{action_url}}" style="display:inline-block;padding:14px 40px;background:#e94560;color:#ffffff;font-size:16px;font-weight:600;text-decoration:none;border-radius:6px;font-family:Arial,sans-serif;">{{action_text}}</a>
    </td>
  </tr>
</table>
HTML,
            ],
            'divider' => [
                'name' => 'Divider',
                'description' => 'Horizontal line separator',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:8px 40px;">
      <hr style="border:none;border-top:1px solid #e0e0e0;margin:0;">
    </td>
  </tr>
</table>
HTML,
            ],
            'footer' => [
                'name' => 'Footer',
                'description' => 'Unsubscribe links and company info',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a2e;">
  <tr>
    <td style="padding:32px 40px;text-align:center;font-family:Arial,sans-serif;">
      <p style="color:#888888;font-size:13px;margin:0 0 8px;">NeoGiga — Global Electronics Engineering Marketplace</p>
      <p style="color:#888888;font-size:12px;margin:0 0 12px;">
        <a href="{{preferences_url}}" style="color:#e94560;text-decoration:none;">Email Preferences</a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="{{unsubscribe_url}}" style="color:#e94560;text-decoration:none;">Unsubscribe</a>
      </p>
      <p style="color:#666666;font-size:11px;margin:0;">&copy; {{current_year}} NeoGiga. All rights reserved.</p>
    </td>
  </tr>
</table>
HTML,
            ],
            'two_column' => [
                'name' => 'Two Column',
                'description' => 'Side-by-side content layout',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding:24px 40px;font-family:Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td width="48%" valign="top">
            <h3 style="color:#1a1a2e;font-size:16px;margin:0 0 8px;">Left Column</h3>
            <p style="color:#555555;font-size:14px;line-height:1.5;margin:0;">Left column content goes here.</p>
          </td>
          <td width="4%"></td>
          <td width="48%" valign="top">
            <h3 style="color:#1a1a2e;font-size:16px;margin:0 0 8px;">Right Column</h3>
            <p style="color:#555555;font-size:14px;line-height:1.5;margin:0;">Right column content goes here.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML,
            ],
            'spacer' => [
                'name' => 'Spacer',
                'description' => 'Vertical spacing between sections',
                'html' => <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0">
  <tr><td style="padding:16px 0;">&nbsp;</td></tr>
</table>
HTML,
            ],
        ];
    }
}
