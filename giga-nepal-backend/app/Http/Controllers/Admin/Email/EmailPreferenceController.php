<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailPreferenceController extends Controller
{
    public function show(string $token): View
    {
        $subscriber = DB::table('email_subscribers')
            ->where('preference_token', $token)
            ->orWhere('uuid', $token)
            ->first();

        abort_unless($subscriber, 404);

        $preferences = DB::table('email_preferences')
            ->where('subscriber_id', $subscriber->id)
            ->first();

        $groups = DB::table('email_group_subscriber')
            ->join('email_groups', 'email_groups.id', '=', 'email_group_subscriber.group_id')
            ->where('email_group_subscriber.subscriber_id', $subscriber->id)
            ->select('email_groups.id', 'email_groups.name', 'email_groups.description')
            ->get();

        $allGroups = DB::table('email_groups')
            ->orderBy('name')
            ->get();

        return view('email.preference', compact('subscriber', 'preferences', 'groups', 'allGroups', 'token'));
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $subscriber = DB::table('email_subscribers')
            ->where('preference_token', $token)
            ->orWhere('uuid', $token)
            ->first();

        abort_unless($subscriber, 404);

        $data = $request->validate([
            'email_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,never'],
            'marketing_emails' => ['nullable', 'boolean'],
            'product_updates' => ['nullable', 'boolean'],
            'newsletter' => ['nullable', 'boolean'],
            'promotions' => ['nullable', 'boolean'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:email_groups,id'],
        ]);

        DB::table('email_preferences')->updateOrInsert(
            ['subscriber_id' => $subscriber->id],
            [
                'email_frequency' => $data['email_frequency'] ?? 'weekly',
                'marketing_emails' => $data['marketing_emails'] ?? true,
                'product_updates' => $data['product_updates'] ?? true,
                'newsletter' => $data['newsletter'] ?? true,
                'promotions' => $data['promotions'] ?? true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('email_group_subscriber')->where('subscriber_id', $subscriber->id)->delete();
        if (! empty($data['groups'])) {
            foreach ($data['groups'] as $groupId) {
                DB::table('email_group_subscriber')->insert([
                    'subscriber_id' => $subscriber->id,
                    'group_id' => $groupId,
                    'assignment_source' => 'preference_center',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect("/email/preference/{$token}")->with('status', 'Preferences updated successfully.');
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = DB::table('email_subscribers')
            ->where('unsubscribe_token', $token)
            ->orWhere('uuid', $token)
            ->first();

        abort_unless($subscriber, 404);

        DB::table('email_subscribers')->where('id', $subscriber->id)->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('email_consent_logs')->insert([
            'subscriber_id' => $subscriber->id,
            'action' => 'unsubscribed',
            'source' => 'unsubscribe_link',
            'ip_address' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subscriber = DB::table('email_subscribers')->find($subscriber->id);

        return view('email.preference', [
            'subscriber' => $subscriber,
            'unsubscribed' => true,
            'token' => $token,
        ]);
    }
}
