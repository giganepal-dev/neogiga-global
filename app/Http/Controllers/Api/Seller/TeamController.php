<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\TeamService;
use App\Models\VendorTeamMember;
use App\Models\VendorMemberInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all team members
     */
    public function index()
    {
        $vendorId = Auth::user()->vendor_id;
        $members = $this->teamService->getTeamMembers($vendorId);

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Invite new team member
     */
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,catalog_manager,inventory_manager,order_manager,logistics_manager,finance_manager,support_agent,viewer',
        ]);

        try {
            $vendorId = Auth::user()->vendor_id;
            $invitation = $this->teamService->inviteMember($vendorId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully to ' . $validated['email'],
                'data' => $invitation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept invitation (public endpoint with token)
     */
    public function acceptInvitation($token)
    {
        // Find invitation
        $invitation = VendorMemberInvitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // If user is authenticated, accept directly
        if (Auth::check()) {
            try {
                $member = $this->teamService->acceptInvitation($token, Auth::id());

                return redirect('/seller/dashboard')->with('success', 'You have joined the team!');
            } catch (\Exception $e) {
                return redirect('/seller/invitations/accept?token=' . $token)
                    ->with('error', $e->getMessage());
            }
        }

        // Show acceptance page for unauthenticated users
        return view('seller.invitations.accept', compact('invitation', 'token'));
    }

    /**
     * Update member role
     */
    public function updateRole(Request $request, VendorTeamMember $member)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($member->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|string|in:admin,catalog_manager,inventory_manager,order_manager,logistics_manager,finance_manager,support_agent,viewer',
        ]);

        try {
            $updated = $this->teamService->updateMemberRole($member, $validated['role']);

            return response()->json([
                'success' => true,
                'message' => 'Member role updated successfully.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Deactivate member
     */
    public function deactivate(VendorTeamMember $member)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($member->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $this->teamService->deactivateMember($member);

            return response()->json([
                'success' => true,
                'message' => 'Member deactivated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reactivate member
     */
    public function reactivate(VendorTeamMember $member)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($member->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $this->teamService->reactivateMember($member);

        return response()->json([
            'success' => true,
            'message' => 'Member reactivated successfully.',
        ]);
    }

    /**
     * Remove member permanently
     */
    public function destroy(VendorTeamMember $member)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($member->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $this->teamService->removeMember($member);

            return response()->json([
                'success' => true,
                'message' => 'Member removed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resend invitation
     */
    public function resendInvitation(VendorMemberInvitation $invitation)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($invitation->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $updated = $this->teamService->resendInvitation($invitation);

            return response()->json([
                'success' => true,
                'message' => 'Invitation resent successfully.',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(VendorMemberInvitation $invitation)
    {
        $vendorId = Auth::user()->vendor_id;

        if ($invitation->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $this->teamService->cancelInvitation($invitation);

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled.',
        ]);
    }

    /**
     * Get pending invitations
     */
    public function pendingInvitations()
    {
        $vendorId = Auth::user()->vendor_id;

        $invitations = VendorMemberInvitation::where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations,
        ]);
    }

    /**
     * Get available roles
     */
    public function roles()
    {
        $roles = $this->teamService->getAvailableRoles();
        $rolePermissions = [];

        foreach ($roles as $role) {
            if ($role !== 'owner') {
                $rolePermissions[$role] = $this->teamService->getPermissionsForRole($role);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permissions' => $rolePermissions,
            ],
        ]);
    }
}
