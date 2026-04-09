<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    /**
     * Shield registra un "before" gate global para super_admin.
     * Este método es el respaldo explícito para el panel Admin central.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Seteamos el team central antes de verificar el rol
        $previousTeam = getPermissionsTeamId();
        setPermissionsTeamId(config('filament-shield.central_team_id', 0));

        $isSuperAdmin = $user->hasRole(config('filament-shield.super_admin.name', 'super_admin'));

        setPermissionsTeamId($previousTeam);

        return $isSuperAdmin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Tenant');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->can('View:Tenant');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Tenant');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->can('Update:Tenant');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->can('Delete:Tenant');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Tenant');
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->can('Restore:Tenant');
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->can('ForceDelete:Tenant');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Tenant');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Tenant');
    }

    public function replicate(User $user, Tenant $tenant): bool
    {
        return $user->can('Replicate:Tenant');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Tenant');
    }
}
