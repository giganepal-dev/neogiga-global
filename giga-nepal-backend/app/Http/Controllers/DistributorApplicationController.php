<?php

namespace App\Http\Controllers;

use App\Models\DistributorApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DistributorApplicationController extends Controller
{
    /**
     * Display a listing of distributor applications.
     * Admin only - with filtering and search
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization check
        if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'marketplace_manager', 'distributor_reviewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $query = DistributorApplication::with(['country', 'province', 'district', 'reviewer']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by country
        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Search by company name, contact person, or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_person_name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_person_email', 'LIKE', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $applications,
            'meta' => [
                'total' => $applications->total(),
                'per_page' => $applications->per_page(),
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
            ]
        ]);
    }

    /**
     * Get application statistics for admin dashboard
     */
    public function stats(): JsonResponse
    {
        // Authorization check
        if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'marketplace_manager', 'distributor_reviewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $stats = [
            'total' => DistributorApplication::count(),
            'pending' => DistributorApplication::where('status', 'pending')->count(),
            'under_review' => DistributorApplication::where('status', 'under_review')->count(),
            'approved' => DistributorApplication::where('status', 'approved')->count(),
            'rejected' => DistributorApplication::where('status', 'rejected')->count(),
            'today' => DistributorApplication::whereDate('created_at', today())->count(),
            'this_week' => DistributorApplication::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => DistributorApplication::whereMonth('created_at', now()->month)->count(),
        ];

        // Country breakdown
        $countryBreakdown = DistributorApplication::selectRaw('country_id, COUNT(*) as count')
            ->groupBy('country_id')
            ->withCount('*')
            ->get()
            ->map(function($item) {
                return [
                    'country_id' => $item->country_id,
                    'country_name' => $item->country ? $item->country->name : 'Unknown',
                    'count' => $item->count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => $stats,
                'by_country' => $countryBreakdown,
            ]
        ]);
    }

    /**
     * Store a new distributor application (public endpoint).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'pan_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_email' => 'required|email|max:255',
            'contact_person_phone' => 'required|string|max:50',
            'business_address' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'province_id' => 'nullable|exists:provinces,id',
            'district_id' => 'nullable|exists:districts,id',
            'city_id' => 'nullable|exists:cities,id',
            'postal_code' => 'nullable|string|max:20',
            'preferred_territories' => 'nullable|array',
            'territory_type' => 'nullable|in:exclusive,non_exclusive',
            'business_experience' => 'nullable|string',
            'years_in_business' => 'nullable|integer|min:0|max:100',
            'annual_turnover' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10|default:NPR',
            'interested_categories' => 'nullable|array',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_holder_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:50',
            'routing_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = DistributorApplication::create(array_merge($validator->validated(), [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'pending',
            ]));

            try {
                app(\App\Services\Marketing\AccountCommunicationService::class)->application(
                    $request->user(), 'distributor', 'submitted'
                );
            } catch (\Throwable) {}

            return response()->json([
                'success' => true,
                'message' => 'Distributor application submitted successfully. We will review your application within 3-5 business days.',
                'data' => $application
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified distributor application.
     */
    public function show(int $id): JsonResponse
    {
        // Authorization check
        if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'marketplace_manager', 'distributor_reviewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $application = DistributorApplication::with([
            'country', 'province', 'district', 'city', 'reviewer', 
            'territories', 'activities', 'leads', 'customers', 'commissions'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

    /**
     * Update distributor application status (approve/reject/under_review).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Authorization check
        if (!Auth::check() || !Auth::user()->hasAnyRole(['admin', 'marketplace_manager', 'distributor_reviewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $application = DistributorApplication::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,under_review,approved,rejected',
            'admin_notes' => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'target_monthly_sales' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|required_if:status,rejected|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $validator->validated();
            
            // Set timestamps based on status
            if ($request->status === 'approved') {
                $updateData['approved_at'] = now();
                $updateData['is_active'] = true;
                
                try {
                    $applicant = $application->user_id ? \App\Models\User::find($application->user_id) : null;
                    if ($applicant) {
                        app(\App\Services\Marketing\AccountCommunicationService::class)->application($applicant, 'distributor', 'approved');
                    }
                } catch (\Throwable) {}

            } elseif ($request->status === 'rejected') {
                $updateData['rejected_at'] = now();
                $updateData['is_active'] = false;

                try {
                    $applicant = $application->user_id ? \App\Models\User::find($application->user_id) : null;
                    if ($applicant) {
                        app(\App\Services\Marketing\AccountCommunicationService::class)->application($applicant, 'distributor', 'rejected');
                    }
                } catch (\Throwable) {}
                
            } elseif ($request->status === 'under_review') {
                $updateData['reviewed_at'] = now();
            }

            $updateData['reviewed_by'] = Auth::id();
            $updateData['reviewed_at'] = now();

            $application->update($updateData);

            return response()->json([
                'success' => true,
                'message' => "Application {$request->status} successfully.",
                'data' => $application->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified distributor application.
     */
    public function destroy(int $id): JsonResponse
    {
        // Authorization check
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $application = DistributorApplication::findOrFail($id);
        
        // Prevent deletion of approved applications with active distributor accounts
        if ($application->status === 'approved' && $application->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete approved and active distributor applications.'
            ], 400);
        }

        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Distributor application deleted successfully.'
        ]);
    }
}
