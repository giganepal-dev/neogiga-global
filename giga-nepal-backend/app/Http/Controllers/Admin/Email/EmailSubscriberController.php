<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_subscribers');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                    ->orWhere('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        $subscribers = $query->orderByDesc('created_at')->paginate(25);

        $stats = [
            'total' => DB::table('email_subscribers')->count(),
            'active' => DB::table('email_subscribers')->where('status', 'active')->count(),
            'unsubscribed' => DB::table('email_subscribers')->where('status', 'unsubscribed')->count(),
            'bounced' => DB::table('email_subscribers')->where('status', 'bounced')->count(),
        ];

        return view('admin.email.subscribers.index', compact('subscribers', 'stats'));
    }

    public function create(): View
    {
        $groups = DB::table('email_groups')->orderBy('name')->get();

        return view('admin.email.subscribers.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:email_subscribers,email'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:200'],
            'country' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'in:active,unsubscribed,bounced,complained'],
            'source' => ['nullable', 'string', 'max:50'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:email_groups,id'],
        ]);

        $id = DB::transaction(function () use ($data): int {
            $subscriberId = DB::table('email_subscribers')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'email' => $data['email'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'country' => $data['country'] ?? null,
                'status' => $data['status'] ?? 'active',
                'source' => $data['source'] ?? 'manual',
                'engagement_score' => 0,
                'total_sent' => 0,
                'total_opened' => 0,
                'total_clicked' => 0,
                'consent_given_at' => now(),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! empty($data['groups'])) {
                foreach ($data['groups'] as $groupId) {
                    DB::table('email_group_subscriber')->insert([
                        'subscriber_id' => $subscriberId,
                        'group_id' => $groupId,
                        'assignment_source' => 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return $subscriberId;
        });

        return redirect("/email/subscribers/{$id}")->with('status', 'Subscriber created.');
    }

    public function show(int $subscriber): View
    {
        $row = DB::table('email_subscribers')->find($subscriber);
        abort_unless($row, 404);

        $groups = DB::table('email_group_subscriber')
            ->join('email_groups', 'email_groups.id', '=', 'email_group_subscriber.group_id')
            ->where('email_group_subscriber.subscriber_id', $subscriber)
            ->select('email_groups.*', 'email_group_subscriber.is_primary')
            ->get();

        $tags = DB::table('email_subscriber_tags')
            ->join('email_tags', 'email_tags.id', '=', 'email_subscriber_tags.tag_id')
            ->where('email_subscriber_tags.subscriber_id', $subscriber)
            ->select('email_tags.*')
            ->get();

        $deliveryLogs = DB::table('email_delivery_logs')
            ->where('subscriber_id', $subscriber)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.email.subscribers.show', compact('row', 'groups', 'tags', 'deliveryLogs'));
    }

    public function edit(int $subscriber): View
    {
        $row = DB::table('email_subscribers')->find($subscriber);
        abort_unless($row, 404);

        $groups = DB::table('email_groups')->orderBy('name')->get();

        $assignedGroupIds = DB::table('email_group_subscriber')
            ->where('subscriber_id', $subscriber)
            ->pluck('group_id')
            ->toArray();

        return view('admin.email.subscribers.edit', compact('row', 'groups', 'assignedGroupIds'));
    }

    public function update(Request $request, int $subscriber): RedirectResponse
    {
        $row = DB::table('email_subscribers')->find($subscriber);
        abort_unless($row, 404);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:200'],
            'country' => ['nullable', 'string', 'max:2'],
            'status' => ['required', 'string', 'in:active,unsubscribed,bounced,complained'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:email_groups,id'],
        ]);

        DB::transaction(function () use ($data, $subscriber): void {
            DB::table('email_subscribers')->where('id', $subscriber)->update([
                'email' => $data['email'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'country' => $data['country'] ?? null,
                'status' => $data['status'],
                'updated_at' => now(),
            ]);

            DB::table('email_group_subscriber')->where('subscriber_id', $subscriber)->delete();
            if (! empty($data['groups'])) {
                foreach ($data['groups'] as $groupId) {
                    DB::table('email_group_subscriber')->insert([
                        'subscriber_id' => $subscriber,
                        'group_id' => $groupId,
                        'assignment_source' => 'manual',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect("/email/subscribers/{$subscriber}")->with('status', 'Subscriber updated.');
    }

    public function destroy(int $subscriber): RedirectResponse
    {
        DB::table('email_subscribers')->where('id', $subscriber)->delete();

        return redirect('/email/subscribers')->with('status', 'Subscriber deleted.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'subscriber_ids' => ['required', 'array'],
            'subscriber_ids.*' => ['integer'],
        ]);

        $ids = $data['subscriber_ids'];

        match ($data['action']) {
            'activate' => DB::table('email_subscribers')->whereIn('id', $ids)->update(['status' => 'active', 'updated_at' => now()]),
            'deactivate' => DB::table('email_subscribers')->whereIn('id', $ids)->update(['status' => 'unsubscribed', 'updated_at' => now()]),
            'delete' => DB::table('email_subscribers')->whereIn('id', $ids)->delete(),
        };

        return redirect('/email/subscribers')->with('status', ucfirst($data['action']).': '.count($ids).' subscribers processed.');
    }

    public function export(Request $request)
    {
        $query = DB::table('email_subscribers');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $subscribers = $query->select('email', 'first_name', 'last_name', 'phone', 'company', 'country', 'status', 'source', 'created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscribers-'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($subscribers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'First Name', 'Last Name', 'Phone', 'Company', 'Country', 'Status', 'Source', 'Created At']);
            foreach ($subscribers as $s) {
                fputcsv($handle, [$s->email, $s->first_name, $s->last_name, $s->phone, $s->company, $s->country, $s->status, $s->source, $s->created_at]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
