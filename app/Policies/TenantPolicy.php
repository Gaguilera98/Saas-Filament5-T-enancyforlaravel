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
        // Verificación directa en BD de la relación de roles,
        // sin depender del team_id de Spatie (que no está disponible en Livewire AJAX).
        $isSuperAdmin = $user->roles()
            ->where('name', config('filament-shield.super_admin.name', 'super_admin'))
            ->exists();

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
