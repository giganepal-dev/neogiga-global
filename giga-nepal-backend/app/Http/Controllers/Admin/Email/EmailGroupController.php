<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailGroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_groups');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('group_type', $type);
        }

        $groups = $query->orderByDesc('created_at')->paginate(25);

        $types = DB::table('email_groups')->distinct()->pluck('group_type')->filter()->sort()->values();

        return view('admin.email.groups.index', compact('groups', 'types'));
    }

    public function create(): View
    {
        return view('admin.email.groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'group_type' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'max_emails_per_day' => ['nullable', 'integer', 'min:0'],
            'max_emails_per_month' => ['nullable', 'integer', 'min:0'],
        ]);

        $groupId = DB::table('email_groups')->insertGetId([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'group_type' => $data['group_type'] ?? 'manual',
            'country_code' => $data['country_code'] ?? null,
            'max_emails_per_day' => $data['max_emails_per_day'] ?? null,
            'max_emails_per_month' => $data['max_emails_per_month'] ?? null,
            'subscriber_count' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/groups/{$groupId}")->with('status', 'Group created.');
    }

    public function show(int $group): View
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        $subscribers = DB::table('email_group_subscriber')
            ->join('email_subscribers', 'email_subscribers.id', '=', 'email_group_subscriber.subscriber_id')
            ->where('email_group_subscriber.group_id', $group)
            ->select('email_subscribers.*', 'email_group_subscriber.is_primary', 'email_group_subscriber.assignment_source')
            ->orderByDesc('email_group_subscriber.created_at')
            ->paginate(25);

        return view('admin.email.groups.show', compact('row', 'subscribers'));
    }

    public function edit(int $group): View
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        return view('admin.email.groups.edit', compact('row'));
    }

    public function update(Request $request, int $group): RedirectResponse
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'group_type' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'max_emails_per_day' => ['nullable', 'integer', 'min:0'],
            'max_emails_per_month' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::table('email_groups')->where('id', $group)->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'group_type' => $data['group_type'] ?? $row->group_type,
            'country_code' => $data['country_code'] ?? null,
            'max_emails_per_day' => $data['max_emails_per_day'] ?? null,
            'max_emails_per_month' => $data['max_emails_per_month'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect("/email/groups/{$group}")->with('status', 'Group updated.');
    }

    public function destroy(int $group): RedirectResponse
    {
        DB::table('email_groups')->where('id', $group)->delete();

        return redirect('/email/groups')->with('status', 'Group deleted.');
    }

    public function addSubscribers(Request $request, int $group): RedirectResponse
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        $data = $request->validate([
            'subscriber_ids' => ['required', 'array'],
            'subscriber_ids.*' => ['integer', 'exists:email_subscribers,id'],
        ]);

        $added = 0;
        foreach ($data['subscriber_ids'] as $subscriberId) {
            $exists = DB::table('email_group_subscriber')
                ->where('subscriber_id', $subscriberId)
                ->where('group_id', $group)
                ->exists();

            if (! $exists) {
                DB::table('email_group_subscriber')->insert([
                    'subscriber_id' => $subscriberId,
                    'group_id' => $group,
                    'assignment_source' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $added++;
            }
        }

        DB::table('email_groups')->where('id', $group)->update([
            'subscriber_count' => DB::table('email_group_subscriber')->where('group_id', $group)->count(),
            'updated_at' => now(),
        ]);

        return redirect("/email/groups/{$group}")->with('status', "{$added} subscriber(s) added to group.");
    }

    public function removeSubscribers(Request $request, int $group): RedirectResponse
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        $data = $request->validate([
            'subscriber_ids' => ['required', 'array'],
            'subscriber_ids.*' => ['integer'],
        ]);

        DB::table('email_group_subscriber')
            ->where('group_id', $group)
            ->whereIn('subscriber_id', $data['subscriber_ids'])
            ->delete();

        DB::table('email_groups')->where('id', $group)->update([
            'subscriber_count' => DB::table('email_group_subscriber')->where('group_id', $group)->count(),
            'updated_at' => now(),
        ]);

        return redirect("/email/groups/{$group}")->with('status', 'Subscribers removed from group.');
    }

    public function export(int $group)
    {
        $row = DB::table('email_groups')->find($group);
        abort_unless($row, 404);

        $subscribers = DB::table('email_group_subscriber')
            ->join('email_subscribers', 'email_subscribers.id', '=', 'email_group_subscriber.subscriber_id')
            ->where('email_group_subscriber.group_id', $group)
            ->select('email_subscribers.email', 'email_subscribers.first_name', 'email_subscribers.last_name', 'email_subscribers.status')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"group-{$row->slug}-subscribers.csv\"",
        ];

        $callback = function () use ($subscribers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'First Name', 'Last Name', 'Status']);
            foreach ($subscribers as $s) {
                fputcsv($handle, [$s->email, $s->first_name, $s->last_name, $s->status]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
