<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use App\Services\Pcb\PcbFileService;
use App\Services\Pcb\PcbOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcbPortalController extends Controller
{
    private const PROJECT_STATUSES = [
        'draft', 'requirements_pending', 'design_requested', 'design_in_progress',
        'design_review', 'design_approved', 'files_ready', 'quote_pending',
        'quoted', 'awaiting_approval', 'ordered', 'manufacturing', 'inspection',
        'shipped', 'completed', 'on_hold', 'cancelled',
    ];

    public function landing(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect('/en/projects');
        }

        return view('pcb.landing');
    }

    public function index(Request $request): View
    {
        $projects = $this->visibleProjects($request)
            ->withCount(['files', 'quoteConfigurations'])
            ->latest()
            ->paginate(12);

        $summary = [
            'active' => $this->visibleProjects($request)->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'quotes' => $this->visibleProjects($request)->whereIn('status', ['quote_pending', 'quoted', 'awaiting_approval'])->count(),
            'manufacturing' => $this->visibleProjects($request)->whereIn('status', ['ordered', 'manufacturing', 'inspection', 'shipped'])->count(),
        ];

        return view('pcb.projects.index', compact('projects', 'summary'));
    }

    public function create(): View
    {
        return view('pcb.projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'application_type' => ['nullable', 'string', 'max:100'],
            'confidentiality' => ['required', 'in:internal,confidential,nda_required'],
            'project_type' => ['required', 'in:prototype,production'],
            'target_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'target_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'required_date' => ['nullable', 'date', 'after:today'],
            'destination_country' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $project = DB::transaction(function () use ($request, $data) {
            $project = PcbProject::create($data + [
                'user_id' => $request->user()->id,
                'organization_id' => $request->user()->organization_id ?? null,
                'marketplace' => strtolower((string) session('marketplace', 'global')),
                'status' => 'draft',
            ]);

            $project->members()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
                'nda_accepted' => $project->confidentiality !== 'nda_required',
                'nda_accepted_at' => $project->confidentiality !== 'nda_required' ? now() : null,
            ]);
            $project->versions()->create([
                'version_number' => 1,
                'change_summary' => 'Project created',
                'created_by_id' => $request->user()->id,
                'snapshot_data' => $project->only(['name', 'project_type', 'target_quantity', 'required_date']),
            ]);
            $project->activityLogs()->create([
                'user_id' => $request->user()->id,
                'action' => 'project_created',
                'description' => 'PCB project created',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $project;
        });

        return redirect('/en/projects/'.$project->id)->with('status', 'Project created. Add your design files and board specification.');
    }

    public function show(Request $request, PcbProject $project): View
    {
        $this->authorizeProject($request, $project);

        $project->load([
            'files' => fn ($query) => $query->latest(),
            'files.scanResults',
            'gerberAnalysisRuns.detectedLayers',
            'gerberAnalysisRuns.warnings',
            'quoteConfigurations' => fn ($query) => $query->with(['lineItems', 'order'])->latest(),
            'activityLogs' => fn ($query) => $query->latest()->limit(30),
        ]);

        $downloadUrls = $project->files->mapWithKeys(fn (PcbFile $file) => [
            $file->id => URL::temporarySignedRoute(
                'pcb.files.download',
                now()->addMinutes((int) config('pcb.download_link_minutes', 15)),
                ['project' => $project->id, 'file' => $file->id],
            ),
        ]);

        return view('pcb.projects.show', compact('project', 'downloadUrls'));
    }

    public function update(Request $request, PcbProject $project): RedirectResponse
    {
        $this->authorizeProject($request, $project, edit: true);

        if (! in_array($project->status, ['draft', 'requirements_pending', 'files_ready', 'quote_pending'], true)) {
            return back()->withErrors(['project' => 'Project details are locked while this project is in an active commercial or manufacturing stage.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'application_type' => ['nullable', 'string', 'max:100'],
            'target_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'target_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'required_date' => ['nullable', 'date', 'after:today'],
            'destination_country' => ['required', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $project->update($data);
        $project->activityLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'project_updated',
            'description' => 'Project requirements updated',
            'metadata' => ['fields' => array_keys($data)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Project requirements saved.');
    }

    public function upload(Request $request, PcbProject $project, PcbFileService $files): RedirectResponse
    {
        $this->authorizeProject($request, $project, edit: true);
        $data = $request->validate([
            'file_type' => ['required', 'in:gerber,bom,cpl,schematic,pcb_source,step,assembly_drawing,other'],
            'file' => ['required', 'file', 'max:'.((int) config('pcb.max_file_size_mb', 100) * 1024)],
        ]);

        $file = $files->store($project, $request->user(), $request->file('file'), $data['file_type']);

        if ($data['file_type'] === 'gerber' && in_array($project->status, ['draft', 'requirements_pending'], true)) {
            $project->update(['status' => 'files_ready']);
        }

        return back()->with('status', $file->filename_original.' uploaded to private project storage.');
    }

    public function download(Request $request, PcbProject $project, PcbFile $file): StreamedResponse
    {
        $this->authorizeProject($request, $project);
        abort_unless($file->project_id === $project->id, 404);
        abort_unless(Storage::disk($file->storage_disk)->exists($file->storage_path), 404);

        $file->accessLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'download',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'project_workspace',
        ]);

        return Storage::disk($file->storage_disk)->download(
            $file->storage_path,
            $file->filename_original,
            ['Content-Type' => $file->mime_type, 'X-Robots-Tag' => 'noindex, nofollow, noarchive'],
        );
    }

    public function submitQuote(Request $request, PcbProject $project): RedirectResponse
    {
        $this->authorizeProject($request, $project, edit: true);

        if (! $project->files()->where('file_type', 'gerber')->exists()) {
            return back()->withErrors(['quote' => 'Upload a Gerber ZIP before requesting an engineering quote.']);
        }

        $data = $request->validate([
            'board_type' => ['required', 'in:single_sided,double_sided,multilayer,rigid_flex,flex,aluminum,ceramic'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'length_mm' => ['required', 'numeric', 'min:1', 'max:2000'],
            'width_mm' => ['required', 'numeric', 'min:1', 'max:2000'],
            'thickness_mm' => ['required', 'numeric', 'min:0.2', 'max:10'],
            'layer_count' => ['required', 'integer', 'min:1', 'max:64'],
            'substrate_material' => ['required', 'string', 'max:80'],
            'outer_copper_oz' => ['required', 'string', 'max:20'],
            'solder_mask_color' => ['required', 'string', 'max:40'],
            'silkscreen_color' => ['required', 'string', 'max:40'],
            'surface_finish' => ['required', 'in:HASL,HASL_Lead_Free,ENIG,OSP,Immersion_Silver,Immersion_Tin,Gold_Fingers'],
            'via_covering' => ['required', 'in:tented,plugged,filled,open'],
            'panelization_type' => ['required', 'in:none,v_score,routing,tab_route'],
            'production_speed' => ['required', 'in:standard,fast,express'],
            'aoi_testing' => ['nullable', 'boolean'],
            'electrical_test' => ['nullable', 'boolean'],
            'impedance_control' => ['nullable', 'boolean'],
            'blind_buried_vias' => ['nullable', 'boolean'],
            'hdi' => ['nullable', 'boolean'],
            'edge_plating' => ['nullable', 'boolean'],
            'castellated_holes' => ['nullable', 'boolean'],
        ]);

        foreach (['aoi_testing', 'electrical_test', 'impedance_control', 'blind_buried_vias', 'hdi', 'edge_plating', 'castellated_holes'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $quote = $project->quoteConfigurations()->whereIn('status', ['draft', 'rejected'])->latest()->first();
        $payload = $data + [
            'created_by_id' => $request->user()->id,
            'organization_id' => $project->organization_id,
            'currency' => $project->currency,
            'status' => 'submitted',
            'submitted_at' => now(),
            'requires_engineering_quote' => true,
        ];

        if ($quote) {
            $quote->update($payload);
        } else {
            $quote = $project->quoteConfigurations()->create($payload);
        }
        $project->update(['status' => 'quote_pending']);
        $project->activityLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'quote_requested',
            'description' => 'Board specification submitted for engineering quote',
            'metadata' => ['quote_id' => $quote->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Your board specification was submitted for engineering review.');
    }

    public function approveQuote(Request $request, PcbProject $project, PcbQuoteConfiguration $quote, PcbOrderService $orders): RedirectResponse
    {
        $this->authorizeProject($request, $project);
        abort_unless($quote->project_id === $project->id, 404);
        $data = $request->validate(['customer_notes' => ['nullable', 'string', 'max:2000']]);
        $order = $orders->approve($project, $quote, $request->user(), $data['customer_notes'] ?? null);

        return back()->with('status', 'Quote approved. NeoGiga order '.$order->order_number.' has been created for manual payment confirmation.');
    }

    public function rejectQuote(Request $request, PcbProject $project, PcbQuoteConfiguration $quote): RedirectResponse
    {
        $this->authorizeProject($request, $project);
        abort_unless($quote->project_id === $project->id, 404);
        $data = $request->validate(['customer_notes' => ['required', 'string', 'min:5', 'max:2000']]);
        abort_unless($quote->status === 'quoted', 422);

        $quote->update([
            'status' => 'rejected',
            'customer_rejected_at' => now(),
            'customer_notes' => $data['customer_notes'],
        ]);
        $project->update(['status' => 'quote_pending']);
        $project->activityLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'quote_changes_requested',
            'description' => 'Customer requested changes to the PCB quote',
            'metadata' => ['quote_id' => $quote->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Quote changes requested.');
    }

    public function cancel(Request $request, PcbProject $project): RedirectResponse
    {
        $this->authorizeProject($request, $project, edit: true);
        abort_unless((int) $project->user_id === (int) $request->user()->id, 403);

        if (! in_array($project->status, ['draft', 'requirements_pending', 'files_ready', 'quote_pending', 'quoted'], true)) {
            return back()->withErrors(['project' => 'An active manufacturing project cannot be cancelled from the portal.']);
        }

        $project->update(['status' => 'cancelled']);

        return redirect('/en/projects')->with('status', 'Project cancelled. Private files remain retained for audit and recovery.');
    }

    private function visibleProjects(Request $request)
    {
        $user = $request->user();

        return PcbProject::query()->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('members', fn ($members) => $members
                    ->where('user_id', $user->id)
                    ->where(fn ($expiry) => $expiry->whereNull('access_expires_at')->orWhere('access_expires_at', '>', now())));

            if ($user->organization_id ?? null) {
                $query->orWhere('organization_id', $user->organization_id);
            }
        });
    }

    private function authorizeProject(Request $request, PcbProject $project, bool $edit = false): void
    {
        $allowed = $edit
            ? $project->canBeEditedBy($request->user())
            : $project->canBeAccessedBy($request->user());

        abort_unless($allowed, 403);
    }
}
