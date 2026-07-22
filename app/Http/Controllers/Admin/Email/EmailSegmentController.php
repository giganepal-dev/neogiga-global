<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSegment;
use App\Models\EmailSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailSegmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:email.segments.manage']);
    }

    public function index(Request $request)
    {
        $segments = EmailSegment::withCount('subscribers')->latest()->paginate(25);
        return view('admin.email.segments.index', compact('segments'));
    }

    public function create()
    {
        return view('admin.email.segments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:email_segments,name',
            'description' => 'nullable|string',
            'filter_type' => 'required|in:static,dynamic',
            'filter_criteria' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $segment = EmailSegment::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'filter_type' => $validated['filter_type'],
            'filter_criteria' => $validated['filter_criteria'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        // If static segment, attach subscribers
        if ($validated['filter_type'] === 'static' && $request->filled('subscriber_ids')) {
            $segment->subscribers()->attach($request->subscriber_ids);
        }

        return redirect()->route('admin.email.segments.show', $segment)
            ->with('success', 'Segment created successfully.');
    }

    public function show(EmailSegment $segment)
    {
        $segment->load(['creator', 'subscribers' => fn($q) => $q->latest()->limit(50)]);
        
        // Recalculate dynamic segment if needed
        if ($segment->filter_type === 'dynamic') {
            $count = $this->calculateDynamicSegmentCount($segment);
        } else {
            $count = $segment->subscribers()->count();
        }

        return view('admin.email.segments.show', compact('segment', 'count'));
    }

    public function edit(EmailSegment $segment)
    {
        return view('admin.email.segments.edit', compact('segment'));
    }

    public function update(Request $request, EmailSegment $segment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:email_segments,name,'.$segment->id,
            'description' => 'nullable|string',
            'filter_type' => 'required|in:static,dynamic',
            'filter_criteria' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $segment->update($validated);

        // Update static segment subscribers
        if ($validated['filter_type'] === 'static' && $request->filled('subscriber_ids')) {
            $segment->subscribers()->sync($request->subscriber_ids);
        }

        return redirect()->route('admin.email.segments.show', $segment)
            ->with('success', 'Segment updated successfully.');
    }

    public function destroy(EmailSegment $segment)
    {
        $segment->delete();

        return redirect()->route('admin.email.segments.index')
            ->with('success', 'Segment deleted successfully.');
    }

    public function recalculate(EmailSegment $segment)
    {
        if ($segment->filter_type !== 'dynamic') {
            return back()->with('error', 'Only dynamic segments can be recalculated.');
        }

        $count = $this->calculateDynamicSegmentCount($segment);

        return back()->with('success', "Segment recalculated. Found {$count} matching subscribers.");
    }

    private function calculateDynamicSegmentCount(EmailSegment $segment): int
    {
        $criteria = $segment->filter_criteria ?? [];
        $query = EmailSubscriber::query();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator'])) {
                    switch ($value['operator']) {
                        case 'equals':
                            $query->where($field, $value['value']);
                            break;
                        case 'not_equals':
                            $query->where($field, '!=', $value['value']);
                            break;
                        case 'contains':
                            $query->where($field, 'like', "%{$value['value']}%");
                            break;
                        case 'greater_than':
                            $query->where($field, '>', $value['value']);
                            break;
                        case 'less_than':
                            $query->where($field, '<', $value['value']);
                            break;
                        case 'in':
                            $query->whereIn($field, $value['value']);
                            break;
                        case 'not_in':
                            $query->whereNotIn($field, $value['value']);
                            break;
                    }
                }
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    public function preview(EmailSegment $segment)
    {
        if ($segment->filter_type === 'dynamic') {
            $criteria = $segment->filter_criteria ?? [];
            $query = EmailSubscriber::query();

            foreach ($criteria as $field => $value) {
                if (is_array($value) && isset($value['operator'])) {
                    switch ($value['operator']) {
                        case 'equals':
                            $query->where($field, $value['value']);
                            break;
                        case 'contains':
                            $query->where($field, 'like', "%{$value['value']}%");
                            break;
                        case 'greater_than':
                            $query->where($field, '>', $value['value']);
                            break;
                        case 'less_than':
                            $query->where($field, '<', $value['value']);
                            break;
                        case 'in':
                            $query->whereIn($field, $value['value']);
                            break;
                    }
                }
            }

            $subscribers = $query->latest()->limit(100)->get();
        } else {
            $subscribers = $segment->subscribers()->latest()->limit(100)->get();
        }

        return view('admin.email.segments.preview', compact('segment', 'subscribers'));
    }
}
