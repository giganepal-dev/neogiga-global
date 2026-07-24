<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailSegmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('email_segments');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('segment_type', $type);
        }

        $segments = $query->orderByDesc('created_at')->paginate(25);

        $types = DB::table('email_segments')->distinct()->pluck('segment_type')->filter()->sort()->values();

        return view('admin.email.segments.index', compact('segments', 'types'));
    }

    public function create(): View
    {
        $fields = [
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'status' => 'Status',
            'source' => 'Source',
            'country' => 'Country',
            'company' => 'Company',
            'engagement_score' => 'Engagement Score',
            'total_sent' => 'Total Sent',
            'total_opened' => 'Total Opened',
            'total_clicked' => 'Total Clicked',
            'created_at' => 'Created At',
        ];

        $operators = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'greater_than', 'less_than', 'in', 'not_in', 'is_empty', 'is_not_empty'];

        return view('admin.email.segments.create', compact('fields', 'operators'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'segment_type' => ['nullable', 'string', 'max:50'],
            'rules' => ['nullable', 'array'],
            'rules.*.field' => ['required_with:rules', 'string', 'max:100'],
            'rules.*.operator' => ['required_with:rules', 'string', 'max:50'],
            'rules.*.value' => ['nullable', 'string', 'max:255'],
            'rules.*.boolean_operator' => ['nullable', 'string', 'in:and,or'],
        ]);

        $segmentId = DB::table('email_segments')->insertGetId([
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'segment_type' => $data['segment_type'] ?? 'dynamic',
            'rules' => json_encode($data['rules'] ?? []),
            'subscriber_count' => 0,
            'last_calculated_at' => null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! empty($data['rules'])) {
            foreach ($data['rules'] as $index => $rule) {
                DB::table('email_segment_rules')->insert([
                    'segment_id' => $segmentId,
                    'field' => $rule['field'],
                    'operator' => $rule['operator'],
                    'value' => $rule['value'] ?? null,
                    'boolean_operator' => $index === 0 ? null : ($rule['boolean_operator'] ?? 'and'),
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect("/email/segments/{$segmentId}")->with('status', 'Segment created.');
    }

    public function show(int $segment): View
    {
        $row = DB::table('email_segments')->find($segment);
        abort_unless($row, 404);

        $rules = DB::table('email_segment_rules')
            ->where('segment_id', $segment)
            ->orderBy('sort_order')
            ->get();

        $previewSubscribers = $this->matchSubscribers($rules)->limit(20)->get();

        return view('admin.email.segments.show', compact('row', 'rules', 'previewSubscribers'));
    }

    public function edit(int $segment): View
    {
        $row = DB::table('email_segments')->find($segment);
        abort_unless($row, 404);

        $rules = DB::table('email_segment_rules')
            ->where('segment_id', $segment)
            ->orderBy('sort_order')
            ->get();

        $fields = [
            'email' => 'Email', 'first_name' => 'First Name', 'last_name' => 'Last Name',
            'status' => 'Status', 'source' => 'Source', 'country' => 'Country',
            'company' => 'Company', 'engagement_score' => 'Engagement Score',
            'total_sent' => 'Total Sent', 'total_opened' => 'Total Opened',
            'total_clicked' => 'Total Clicked', 'created_at' => 'Created At',
        ];
        $operators = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with', 'greater_than', 'less_than', 'in', 'not_in', 'is_empty', 'is_not_empty'];

        return view('admin.email.segments.edit', compact('row', 'rules', 'fields', 'operators'));
    }

    public function update(Request $request, int $segment): RedirectResponse
    {
        $row = DB::table('email_segments')->find($segment);
        abort_unless($row, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:500'],
            'segment_type' => ['nullable', 'string', 'max:50'],
            'rules' => ['nullable', 'array'],
            'rules.*.field' => ['required_with:rules', 'string', 'max:100'],
            'rules.*.operator' => ['required_with:rules', 'string', 'max:50'],
            'rules.*.value' => ['nullable', 'string', 'max:255'],
            'rules.*.boolean_operator' => ['nullable', 'string', 'in:and,or'],
        ]);

        DB::transaction(function () use ($data, $segment): void {
            DB::table('email_segments')->where('id', $segment)->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'segment_type' => $data['segment_type'] ?? 'dynamic',
                'rules' => json_encode($data['rules'] ?? []),
                'updated_at' => now(),
            ]);

            DB::table('email_segment_rules')->where('segment_id', $segment)->delete();
            if (! empty($data['rules'])) {
                foreach ($data['rules'] as $index => $rule) {
                    DB::table('email_segment_rules')->insert([
                        'segment_id' => $segment,
                        'field' => $rule['field'],
                        'operator' => $rule['operator'],
                        'value' => $rule['value'] ?? null,
                        'boolean_operator' => $index === 0 ? null : ($rule['boolean_operator'] ?? 'and'),
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect("/email/segments/{$segment}")->with('status', 'Segment updated.');
    }

    public function destroy(int $segment): RedirectResponse
    {
        DB::table('email_segments')->where('id', $segment)->delete();

        return redirect('/email/segments')->with('status', 'Segment deleted.');
    }

    public function recalculate(int $segment): RedirectResponse
    {
        $row = DB::table('email_segments')->find($segment);
        abort_unless($row, 404);

        $rules = DB::table('email_segment_rules')
            ->where('segment_id', $segment)
            ->orderBy('sort_order')
            ->get();

        $count = $this->matchSubscribers($rules)->count();

        DB::table('email_segments')->where('id', $segment)->update([
            'subscriber_count' => $count,
            'last_calculated_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect("/email/segments/{$segment}")->with('status', "Segment recalculated: {$count} subscribers matched.");
    }

    public function preview(int $segment)
    {
        $row = DB::table('email_segments')->find($segment);
        abort_unless($row, 404);

        $rules = DB::table('email_segment_rules')
            ->where('segment_id', $segment)
            ->orderBy('sort_order')
            ->get();

        $subscribers = $this->matchSubscribers($rules)
            ->select('email', 'first_name', 'last_name', 'status', 'engagement_score')
            ->limit(50)
            ->get();

        return response()->json(['subscribers' => $subscribers, 'total' => $this->matchSubscribers($rules)->count()]);
    }

    private function matchSubscribers($rules)
    {
        return DB::table('email_subscribers')->where(function ($q) use ($rules) {
            $first = true;
            foreach ($rules as $rule) {
                $operator = $first ? 'where' : ($rule->boolean_operator === 'or' ? 'orWhere' : 'where');
                $q->{$operator}(function ($sub) use ($rule) {
                    match ($rule->operator) {
                        'equals' => $sub->where($rule->field, '=', $rule->value),
                        'not_equals' => $sub->where($rule->field, '!=', $rule->value),
                        'contains' => $sub->where($rule->field, 'ilike', "%{$rule->value}%"),
                        'not_contains' => $sub->where($rule->field, 'not ilike', "%{$rule->value}%"),
                        'starts_with' => $sub->where($rule->field, 'ilike', "{$rule->value}%"),
                        'ends_with' => $sub->where($rule->field, 'ilike', "%{$rule->value}"),
                        'greater_than' => $sub->where($rule->field, '>', $rule->value),
                        'less_than' => $sub->where($rule->field, '<', $rule->value),
                        'in' => $sub->whereIn($rule->field, explode(',', $rule->value)),
                        'not_in' => $sub->whereNotIn($rule->field, explode(',', $rule->value)),
                        'is_empty' => $sub->whereNull($rule->field),
                        'is_not_empty' => $sub->whereNotNull($rule->field),
                        default => null,
                    };
                });
                $first = false;
            }
        });
    }
}
