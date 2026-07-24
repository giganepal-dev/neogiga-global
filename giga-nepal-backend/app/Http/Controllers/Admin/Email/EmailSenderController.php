<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailSenderController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_senders_extension');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sender_name', 'ilike', "%{$search}%")
                    ->orWhere('sender_email', 'ilike', "%{$search}%");
            });
        }

        $senders = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.email.senders.index', compact('senders'));
    }

    public function create(): View
    {
        return view('admin.email.senders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_email' => ['required', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            DB::table('email_senders_extension')->where('is_default', true)->update(['is_default' => false]);
        }

        $senderId = DB::table('email_senders_extension')->insertGetId([
            'sender_name' => $data['sender_name'],
            'sender_email' => $data['sender_email'],
            'reply_to' => $data['reply_to'] ?? null,
            'is_verified' => false,
            'is_default' => $data['is_default'] ?? false,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/senders/{$senderId}")->with('status', 'Sender identity created.');
    }

    public function show(int $sender): View
    {
        $row = DB::table('email_senders_extension')->find($sender);
        abort_unless($row, 404);

        $campaignCount = DB::table('email_campaigns')->where('sender_id', $sender)->count();

        return view('admin.email.senders.show', compact('row', 'campaignCount'));
    }

    public function edit(int $sender): View
    {
        $row = DB::table('email_senders_extension')->find($sender);
        abort_unless($row, 404);

        return view('admin.email.senders.edit', compact('row'));
    }

    public function update(Request $request, int $sender): RedirectResponse
    {
        $row = DB::table('email_senders_extension')->find($sender);
        abort_unless($row, 404);

        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_email' => ['required', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            DB::table('email_senders_extension')->where('is_default', true)->update(['is_default' => false]);
        }

        DB::table('email_senders_extension')->where('id', $sender)->update([
            'sender_name' => $data['sender_name'],
            'sender_email' => $data['sender_email'],
            'reply_to' => $data['reply_to'] ?? null,
            'is_default' => $data['is_default'] ?? false,
            'updated_at' => now(),
        ]);

        return redirect("/email/senders/{$sender}")->with('status', 'Sender identity updated.');
    }

    public function destroy(int $sender): RedirectResponse
    {
        DB::table('email_senders_extension')->where('id', $sender)->delete();

        return redirect('/email/senders')->with('status', 'Sender identity deleted.');
    }

    public function verify(int $sender): RedirectResponse
    {
        $row = DB::table('email_senders_extension')->find($sender);
        abort_unless($row, 404);

        // In a real implementation, this would send a verification email
        DB::table('email_senders_extension')->where('id', $sender)->update([
            'is_verified' => true,
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/senders/{$sender}")->with('status', 'Sender verified.');
    }

    public function setDefault(int $sender): RedirectResponse
    {
        $row = DB::table('email_senders_extension')->find($sender);
        abort_unless($row, 404);

        DB::table('email_senders_extension')->where('is_default', true)->update(['is_default' => false]);
        DB::table('email_senders_extension')->where('id', $sender)->update([
            'is_default' => true,
            'updated_at' => now(),
        ]);

        return redirect("/email/senders/{$sender}")->with('status', 'Default sender updated.');
    }
}
