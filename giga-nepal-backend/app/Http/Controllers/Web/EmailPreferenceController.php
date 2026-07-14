<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Marketing\EmailPreferenceTokenService;
use App\Services\Marketing\EmailSuppressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailPreferenceController extends Controller
{
    public function __construct(private EmailPreferenceTokenService $tokens, private EmailSuppressionService $suppressions) {}

    public function unsubscribe(string $token): View
    {
        $record = $this->token($token);

        return view('frontend.email-preferences.unsubscribe', [
            'record' => $record,
            'token' => $token,
            'maskedEmail' => $this->tokens->mask((string) $record->email),
            'confirmed' => $record->confirmed_at !== null,
            'canonical' => url('/email/unsubscribe/'.$token),
            'robots' => 'noindex,nofollow,noarchive',
        ]);
    }

    public function confirmUnsubscribe(Request $request, string $token): RedirectResponse
    {
        $record = $this->token($token);
        $validated = $request->validate([
            'confirmation' => ['required', 'accepted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($record, $request, $validated): void {
            $email = mb_strtolower(trim((string) $record->email));
            $now = now();
            DB::table('unsubscribes')->where('id', $record->id)->update([
                'reason' => $validated['reason'] ?? 'recipient_request',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'confirmed_at' => $now,
                'unsubscribed_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('email_preferences')->updateOrInsert(['email' => $email], [
                'all_marketing_opt_out' => true,
                'updated_by_recipient_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            DB::table('email_subscriptions')->whereRaw('LOWER(email) = ?', [$email])->update([
                'status' => 'unsubscribed', 'unsubscribed_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('newsletter_subscribers')->whereRaw('LOWER(email) = ?', [$email])->update([
                'status' => 'unsubscribed', 'consent_status' => 'unsubscribed', 'unsubscribed_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('customer_profiles')->whereRaw('LOWER(email) = ?', [$email])->update([
                'marketing_opt_in' => false, 'marketing_status' => 'unsubscribed', 'updated_at' => $now,
            ]);
            DB::table('customer_consents')->insert([
                'customer_profile_id' => DB::table('customer_profiles')->whereRaw('LOWER(email) = ?', [$email])->value('id'),
                'email' => $email,
                'channel' => 'email',
                'purpose' => 'marketing',
                'granted' => false,
                'status' => 'opted_out',
                'source' => 'recipient_preference_center',
                'evidence' => json_encode(['unsubscribe_id' => $record->id, 'scope' => $record->scope]),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'revoked_at' => $now,
                'effective_at' => $now,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->suppressions->suppress($email, 'unsubscribe', 'marketing', ['source' => 'recipient_preference_center']);
        });

        return redirect()->route('email.unsubscribe', ['token' => $token])->with('status', 'Your marketing email unsubscribe request is confirmed. Essential order and account emails are unaffected.');
    }

    public function preferences(string $token): View
    {
        $record = $this->token($token);
        $email = mb_strtolower(trim((string) $record->email));
        $preferences = DB::table('email_preferences')->whereRaw('LOWER(email) = ?', [$email])->first();
        $categories = DB::table('newsletter_categories')->where('is_active', true)->orderBy('name')->get();

        return view('frontend.email-preferences.preferences', [
            'record' => $record,
            'preferences' => $preferences,
            'categories' => $categories,
            'selectedCategories' => json_decode((string) ($preferences->categories ?? '[]'), true) ?: [],
            'token' => $token,
            'maskedEmail' => $this->tokens->mask($email),
            'canonical' => url('/email/preferences/'.$token),
            'robots' => 'noindex,nofollow,noarchive',
        ]);
    }

    public function updatePreferences(Request $request, string $token): RedirectResponse
    {
        $record = $this->token($token);
        $validated = $request->validate([
            'categories' => ['nullable', 'array', 'max:50'],
            'categories.*' => ['integer'],
            'preferred_language' => ['required', 'string', 'max:12'],
            'preferred_format' => ['required', 'in:html,text'],
            'frequency' => ['required', 'in:standard,weekly,monthly'],
            'all_marketing_opt_out' => ['nullable', 'boolean'],
        ]);
        $selected = array_map('intval', $validated['categories'] ?? []);
        $allowed = DB::table('newsletter_categories')->where('is_active', true)->whereIn('id', $selected)->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($allowed);
        $email = mb_strtolower(trim((string) $record->email));
        $now = now();

        DB::table('email_preferences')->updateOrInsert(['email' => $email], [
            'categories' => json_encode($allowed),
            'preferred_language' => $validated['preferred_language'],
            'preferred_format' => $validated['preferred_format'],
            'frequency' => $validated['frequency'],
            'all_marketing_opt_out' => (bool) ($validated['all_marketing_opt_out'] ?? false),
            'updated_by_recipient_at' => $now,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        return redirect()->route('email.preferences', ['token' => $token])->with('status', 'Your email preferences have been saved.');
    }

    private function token(string $token): object
    {
        abort_unless(strlen($token) === 64 && ctype_alnum($token), 404);
        $record = $this->tokens->find($token);
        abort_unless($record && $record->email, 404);

        return $record;
    }
}
