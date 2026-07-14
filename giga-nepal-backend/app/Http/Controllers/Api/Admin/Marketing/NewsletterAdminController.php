<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Jobs\Marketing\SendNewsletterCampaignJob;
use App\Services\Marketing\EmailTemplateValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsletterAdminController extends Controller
{
    use ApiResponses;

    public function subscribers(Request $request): JsonResponse
    {
        return $this->success(DB::table('newsletter_subscribers')->paginate((int) $request->query('per_page', 25)));
    }

    public function templates(): JsonResponse
    {
        return $this->success(DB::table('newsletter_templates')->orderByDesc('id')->get());
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'subject' => 'required|string|max:190',
            'html_body' => 'nullable|string',
            'text_body' => 'nullable|string',
        ]);

        $validation = app(EmailTemplateValidator::class)->validate($data, true);
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        $id = DB::transaction(function () use ($data, $validation): int {
            $id = DB::table('newsletter_templates')->insertGetId($data + [
                'slug' => $this->uniqueSlug($data['name']),
                'variables' => json_encode($validation['variables']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('newsletter_template_versions')->insert([
                'newsletter_template_id' => $id, 'version' => 1, 'subject' => $data['subject'],
                'html_body' => $data['html_body'] ?? null, 'text_body' => $data['text_body'] ?? null,
                'variables' => json_encode($validation['variables']), 'validation_results' => json_encode($validation),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $id;
        });

        return $this->success(['id' => $id, 'validation' => $validation], 201);
    }

    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:190',
            'subject' => 'sometimes|string|max:190',
            'html_body' => 'nullable|string',
            'text_body' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $template = DB::table('newsletter_templates')->find($id);
        if (! $template) {
            return $this->error('Newsletter template not found.', 404);
        }
        $merged = array_merge((array) $template, $data);
        $validation = app(EmailTemplateValidator::class)->validate($merged, true);
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        DB::transaction(function () use ($id, $data, $merged, $validation): void {
            DB::table('newsletter_templates')->where('id', $id)->update($data + ['variables' => json_encode($validation['variables']), 'updated_at' => now()]);
            $version = ((int) DB::table('newsletter_template_versions')->where('newsletter_template_id', $id)->max('version')) + 1;
            DB::table('newsletter_template_versions')->insert([
                'newsletter_template_id' => $id, 'version' => $version, 'subject' => $merged['subject'],
                'html_body' => $merged['html_body'] ?? null, 'text_body' => $merged['text_body'] ?? null,
                'variables' => json_encode($validation['variables']), 'validation_results' => json_encode($validation),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return $this->success(['message' => 'Updated.', 'validation' => $validation]);
    }

    public function campaigns(): JsonResponse
    {
        return $this->success(DB::table('newsletter_campaigns')->orderByDesc('id')->get());
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'subject' => 'nullable|string|max:190',
            'newsletter_template_id' => 'nullable|integer',
            'targeting_rules' => 'array',
            'marketplace_id' => 'nullable|integer',
            'reply_to' => 'nullable|email|max:190',
        ]);

        $id = DB::table('newsletter_campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'newsletter_template_id' => $data['newsletter_template_id'] ?? null,
            'targeting_rules' => json_encode($data['targeting_rules'] ?? []),
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'internal_reference' => 'NEWS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'requires_approval' => true,
            'production_send_enabled' => false,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function preview(int $id): JsonResponse
    {
        return $this->success(DB::table('newsletter_campaigns')->find($id));
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => 'nullable|date']);
        $campaign = DB::table('newsletter_campaigns')->find($id);
        if (! $campaign) {
            return $this->error('Newsletter campaign not found.', 404);
        }
        if (($campaign->requires_approval ?? true) && ! $campaign->approved_at) {
            return $this->error('Newsletter approval is required before scheduling.', 422);
        }
        DB::table('newsletter_campaigns')->where('id', $id)->update([
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'updated_at' => now(),
        ]);

        return $this->success(['message' => 'Scheduled.']);
    }

    public function sendTest(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        SendNewsletterCampaignJob::dispatch(['campaign_id' => $id, 'test' => true, 'test_email' => mb_strtolower($data['email'])]);

        return $this->success(['status' => 'queued', 'queue' => config('marketing.email.queue'), 'test_recipient' => mb_strtolower($data['email'])], 202);
    }

    public function sendNow(int $id): JsonResponse
    {
        SendNewsletterCampaignJob::dispatch(['campaign_id' => $id]);

        return $this->success(['status' => 'queued', 'queue' => config('marketing.email.queue')], 202);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $campaign = DB::table('newsletter_campaigns')->find($id);
        if (! $campaign) {
            return $this->error('Newsletter campaign not found.', 404);
        }
        $template = $campaign->newsletter_template_id ? DB::table('newsletter_templates')->find($campaign->newsletter_template_id) : null;
        if (! $template) {
            return $this->error('An active newsletter template is required.', 422);
        }
        $validation = app(EmailTemplateValidator::class)->validate($template, true);
        if (! $validation['valid']) {
            return $this->error('Template validation failed: '.implode(', ', $validation['errors']), 422);
        }
        DB::table('newsletter_campaigns')->where('id', $id)->update([
            'status' => 'approved', 'approved_by' => $request->user()?->id, 'approved_at' => now(),
            'production_send_enabled' => $request->boolean('production_send_enabled', false), 'updated_at' => now(),
        ]);

        return $this->success(['message' => 'Newsletter approved.', 'production_send_enabled' => $request->boolean('production_send_enabled', false), 'validation' => $validation]);
    }

    public function pause(int $id): JsonResponse
    {
        $updated = DB::table('newsletter_campaigns')->where('id', $id)->whereNull('cancelled_at')->update(['status' => 'paused', 'paused_at' => now(), 'updated_at' => now()]);

        return $updated ? $this->success(['message' => 'Newsletter paused.']) : $this->error('Newsletter not found or already cancelled.', 404);
    }

    public function resume(int $id): JsonResponse
    {
        $campaign = DB::table('newsletter_campaigns')->find($id);
        if (! $campaign || $campaign->cancelled_at) {
            return $this->error('Newsletter not found or cancelled.', 404);
        }
        if (! $campaign->approved_at) {
            return $this->error('Newsletter approval is required.', 422);
        }
        DB::table('newsletter_campaigns')->where('id', $id)->update(['status' => $campaign->scheduled_at ? 'scheduled' : 'approved', 'paused_at' => null, 'updated_at' => now()]);

        return $this->success(['message' => 'Newsletter resumed without changing its frozen audience.']);
    }

    public function cancel(int $id): JsonResponse
    {
        $updated = DB::table('newsletter_campaigns')->where('id', $id)->whereNull('cancelled_at')->update(['status' => 'cancelled', 'cancelled_at' => now(), 'production_send_enabled' => false, 'updated_at' => now()]);
        if ($updated) {
            DB::table('email_messages')->where('newsletter_campaign_id', $id)->whereIn('status', ['queued', 'scheduled'])->update(['status' => 'cancelled', 'updated_at' => now()]);
        }

        return $updated ? $this->success(['message' => 'Newsletter cancelled.']) : $this->error('Newsletter not found or already cancelled.', 404);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'newsletter-template';
        $slug = $base;
        $suffix = 2;
        while (DB::table('newsletter_templates')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
