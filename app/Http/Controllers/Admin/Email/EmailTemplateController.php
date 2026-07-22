<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailGroup;
use App\Models\EmailSegment;
use App\Models\EmailSenderIdentity;
use App\Models\EmailProviderConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:email.templates.manage']);
    }

    public function index(Request $request)
    {
        $query = EmailTemplate::latest();
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $templates = $query->paginate(25);
        
        return view('admin.email.templates.index', compact('templates'));
    }

    public function create()
    {
        $groups = EmailGroup::all();
        return view('admin.email.templates.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'html_body' => 'required|string',
            'text_body' => 'nullable|string',
            'category' => 'required|in:newsletter,product_launch,announcement,promotion,onboarding,followup,reengagement,event,regional',
            'is_active' => 'boolean',
        ]);

        $template = EmailTemplate::create([
            ...$validated,
            'user_id' => auth()->id(),
            'preview_text' => $request->preview_text ?? null,
        ]);

        return redirect()->route('admin.email.templates.show', $template)
            ->with('success', 'Template created successfully.');
    }

    public function show(EmailTemplate $template)
    {
        return view('admin.email.templates.show', compact('template'));
    }

    public function edit(EmailTemplate $template)
    {
        $groups = EmailGroup::all();
        return view('admin.email.templates.edit', compact('template', 'groups'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'html_body' => 'required|string',
            'text_body' => 'nullable|string',
            'category' => 'required|in:newsletter,product_launch,announcement,promotion,onboarding,followup,reengagement,event,regional',
            'is_active' => 'boolean',
            'preview_text' => 'nullable|string|max:255',
        ]);

        $template->update($validated);

        return redirect()->route('admin.email.templates.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    public function duplicate(EmailTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->user_id = auth()->id();
        $newTemplate->save();

        return redirect()->route('admin.email.templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully.');
    }

    public function destroy(EmailTemplate $template)
    {
        // Check if template is used in any active campaigns
        $usedInCampaigns = DB::table('email_campaigns')
            ->where('template_id', $template->id)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($usedInCampaigns) {
            return back()->with('error', 'Cannot delete template used in active campaigns.');
        }

        $template->delete();

        return redirect()->route('admin.email.templates.index')
            ->with('success', 'Template deleted successfully.');
    }
}
