<?php

declare(strict_types=1);

namespace App\Jobs\Tenancy;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Limpia todos los registros del tenant en la base de datos del pool compartido.
 * Se usa en lugar de Jobs\DeleteDatabase (que intentaría borrar toda la BD).
 */
class CleanTenantPoolData
{
    public function __construct(
        protected TenantWithDatabase $tenant
    ) {}

    public function handle(): void
    {
        $pool = $this->tenant->db_pool;

        if (! $pool) {
            Log::warning("[CleanTenantPoolData] El tenant {$this->tenant->id} no tiene db_pool asignado. Nada que limpiar en el pool.");
            return;
        }

        $tenantId = $this->tenant->id;

        Log::info("[CleanTenantPoolData] Limpiando datos del tenant {$tenantId} en conexión {$pool}");

        // Ejecutamos todas las limpiezas en la conexión del pool
        $db = DB::connection($pool);

        // 1. Borrar usuarios del tenant
        $deletedUsers = $db->table('users')->where('tenant_id', $tenantId)->delete();
        Log::info("[CleanTenantPoolData] Usuarios eliminados: {$deletedUsers}");

        // 2. Borrar roles y permisos Spatie del tenant (si usas teams)
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');

        try {
            $deletedRoles = $db->table(config('permission.table_names.roles', 'roles'))
                ->where($teamKey, $tenantId)
                ->delete();
            Log::info("[CleanTenantPoolData] Roles eliminados: {$deletedRoles}");
        } catch (\Exception $e) {
            Log::warning("[CleanTenantPoolData] Error al eliminar roles: " . $e->getMessage());
        }

        Log::info("[CleanTenantPoolData] Limpieza completada para tenant {$tenantId}");
    }
}
