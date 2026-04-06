<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En el panel admin (BD central) fija el team de Spatie para que hasRole / can()
 * resuelvan roles con tenant_id = central_team_id (ver config/filament-shield.php).
 */
final class SetCentralSpatiePermissionTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $teamId = config('filament-shield.central_team_id');
        $previous = getPermissionsTeamId();

        setPermissionsTeamId($teamId);

        try {
            return $next($request);
        } finally {
            setPermissionsTeamId($previous);
        }
    }
}
