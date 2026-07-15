<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcbAdminController extends Controller
{
    private const STATUSES = [
        'draft', 'requirements_pending', 'design_requested', 'design_in_progress',
        'design_review', 'design_approved', 'files_ready', 'quote_pending', 'quoted',
        'awaiting_approval', 'ordered', 'manufacturing', 'inspection', 'shipped',
        'completed', 'on_hold', 'cancelled',
    ];

    public function index(Request $request): View
    {
        $query = PcbProject::query()
            ->with(['user:id,name,email', 'assignedEngineer:id,name'])
            ->withCount(['files', 'quoteConfigurations']);

        $query->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')));
        $query->when($request->filled('q'), function ($builder) use ($request) {
            $term = mb_substr((string) $request->query('q'), 0, 120);
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $builder->where(fn ($search) => $search
                ->where('name', $operator, "%{$term}%")
                ->orWhere('code', $operator, "%{$term}%"));
        });

        return view('admin.pcb.index', [
            'projects' => $query->latest()->paginate(25)->withQueryString(),
            'statuses' => self::STATUSES,
            'stats' => [
                'total' => PcbProject::count(),
                'review' => PcbProject::whereIn('status', ['requirements_pending', 'files_ready', 'quote_pending', 'design_review'])->count(),
                'quoted' => PcbProject::whereIn('status', ['quoted', 'awaiting_approval'])->count(),
                'production' => PcbProject::whereIn('status', ['ordered', 'manufacturing', 'inspection', 'shipped'])->count(),
            ],
        ]);
    }

    public function show(PcbProject $project): View
    {
        $project->load([
            'user:id,name,email',
            'assignedEngineer:id,name,email',
            'files' => fn ($query) => $query->with('scanResults')->latest(),
            'gerberAnalysisRuns.detectedLayers',
            'gerberAnalysisRuns.warnings',
            'quoteConfigurations' => fn ($query) => $query->with(['lineItems', 'order'])->latest(),
            'activityLogs' => fn ($query) => $query->with('user:id,name')->latest()->limit(50),
        ]);

        return view('admin.pcb.show', [
            'project' => $project,
            'statuses' => self::STATUSES,
        ]);
    }

    public function status(Request $request, PcbProject $project): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'note' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $previous = $project->status;
        $project->update(['status' => $data['status']]);
        $project->activityLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'admin_status_changed',
            'description' => $data['note'],
            'metadata' => ['from' => $previous, 'to' => $data['status']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'PCB project status updated.');
    }

    public function quote(Request $request, PcbProject $project, PcbQuoteConfiguration $quote): RedirectResponse
    {
        abort_unless($quote->project_id === $project->id, 404);
        $data = $request->validate([
            'setup_charge' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'engineering_charge' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'fabrication_unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:365'],
            'quote_valid_until' => ['required', 'date', 'after_or_equal:today'],
            'engineering_notes' => ['required', 'string', 'min:10', 'max:10000'],
        ]);

        $data['total_fabrication_price'] = round((float) $data['fabrication_unit_price'] * max(1, (int) $quote->quantity), 2);
        $data['status'] = 'quoted';
        $data['quoted_at'] = now();
        $data['requires_engineering_quote'] = false;

        DB::transaction(function () use ($request, $project, $quote, $data) {
            $quote->update($data);
            $project->update(['status' => 'quoted']);
            $project->activityLogs()->create([
                'user_id' => $request->user()->id,
                'action' => 'engineering_quote_issued',
                'description' => 'Manual engineering quote issued to customer',
                'metadata' => [
                    'quote_id' => $quote->id,
                    'currency' => $data['currency'],
                    'total' => (float) $data['setup_charge'] + (float) $data['engineering_charge'] + (float) $data['total_fabrication_price'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Engineering quote issued. The customer can now approve it in the PCB portal.');
    }

    public function download(Request $request, PcbProject $project, PcbFile $file): StreamedResponse
    {
        abort_unless($file->project_id === $project->id, 404);
        abort_unless(Storage::disk($file->storage_disk)->exists($file->storage_path), 404);

        $file->accessLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'download',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'admin_engineering_review',
        ]);

        return Storage::disk($file->storage_disk)->download(
            $file->storage_path,
            $file->filename_original,
            ['Content-Type' => $file->mime_type, 'X-Robots-Tag' => 'noindex, nofollow, noarchive'],
        );
    }
}
