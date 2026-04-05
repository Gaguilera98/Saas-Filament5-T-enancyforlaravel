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
        if ($tenant = tenant()) {
            $connection = $tenant->db_pool ?? config('tenancy.database.central_connection');

            Config::set('database.default', $connection);
            DB::setDefaultConnection($connection);
        }

        return $next($request);
    }
}
