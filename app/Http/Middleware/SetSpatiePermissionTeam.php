<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tras resolver Stancl tenant y la conexión al pool, fija el "team" de Spatie (tenant_id)
 * para que hasRole / can() usen el contexto correcto.
 */
class SetSpatiePermissionTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($tenant = tenant()) {
            setPermissionsTeamId($tenant->getTenantKey());
        }

        return $next($request);
    }
}
