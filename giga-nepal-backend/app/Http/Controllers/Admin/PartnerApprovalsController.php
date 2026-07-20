<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor\DistributorTerritoryRequest;
use App\Models\ResellerApplication;
use App\Models\ResellerTerritoryRequest;
use App\Services\Distributor\DistributorTerritoryService;
use App\Services\Reseller\ResellerApplicationService;
use App\Services\Reseller\ResellerTerritoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

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
            ],
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
