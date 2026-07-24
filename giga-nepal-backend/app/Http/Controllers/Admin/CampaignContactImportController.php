<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignContactImportService;
use App\Services\Marketing\CampaignContactXmlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignContactImportController extends Controller
{
    public function index(Request $request): View
    {
        $imports = DB::table('campaign_contact_imports')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.marketing.campaign-contacts', [
            'imports' => $imports,
        ]);
    }

    public function preview(Request $request): View
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'extensions:csv,xlsx,xls,xml'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xml') {
            $content = file_get_contents($file->getRealPath());
            $parser = app(CampaignContactXmlParser::class);
            $preview = $parser->preview($content);
        } else {
            // For CSV/XLS/XLSX, use the existing spreadsheet reader
            $token = Str::random(64);
            $path = $file->storeAs('campaign-contact-previews', $token . '.' . $extension, 'local');
            $preview = ['token' => $token, 'format' => $extension, 'file_path' => Storage::disk('local')->path($path)];
        }

        return view('admin.marketing.campaign-contact-import', [
            'preview' => $preview,
            'file_name' => $file->getClientOriginalName(),
        ]);
    }

    public function execute(Request $request, CampaignContactImportService $importService): RedirectResponse
    {
        $data = $request->validate([
            'preview_token' => ['required', 'string'],
            'country_group_id' => ['nullable', 'integer', 'exists:email_groups,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:email_groups,id'],
            'source' => ['nullable', 'string', 'max:190'],
        ]);

        $preview = cache()->get("campaign-contact-preview:{$data['preview_token']}");
        abort_unless($preview, 410, 'Preview expired. Upload the file again.');

        // Process the import
        $result = $importService->import(
            $preview['rows'] ?? [],
            [
                'name' => $preview['file_name'] ?? 'Campaign Import',
                'source' => $data['source'] ?? 'csv',
                'uploaded_by' => $request->user()->id,
                'country_group_id' => $data['country_group_id'] ?? null,
                'group_ids' => $data['group_ids'] ?? [],
            ]
        );

        return redirect('/admin/marketing/campaign-contacts')
            ->with('status', "Import complete: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped ({$result['suppressed']} suppressed).");
    }

    public function show(int $import): View
    {
        $record = DB::table('campaign_contact_imports')->find($import);
        abort_unless($record, 404);

        $errors = DB::table('campaign_contact_import_errors')
            ->where('campaign_contact_import_id', $import)
            ->orderBy('row_number')
            ->limit(250)
            ->get();

        return view('admin.marketing.campaign-contact-import-detail', [
            'import' => $record,
            'errors' => $errors,
        ]);
    }

    public function conversionStatus(int $subscriberId): View
    {
        $subscriber = DB::table('email_subscribers')->find($subscriberId);
        abort_unless($subscriber, 404);

        $logs = DB::table('contact_conversion_logs')
            ->where('subscriber_id', $subscriberId)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.marketing.contact-conversion', [
            'subscriber' => $subscriber,
            'logs' => $logs,
        ]);
    }

    public function convertToCustomer(Request $request, int $subscriberId): RedirectResponse
    {
        $service = app(\App\Services\Marketing\ContactToCustomerConversionService::class);
        $result = $service->convertToCustomer($subscriberId, [
            'invited_by' => $request->user()->id,
        ]);

        if ($result['success']) {
            return redirect()->back()->with('status', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    public function sendInvitation(Request $request, int $subscriberId): RedirectResponse
    {
        $service = app(\App\Services\Marketing\ContactToCustomerConversionService::class);
        $result = $service->sendInvitation($subscriberId, [
            'invited_by' => $request->user()->id,
        ]);

        if ($result['success']) {
            return redirect()->back()->with('status', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
