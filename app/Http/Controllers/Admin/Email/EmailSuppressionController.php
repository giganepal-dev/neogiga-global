<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSuppression;
use App\Models\EmailSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class EmailSuppressionController extends Controller
{
    /**
     * Display suppression list
     */
    public function index(Request $request)
    {
        $query = EmailSuppression::with(['subscriber', 'campaign'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Search by email
        if ($request->filled('email')) {
            $query->where('email_normalized', 'like', '%' . strtolower(trim($request->email)) . '%');
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $suppressions = $query->paginate(50);

        $stats = [
            'total' => EmailSuppression::count(),
            'bounced' => EmailSuppression::where('status', 'bounced')->count(),
            'complained' => EmailSuppression::where('status', 'complained')->count(),
            'unsubscribed' => EmailSuppression::where('status', 'unsubscribed')->count(),
            'manual' => EmailSuppression::where('type', 'manual')->count(),
        ];

        return view('admin.email.suppressions.index', compact('suppressions', 'stats'));
    }

    /**
     * Manually suppress an email
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:bounce,complaint,unsubscribe,manual',
            'status' => 'required|in:bounced,complained,unsubscribed,suppressed',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Find subscriber if exists
                $subscriber = EmailSubscriber::where('email_normalized', strtolower(trim($validated['email'])))->first();

                $suppression = EmailSuppression::create([
                    'email' => $validated['email'],
                    'email_normalized' => strtolower(trim($validated['email'])),
                    'type' => $validated['type'],
                    'status' => $validated['status'],
                    'reason' => $validated['reason'] ?? null,
                    'source' => 'admin',
                    'email_subscriber_id' => $subscriber?->id,
                    'expires_at' => $validated['expires_at'] ?? null,
                ]);

                // Update subscriber status if found
                if ($subscriber && in_array($validated['status'], ['bounced', 'complained', 'unsubscribed', 'suppressed'])) {
                    $subscriber->update([
                        'status' => $validated['status'],
                    ]);
                }

                return $suppression;
            });

            return back()->with('success', 'Email suppressed successfully');

        } catch (Exception $e) {
            return back()->with('error', 'Failed to suppress email: ' . $e->getMessage());
        }
    }

    /**
     * Remove suppression
     */
    public function destroy(int $id)
    {
        try {
            $suppression = EmailSuppression::findOrFail($id);
            
            // Don't allow removing hard bounces or complaints without admin override
            if (in_array($suppression->status, ['bounced', 'complained'])) {
                return back()->with('warning', 'Cannot automatically remove bounce/complaint suppression. Contact system administrator.');
            }

            $suppression->delete();

            // Reactivate subscriber if exists
            if ($suppression->subscriber) {
                $suppression->subscriber->update([
                    'status' => 'subscribed',
                ]);
            }

            return back()->with('success', 'Suppression removed successfully');

        } catch (Exception $e) {
            return back()->with('error', 'Failed to remove suppression: ' . $e->getMessage());
        }
    }

    /**
     * Bulk remove suppressions
     */
    public function bulkRemove(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:email_suppressions,id',
        ]);

        $removed = 0;
        $failed = 0;

        foreach ($validated['ids'] as $id) {
            try {
                $suppression = EmailSuppression::find($id);
                
                if ($suppression && !in_array($suppression->status, ['bounced', 'complained'])) {
                    if ($suppression->subscriber) {
                        $suppression->subscriber->update(['status' => 'subscribed']);
                    }
                    $suppression->delete();
                    $removed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }

        return back()->with('success', "Removed {$removed} suppressions. {$failed} could not be removed (bounce/complaint requires admin override).");
    }

    /**
     * Export suppressions
     */
    public function export(Request $request)
    {
        // Implementation for CSV export
        return response()->json([
            'message' => 'Export functionality - implement based on requirements',
        ]);
    }
}
