<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea el usuario admin en el pool y asigna el rol super_admin (Spatie + teams) para ese tenant.
 */
final class TenantPoolAdminProvisioner
{
    public function provision(
        Tenant $tenant,
        string $name,
        string $email,
        string $plainPassword,
    ): void {
        $connection = $tenant->db_pool ?? config('tenancy.database.central_connection');

        if (! Schema::connection($connection)->hasTable('roles')) {
            throw new \RuntimeException(
                'La base del pool no tiene tablas de permisos. Ejecuta: php artisan migrate --path=database/migrations/tenant --database='.$connection
            );
        }

        $previousDefault = Config::get('database.default');

        Config::set('database.default', $connection);
        DB::setDefaultConnection($connection);

        setPermissionsTeamId($tenant->id);

        try {
            DB::connection($connection)->transaction(function () use ($tenant, $name, $email, $plainPassword) {
                $user = TenantUser::query()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $plainPassword,
                    'role' => 'admin',
                    'is_active' => true,
                ]);

                $roleName = config('filament-shield.super_admin.name', 'super_admin');
                $guard = 'tenant';

                $role = Role::findOrCreate($roleName, $guard);

                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }

                // Con define_via_gate el acceso no depende de filas en role_has_permissions,
                // pero si ya existen permisos en el pool, los sincronizamos para que el UI muestre los checks.
                $permIds = Permission::query()->where('guard_name', $guard)->pluck('id');
                if ($permIds->isNotEmpty()) {
                    $role->syncPermissions($permIds);
                }
            });
        } finally {
            setPermissionsTeamId(null);
            Config::set('database.default', $previousDefault);
            DB::setDefaultConnection($previousDefault);
        }
    }
}
