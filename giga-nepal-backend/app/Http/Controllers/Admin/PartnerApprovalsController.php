<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor\DistributorTerritoryRequest;
use App\Models\ResellerApplication;
use App\Models\ResellerTerritoryRequest;
use App\Services\Account\PartnerApplicationService;
use App\Services\Distributor\DistributorTerritoryService;
use App\Services\Reseller\ResellerApplicationService;
use App\Services\Reseller\ResellerTerritoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartnerApprovalsController extends Controller
{
    public function index(): View
    {
        return view('admin.partner-approvals', [
            'stats' => [
                'reseller_apps_pending' => Schema::hasTable('reseller_applications')
                    ? DB::table('reseller_applications')->where('status', 'pending')->count() : 0,
                'reseller_territory_pending' => Schema::hasTable('reseller_territory_requests')
                    ? DB::table('reseller_territory_requests')->where('status', 'pending')->count() : 0,
                'distributor_territory_pending' => Schema::hasTable('distributor_territory_requests')
                    ? DB::table('distributor_territory_requests')->where('status', 'pending')->count() : 0,
                'account_applications_pending' => Schema::hasTable('account_applications')
                    ? DB::table('account_applications')->whereIn('status', ['submitted', 'under_review', 'needs_information'])->count() : 0,
            ],
            'accountApplications' => Schema::hasTable('account_applications')
                ? DB::table('account_applications as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                    ->select('a.*', 'u.name as applicant_name', 'u.email as applicant_email')
                    ->orderByDesc('a.id')->limit(100)->get() : collect(),
            'accountDocuments' => Schema::hasTable('account_application_documents')
                ? DB::table('account_application_documents')->orderBy('id')->get()->groupBy('account_application_id') : collect(),
            'resellerApplications' => Schema::hasTable('reseller_applications')
                ? DB::table('reseller_applications')->orderByDesc('id')->limit(50)->get() : collect(),
            'resellerTerritoryRequests' => Schema::hasTable('reseller_territory_requests')
                ? DB::table('reseller_territory_requests as r')
                    ->leftJoin('resellers as rs', 'rs.id', '=', 'r.reseller_id')
                    ->select('r.*', 'rs.company_name as partner_name')
                    ->orderByDesc('r.id')->limit(50)->get() : collect(),
            'distributorTerritoryRequests' => Schema::hasTable('distributor_territory_requests')
                ? DB::table('distributor_territory_requests as r')
                    ->leftJoin('distributors as d', 'd.id', '=', 'r.distributor_id')
                    ->select('r.*', 'd.name as partner_name')
                    ->orderByDesc('r.id')->limit(50)->get() : collect(),
        ]);
    }

    public function approveAccountApplication(int $application, PartnerApplicationService $service): RedirectResponse
    {
        $service->approve($application, (int) auth()->id());

        return back()->with('status', 'Partner application approved and account role provisioned.');
    }

    public function reviewAccountApplication(Request $request, int $application, PartnerApplicationService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:under_review,needs_information,rejected'],
            'notes' => ['required', 'string', 'max:5000'],
        ]);
        $service->review($application, (int) auth()->id(), $data['status'], $data['notes']);

        return back()->with('status', 'Application review status updated.');
    }

    public function downloadAccountDocument(int $document): BinaryFileResponse
    {
        $record = DB::table('account_application_documents')->find($document);
        abort_unless($record && Storage::disk($record->storage_disk)->exists($record->storage_path), 404);

        return response()->download(Storage::disk($record->storage_disk)->path($record->storage_path), $record->original_name);
    }

    public function approveResellerApplication(int $application, ResellerApplicationService $service): RedirectResponse
    {
        $record = ResellerApplication::findOrFail($application);
        abort_if($record->status !== 'pending', 422, 'Application is not pending.');
        $service->approve($record);

        return back()->with('status', 'Reseller application approved and account provisioned.');
    }

    public function rejectResellerApplication(Request $request, int $application): RedirectResponse
    {
        $record = ResellerApplication::findOrFail($application);
        $record->forceFill([
            'status' => 'rejected',
            'metadata' => array_merge($record->metadata ?? [], ['reason' => $request->input('reason', 'Rejected by admin')]),
        ])->save();

        return back()->with('status', 'Reseller application rejected.');
    }

    public function approveResellerTerritory(int $requestId, ResellerTerritoryService $service): RedirectResponse
    {
        $record = ResellerTerritoryRequest::findOrFail($requestId);
        abort_if($record->status !== 'pending', 422, 'Request is not pending.');
        $service->approveRequest($record);

        return back()->with('status', 'Reseller territory request approved.');
    }

    public function rejectResellerTerritory(Request $request, int $requestId, ResellerTerritoryService $service): RedirectResponse
    {
        $record = ResellerTerritoryRequest::findOrFail($requestId);
        abort_if($record->status !== 'pending', 422, 'Request is not pending.');
        $service->rejectRequest($record, $request->input('reason', 'Rejected by admin'));

        return back()->with('status', 'Reseller territory request rejected.');
    }

    public function approveDistributorTerritory(int $requestId, DistributorTerritoryService $service): RedirectResponse
    {
        $record = DistributorTerritoryRequest::findOrFail($requestId);
        abort_if($record->status !== 'pending', 422, 'Request is not pending.');
        $service->approveRequest($record);

        return back()->with('status', 'Distributor territory request approved.');
    }

    public function rejectDistributorTerritory(Request $request, int $requestId, DistributorTerritoryService $service): RedirectResponse
    {
        $record = DistributorTerritoryRequest::findOrFail($requestId);
        abort_if($record->status !== 'pending', 422, 'Request is not pending.');
        $service->rejectRequest($record, $request->input('reason', 'Rejected by admin'));

        return back()->with('status', 'Distributor territory request rejected.');
    }
}
