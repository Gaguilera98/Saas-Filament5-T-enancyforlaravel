<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\Roles;

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * Limita roles al tenant actual y al guard del panel cliente (evita mezclar filas
 * con guard_name incorrecto o de otros tenants en el pool).
 */
final class RoleResource extends ShieldRoleResource
{
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $guard = Filament::getCurrentPanel()?->getAuthGuard() ?? 'tenant';
        $query->where('guard_name', $guard);

        if (Utils::isTenancyEnabled() && ($tenant = tenant())) {
            $query->where(Utils::getTenantModelForeignKey(), $tenant->getTenantKey());
        }

        return $query;
    }
}
