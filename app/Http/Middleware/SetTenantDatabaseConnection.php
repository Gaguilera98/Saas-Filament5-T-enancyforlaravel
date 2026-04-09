<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SetTenantDatabaseConnection
{
    public function handle(Request $request, Closure $next)
    {
        // ===== LOGS DE DIAGNÓSTICO (quitar en producción estable) =====
        \Illuminate\Support\Facades\Log::info('[Tenancy] Request entrante', [
            'host'            => $request->getHost(),
            'full_url'        => $request->fullUrl(),
            'tenant_active'   => tenancy()->initialized ? 'SÍ' : 'NO',
            'tenant_id'       => tenant()?->id ?? 'ninguno',
            'tenant_db_pool'  => tenant()?->db_pool ?? 'ninguno',
            'central_domains' => config('tenancy.central_domains'),
        ]);
        // ==============================================================

        if ($tenant = tenant()) {
            $connection = $tenant->db_pool ?? config('tenancy.database.central_connection');

            \Illuminate\Support\Facades\Log::info('[Tenancy] Conectando BD', [
                'tenant_id'  => $tenant->id,
                'connection' => $connection,
            ]);

            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);
        } else {
            \Illuminate\Support\Facades\Log::warning('[Tenancy] No se encontró tenant para el host: ' . $request->getHost());
        }

        return $next($request);
    }
}
