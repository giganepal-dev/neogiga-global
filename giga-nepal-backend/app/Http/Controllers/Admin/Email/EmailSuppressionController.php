<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailSuppressionController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_suppressions_extension');

        if ($search = $request->input('search')) {
            $query->where('email', 'ilike', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $suppressions = $query->orderByDesc('created_at')->paginate(25);

        $stats = [
            'total' => DB::table('email_suppressions_extension')->count(),
            'global' => DB::table('email_suppressions_extension')->where('is_global', true)->count(),
            'permanent' => DB::table('email_suppressions_extension')->where('is_permanent', true)->count(),
            'bounces' => DB::table('email_suppressions_extension')->where('reason', 'bounce')->count(),
            'complaints' => DB::table('email_suppressions_extension')->where('reason', 'complaint')->count(),
        ];

        return view('admin.email.suppressions.index', compact('suppressions', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'is_global' => ['nullable', 'boolean'],
            'is_permanent' => ['nullable', 'boolean'],
        ]);

        DB::table('email_suppressions_extension')->insert([
            'email' => $data['email'],
            'reason' => $data['reason'] ?? 'manual',
            'source' => $data['source'] ?? 'admin',
            'is_global' => $data['is_global'] ?? true,
            'is_permanent' => $data['is_permanent'] ?? false,
            'suppressed_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/email/suppressions')->with('status', 'Email suppressed.');
    }

    public function destroy(int $suppression): RedirectResponse
    {
        DB::table('email_suppressions_extension')->where('id', $suppression)->delete();

        return redirect('/email/suppressions')->with('status', 'Suppression removed.');
    }

    public function bulkRemove(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'suppression_ids' => ['required', 'array'],
            'suppression_ids.*' => ['integer'],
        ]);

        DB::table('email_suppressions_extension')
            ->whereIn('id', $data['suppression_ids'])
            ->delete();

        return redirect('/email/suppressions')->with('status', count($data['suppression_ids']).' suppression(s) removed.');
    }

    public function export(Request $request)
    {
        $suppressions = DB::table('email_suppressions_extension')
            ->select('email', 'reason', 'source', 'is_global', 'is_permanent', 'created_at')
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suppressions-'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($suppressions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'Reason', 'Source', 'Global', 'Permanent', 'Created At']);
            foreach ($suppressions as $s) {
                fputcsv($handle, [$s->email, $s->reason, $s->source, $s->is_global ? 'Yes' : 'No', $s->is_permanent ? 'Yes' : 'No', $s->created_at]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
