<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorTeamMember;
use App\Models\VendorMemberInvitation;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    protected $roles = [
        'owner' => ['all'],
        'admin' => ['manage_team', 'manage_products', 'manage_inventory', 'manage_orders', 'manage_finance', 'view_reports'],
        'catalog_manager' => ['manage_products', 'view_inventory'],
        'inventory_manager' => ['manage_inventory', 'view_products'],
        'order_manager' => ['manage_orders', 'view_inventory', 'view_products'],
        'logistics_manager' => ['manage_shipments', 'view_orders'],
        'finance_manager' => ['manage_finance', 'view_reports'],
        'support_agent' => ['manage_support', 'view_orders'],
        'viewer' => ['view_only'],
    ];

    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $members = VendorTeamMember::where('vendor_id', $vendor->id)
            ->with('user')
            ->latest()
            ->get();

        $pendingInvitations = VendorMemberInvitation::where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('seller.team.index', compact('members', 'pendingInvitations'));
    }

    public function create()
    {
        $this->checkPermission('manage_team');
        
        return view('seller.team.create', ['roles' => array_keys($this->roles)]);
    }

    public function store(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $this->checkPermission('manage_team');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:vendor_team_members,email',
            'role' => 'required|in:' . implode(',', array_keys($this->roles)),
            'permissions' => 'nullable|array',
        ]);

        // Validate permissions match role
        if (!empty($validated['permissions'])) {
            $allowedPermissions = $this->roles[$validated['role']];
            if (!in_array('all', $allowedPermissions)) {
                $invalidPermissions = array_diff($validated['permissions'], $allowedPermissions);
                if (!empty($invalidPermissions)) {
                    return back()->withErrors(['permissions' => 'Invalid permissions for selected role.']);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Create team member
            $member = VendorTeamMember::create([
                'vendor_id' => $vendor->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'permissions' => !empty($validated['permissions']) 
                    ? $validated['permissions'] 
                    : ($this->roles[$validated['role']] === ['all'] ? ['all'] : $this->roles[$validated['role']]),
                'status' => 'active',
            ]);

            // Create invitation
            $invitation = VendorMemberInvitation::create([
                'vendor_id' => $vendor->id,
                'team_member_id' => $member->id,
                'email' => $validated['email'],
                'role' => $validated['role'],
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'status' => 'pending',
            ]);

            // Send invitation email
            event(new \App\Events\TeamMemberInvited($member, $invitation));

            DB::commit();

            return redirect()->route('seller.team.index')
                ->with('success', 'Invitation sent to ' . $validated['email']);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to invite team member: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $member = VendorTeamMember::where('vendor_id', $vendor->id)->findOrFail($id);
        
        $activityLog = \App\Models\VendorActivityLog::where('user_id', $member->user_id ?? null)
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->take(20)
            ->get();

        return view('seller.team.show', compact('member', 'activityLog'));
    }

    public function edit($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $member = VendorTeamMember::where('vendor_id', $vendor->id)->findOrFail($id);

        // Cannot edit owner
        if ($member->role === 'owner') {
            return back()->withErrors(['error' => 'Cannot edit owner role.']);
        }

        return view('seller.team.edit', compact('member', 'roles' => array_keys($this->roles)));
    }

    public function update(Request $request, $id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $member = VendorTeamMember::where('vendor_id', $vendor->id)->findOrFail($id);

        // Cannot edit owner
        if ($member->role === 'owner') {
            return back()->withErrors(['error' => 'Cannot edit owner role.']);
        }

        $this->checkPermission('manage_team');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:' . implode(',', array_keys($this->roles)),
            'permissions' => 'nullable|array',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        $member->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'permissions' => !empty($validated['permissions']) 
                ? $validated['permissions'] 
                : ($this->roles[$validated['role']] === ['all'] ? ['all'] : $this->roles[$validated['role']]),
            'status' => $validated['status'] ?? $member->status,
        ]);

        return redirect()->route('seller.team.index')
            ->with('success', 'Team member updated successfully.');
    }

    public function destroy($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $member = VendorTeamMember::where('vendor_id', $vendor->id)->findOrFail($id);

        // Cannot delete owner
        if ($member->role === 'owner') {
            return back()->withErrors(['error' => 'Cannot remove owner from team.']);
        }

        $member->delete();

        return redirect()->route('seller.team.index')
            ->with('success', 'Team member removed.');
    }

    public function resendInvitation($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $invitation = VendorMemberInvitation::where('vendor_id', $vendor->id)
            ->findOrFail($id);

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['error' => 'Cannot resend expired or accepted invitation.']);
        }

        // Regenerate token and extend expiry
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        event(new \App\Events\TeamMemberInvited($invitation->teamMember, $invitation));

        return back()->with('success', 'Invitation resent.');
    }

    public function revokeInvitation($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $invitation = VendorMemberInvitation::where('vendor_id', $vendor->id)
            ->findOrFail($id);

        $invitation->update(['status' => 'revoked']);

        return back()->with('success', 'Invitation revoked.');
    }

    public function acceptInvitation(Request $request, $token)
    {
        $invitation = VendorMemberInvitation::where('token', $token)->firstOrFail();

        if ($invitation->status !== 'pending') {
            return redirect()->route('login')->withErrors(['error' => 'Invalid or expired invitation.']);
        }

        if ($invitation->expires_at < now()) {
            $invitation->update(['status' => 'expired']);
            return redirect()->route('login')->withErrors(['error' => 'Invitation has expired.']);
        }

        session(['pending_invitation' => $invitation->id]);

        return redirect()->route('register', ['invitation' => $token]);
    }

    private function checkPermission($permission)
    {
        $user = Auth::guard('vendor')->user();
        
        $member = VendorTeamMember::where('vendor_id', $user->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$member) {
            abort(403, 'Unauthorized access.');
        }

        if (in_array('all', $member->permissions)) {
            return true;
        }

        if (!in_array($permission, $member->permissions)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return true;
    }
}
