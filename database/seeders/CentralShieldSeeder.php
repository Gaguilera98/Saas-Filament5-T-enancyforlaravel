<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea o unifica el rol super_admin en la BD central (teams) y lo asigna al primer usuario.
 *
 * Evita duplicados: Spatie permite (NULL, super_admin, web) y (0, super_admin, web) como filas
 * distintas; shield:generate sin setPermissionsTeamId suele crear el primero, el seeder el segundo.
 *
 * php artisan db:seed --class=CentralShieldSeeder
 */
final class CentralShieldSeeder extends Seeder
{
    public function run(): void
    {
        $teamKey = config('permission.column_names.team_foreign_key');
        $centralTeamId = config('filament-shield.central_team_id');
        $roleName = config('filament-shield.super_admin.name', 'super_admin');
        $guard = 'web';

        $previous = getPermissionsTeamId();

        try {
            $canonical = $this->mergeDuplicateSuperAdminRoles($teamKey, $centralTeamId, $roleName, $guard);

            $user = User::query()->first();
            if ($user === null) {
                $this->command?->warn('No hay usuarios en la BD central; crea un User antes de ejecutar este seeder.');

                return;
            }

            setPermissionsTeamId($centralTeamId);

            if (! $user->hasRole($canonical)) {
                $user->assignRole($canonical);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->command?->info(sprintf(
                'Rol único %s (id=%s, guard %s, %s=%s) asignado a %s.',
                $roleName,
                (string) $canonical->getKey(),
                $guard,
                $teamKey,
                (string) $canonical->getAttribute($teamKey),
                $user->email,
            ));
        } finally {
            setPermissionsTeamId($previous);
        }
    }

    /**
     * Deja un solo rol super_admin (web) con tenant_id = central_team_id; fusiona permisos y usuarios.
     */
    private function mergeDuplicateSuperAdminRoles(
        string $teamKey,
        int|string $centralTeamId,
        string $roleName,
        string $guard,
    ): Role {
        $canonical = Role::query()->firstOrCreate(
            [
                $teamKey => $centralTeamId,
                'name' => $roleName,
                'guard_name' => $guard,
            ],
        );

        $duplicates = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', $guard)
            ->whereKeyNot($canonical->getKey())
            ->get();

        foreach ($duplicates as $dup) {
            foreach ($dup->permissions as $perm) {
                if (! $canonical->hasPermissionTo($perm)) {
                    $canonical->givePermissionTo($perm);
                }
            }

            $this->moveUsersToCanonicalRole($dup, $canonical, $teamKey, $centralTeamId);

            $dup->delete();
        }

        return $canonical->fresh();
    }

    private function moveUsersToCanonicalRole(
        Role $from,
        Role $to,
        string $teamKey,
        int|string $centralTeamId,
    ): void {
        $pivot = config('permission.table_names.model_has_roles');

        $rows = DB::table($pivot)
            ->where('role_id', $from->getKey())
            ->where('model_type', User::class)
            ->get();

        foreach ($rows as $row) {
            $pivotTeam = $row->{$teamKey} ?? null;

            $user = User::query()->find($row->model_id);
            if ($user === null) {
                continue;
            }

            setPermissionsTeamId($pivotTeam);
            if ($user->hasRole($from)) {
                $user->removeRole($from);
            }
        }

        setPermissionsTeamId($centralTeamId);

        foreach ($rows as $row) {
            $user = User::query()->find($row->model_id);
            if ($user === null) {
                continue;
            }

            if (! $user->hasRole($to)) {
                $user->assignRole($to);
            }
        }
    }
}
