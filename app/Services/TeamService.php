<?php

namespace App\Services;

use App\Models\VendorTeamMember;
use App\Models\VendorMemberInvitation;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    protected $roles = [
        'owner' => ['*'],
        'admin' => ['manage_team', 'manage_products', 'manage_inventory', 'manage_orders', 'manage_logistics', 'manage_finance', 'manage_support', 'view_reports'],
        'catalog_manager' => ['manage_products', 'view_inventory', 'view_orders'],
        'inventory_manager' => ['manage_inventory', 'view_products', 'view_warehouses'],
        'order_manager' => ['manage_orders', 'view_inventory', 'view_shipments', 'manage_support'],
        'logistics_manager' => ['manage_shipments', 'manage_warehouses', 'view_orders', 'view_inventory'],
        'finance_manager' => ['view_payouts', 'view_statements', 'view_earnings', 'manage_taxes'],
        'support_agent' => ['manage_support', 'view_orders', 'view_products'],
        'viewer' => ['view_dashboard', 'view_products', 'view_inventory', 'view_orders'],
    ];

    /**
     * Get all team members for a vendor
     */
    public function getTeamMembers(int $vendorId)
    {
        return VendorTeamMember::where('vendor_id', $vendorId)
            ->with('user')
            ->orderBy('role', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Invite a new team member
     */
    public function inviteMember(int $vendorId, array $data): VendorMemberInvitation
    {
        return DB::transaction(function () use ($vendorId, $data) {
            $vendor = Vendor::findOrFail($vendorId);

            // Validate role
            if (!isset($this->roles[$data['role']])) {
                throw new \Exception('Invalid role specified.');
            }

            // Check if user already exists with this email
            $existingUser = \App\Models\User::where('email', $data['email'])->first();
            
            if ($existingUser) {
                // Check if already a team member
                $existingMember = VendorTeamMember::where('vendor_id', $vendorId)
                    ->where('user_id', $existingUser->id)
                    ->first();
                
                if ($existingMember) {
                    throw new \Exception('User is already a team member.');
                }
            }

            // Create invitation
            $invitation = VendorMemberInvitation::create([
                'vendor_id' => $vendorId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => $data['role'],
                'permissions' => json_encode($this->roles[$data['role']]),
                'token' => Str::random(32),
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'invited_by' => auth()->id(),
            ]);

            event(new \App\Events\TeamMemberInvited($invitation));

            return $invitation;
        });
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(string $token, int $userId): VendorTeamMember
    {
        return DB::transaction(function () use ($token, $userId) {
            $invitation = VendorMemberInvitation::where('token', $token)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->firstOrFail();

            if ($invitation->email !== \App\Models\User::find($userId)->email) {
                throw new \Exception('Email does not match invitation.');
            }

            // Create team member
            $member = VendorTeamMember::create([
                'vendor_id' => $invitation->vendor_id,
                'user_id' => $userId,
                'name' => $invitation->name,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'permissions' => $invitation->permissions,
                'status' => 'active',
            ]);

            // Mark invitation as accepted
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'user_id' => $userId,
            ]);

            event(new \App\Events\TeamMemberJoined($member));

            return $member;
        });
    }

    /**
     * Update member role and permissions
     */
    public function updateMemberRole(VendorTeamMember $member, string $newRole): VendorTeamMember
    {
        if (!isset($this->roles[$newRole])) {
            throw new \Exception('Invalid role specified.');
        }

        // Prevent changing owner role
        if ($member->role === 'owner') {
            throw new \Exception('Owner role cannot be changed.');
        }

        return DB::transaction(function () use ($member, $newRole) {
            $member->update([
                'role' => $newRole,
                'permissions' => json_encode($this->roles[$newRole]),
            ]);

            event(new \App\Events\TeamMemberRoleChanged($member));

            return $member->fresh();
        });
    }

    /**
     * Add custom permissions to member
     */
    public function addPermissions(VendorTeamMember $member, array $permissions): VendorTeamMember
    {
        if ($member->role === 'owner') {
            throw new \Exception('Cannot modify owner permissions.');
        }

        $currentPermissions = json_decode($member->permissions, true) ?? [];
        $newPermissions = array_unique(array_merge($currentPermissions, $permissions));

        $member->update(['permissions' => json_encode($newPermissions)]);

        return $member->fresh();
    }

    /**
     * Remove permissions from member
     */
    public function removePermissions(VendorTeamMember $member, array $permissions): VendorTeamMember
    {
        if ($member->role === 'owner') {
            throw new \Exception('Cannot modify owner permissions.');
        }

        $currentPermissions = json_decode($member->permissions, true) ?? [];
        $newPermissions = array_diff($currentPermissions, $permissions);

        $member->update(['permissions' => json_encode($newPermissions)]);

        return $member->fresh();
    }

    /**
     * Deactivate member
     */
    public function deactivateMember(VendorTeamMember $member): void
    {
        if ($member->role === 'owner') {
            throw new \Exception('Owner cannot be deactivated.');
        }

        $member->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);

        event(new \App\Events\TeamMemberDeactivated($member));
    }

    /**
     * Reactivate member
     */
    public function reactivateMember(VendorTeamMember $member): void
    {
        $member->update([
            'status' => 'active',
            'deactivated_at' => null,
        ]);
    }

    /**
     * Remove member permanently
     */
    public function removeMember(VendorTeamMember $member): void
    {
        if ($member->role === 'owner') {
            throw new \Exception('Owner cannot be removed.');
        }

        $member->delete();
    }

    /**
     * Resend invitation
     */
    public function resendInvitation(VendorMemberInvitation $invitation): VendorMemberInvitation
    {
        if ($invitation->status !== 'pending') {
            throw new \Exception('Only pending invitations can be resent.');
        }

        if ($invitation->expires_at < now()) {
            throw new \Exception('Invitation has expired. Please create a new one.');
        }

        $invitation->update([
            'token' => Str::random(32),
            'resent_at' => now(),
            'resent_count' => ($invitation->resent_count ?? 0) + 1,
        ]);

        event(new \App\Events\TeamMemberInvitationResent($invitation));

        return $invitation;
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(VendorMemberInvitation $invitation): void
    {
        $invitation->update(['status' => 'cancelled']);
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(VendorTeamMember $member, string $permission): bool
    {
        if ($member->role === 'owner') {
            return true;
        }

        $permissions = json_decode($member->permissions, true) ?? [];
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    /**
     * Get available roles
     */
    public function getAvailableRoles(): array
    {
        return array_keys($this->roles);
    }

    /**
     * Get permissions for role
     */
    public function getPermissionsForRole(string $role): array
    {
        return $this->roles[$role] ?? [];
    }

    /**
     * Get member activity log
     */
    public function getMemberActivity(VendorTeamMember $member, array $filters = [])
    {
        $query = \App\Models\AuditLog::where(function ($q) use ($member) {
            $q->where('user_id', $member->user_id)
              ->orWhere('metadata->member_id', $member->id);
        });

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(50);
    }
}
