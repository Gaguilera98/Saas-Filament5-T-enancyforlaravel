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
        // LOG DE EMERGENCIA EN PRODUCCIÓN
        \Illuminate\Support\Facades\Log::info('[DEBUG SUBDOMINIO]', [
            'host' => $request->getHost(),
            'path' => $request->path(),
            'domain_base' => config('app.url'),
            'tenancy_initialized' => tenancy()->initialized ? 'SÍ' : 'NO',
            'tenant_id' => tenant()?->id ?? 'NULO',
            'query_domains' => \Illuminate\Support\Facades\DB::connection('pgsql')->table('domains')->pluck('domain')->toArray(),
        ]);

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
