<?php

namespace App\Policies\Pcb;

use App\Models\User;
use App\Models\Pcb\PcbProject;

class PcbProjectPolicy
{
    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can see their own projects
    }

    /**
     * Determine if the user can view the project.
     */
    public function view(User $user, PcbProject $project): bool
    {
        return $project->canUserAccess($user);
    }

    /**
     * Determine if the user can create projects.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create projects
    }

    /**
     * Determine if the user can update the project.
     */
    public function update(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()->where('user_id', $user->id)->first();
        return $member && $member->hasPermission('edit');
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(User $user, PcbProject $project): bool
    {
        // Only owner can delete
        return $project->user_id === $user->id;
    }

    /**
     * Determine if the user can upload files to the project.
     */
    public function uploadFiles(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()->where('user_id', $user->id)->first();
        return $member && $member->hasPermission('upload_files');
    }

    /**
     * Determine if the user can approve project milestones.
     */
    public function approve(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()->where('user_id', $user->id)->first();
        return $member && $member->hasPermission('approve');
    }

    /**
     * Determine if the user can invite members to the project.
     */
    public function inviteMembers(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()->where('user_id', $user->id)->first();
        return $member && $member->hasPermission('invite');
    }

    /**
     * Determine if the user can manage project members.
     */
    public function manageMembers(User $user, PcbProject $project): bool
    {
        return $this->update($user, $project);
    }
}
