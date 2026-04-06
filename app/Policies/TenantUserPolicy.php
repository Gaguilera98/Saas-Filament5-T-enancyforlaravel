<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(TenantUser $authUser): bool
    {
        return $authUser->can('ViewAny:TenantUser');
    }

    public function view(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('View:TenantUser');
    }

    public function create(TenantUser $authUser): bool
    {
        return $authUser->can('Create:TenantUser');
    }

    public function update(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('Update:TenantUser');
    }

    public function delete(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('Delete:TenantUser');
    }

    public function deleteAny(TenantUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TenantUser');
    }

    public function restore(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('Restore:TenantUser');
    }

    public function forceDelete(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('ForceDelete:TenantUser');
    }

    public function forceDeleteAny(TenantUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TenantUser');
    }

    public function restoreAny(TenantUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TenantUser');
    }

    public function replicate(TenantUser $authUser, TenantUser $model): bool
    {
        return $authUser->can('Replicate:TenantUser');
    }

    public function reorder(TenantUser $authUser): bool
    {
        return $authUser->can('Reorder:TenantUser');
    }
}
