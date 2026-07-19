<?php

namespace App\Http\Controllers;

use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SellerApplicationController extends Controller
{
    /**
     * Display a listing of seller applications (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = SellerApplication::with(['user', 'reviewer']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by country
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        // Search by business name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    /**
     * Store a newly created seller application (Public form submission).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|in:Manufacturer,Distributor,Retailer,Brand Owner,Other',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'country' => 'required|string|max:100',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'pan_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'company_registration_number' => 'nullable|string|max:100',
            'website_url' => 'nullable|url|max:255',
            'product_categories' => 'nullable|array',
            'brand_names' => 'nullable|array',
            'estimated_monthly_volume' => 'nullable|integer|min:0',
            'additional_info' => 'nullable|string',
            'document_pan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_company_reg' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_tax_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_identity' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle file uploads
        $documentPaths = [];
        $documentFields = ['document_pan', 'document_company_reg', 'document_tax_certificate', 'document_identity'];
        
        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('seller-applications/documents', 'public');
                $documentPaths[$field] = $path;
            }
        }

        // Merge document paths with data
        $data = array_merge($data, $documentPaths);

        // Link to user if authenticated
        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $application = SellerApplication::create($data);

        try {
            app(\App\Services\Marketing\AccountCommunicationService::class)->application(
                $request->user(), 'seller', 'submitted'
            );
        } catch (\Throwable) {
            // Non-blocking
        }

        return response()->json([
            'success' => true,
            'message' => 'Your seller application has been submitted successfully. We will review it within 3-5 business days.',
            'data' => $application,
        ], 201);
    }

    /**
     * Display the specified seller application (Admin only).
     */
    public function show(string $id): JsonResponse
    {
        $application = SellerApplication::with(['user', 'reviewer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    /**
     * Update the specified seller application (Admin review).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $application = SellerApplication::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,under_review,approved,rejected',
            'admin_notes' => 'nullable|string',
            'action' => 'nullable|in:approve,reject,under_review',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $adminUser = $request->user();
        
        // Handle action-based updates
        if ($request->has('action')) {
            switch ($request->action) {
                case 'approve':
                    $application->approve($adminUser, $request->admin_notes);
                    try {
                        $applicant = $application->user_id ? \App\Models\User::find($application->user_id) : null;
                        if ($applicant) {
                            app(\App\Services\Marketing\AccountCommunicationService::class)->application($applicant, 'seller', 'approved');
                        }
                    } catch (\Throwable) {}
                    break;
                case 'reject':
                    $application->reject($adminUser, $request->admin_notes);
                    try {
                        $applicant = $application->user_id ? \App\Models\User::find($application->user_id) : null;
                        if ($applicant) {
                            app(\App\Services\Marketing\AccountCommunicationService::class)->application($applicant, 'seller', 'rejected');
                        }
                    } catch (\Throwable) {}
                    break;
                case 'under_review':
                    $application->markUnderReview($adminUser);
                    break;
            }
        } elseif ($request->has('status')) {
            $application->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes ?? $application->admin_notes,
                'reviewed_by' => $adminUser->id,
            ]);

            if ($request->status === 'approved' && !$application->approved_at) {
                $application->update(['approved_at' => now()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Application updated successfully.',
            'data' => $application->fresh(),
        ]);
    }

    /**
     * Remove the specified seller application (Admin only).
     */
    public function destroy(string $id): JsonResponse
    {
        $application = SellerApplication::findOrFail($id);
        
        // Delete associated documents
        $documentFields = ['document_pan', 'document_company_reg', 'document_tax_certificate', 'document_identity'];
        foreach ($documentFields as $field) {
            if ($application->$field) {
                Storage::disk('public')->delete($application->$field);
            }
        }
        
        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application deleted successfully.',
        ]);
    }

    /**
     * Get statistics for admin dashboard.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => SellerApplication::count(),
            'pending' => SellerApplication::where('status', 'pending')->count(),
            'under_review' => SellerApplication::where('status', 'under_review')->count(),
            'approved' => SellerApplication::where('status', 'approved')->count(),
            'rejected' => SellerApplication::where('status', 'rejected')->count(),
            'this_month' => SellerApplication::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
