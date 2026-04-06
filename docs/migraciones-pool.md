# Migraciones del pool (BD compartida por tenants)

Las migraciones en `database/migrations/tenant/` definen el esquema que debe existir en **cada base de datos de pool** (donde viven `users` con `tenant_id`, permisos Spatie por tenant, etc.).

Laravel **no** ejecuta por defecto subcarpetas de `database/migrations/` al correr solo `php artisan migrate`; hay que indicar `--path`.

## Por qué no usar solo `php artisan tenants:migrate`

Con **Stancl** en modo **single-database / pool compartido** (`DatabaseTenancyBootstrapper` desactivado), `tenants:migrate` está pensado sobre todo para el flujo **multi-database** (una BD por tenant). En tu arquitectura el esquema del pool se aplica **una vez por base física de pool**, no “por fila de tenant”. Lo fiable es migrar **contra la conexión del pool** con `migrate` y `--path`.

## Comando recomendado

Desde la raíz del proyecto, usando la conexión definida en `config/database.php` (p. ej. `pool_shared_1`):

```bash
php artisan migrate --path=database/migrations/tenant --database=pool_shared_1
```

- **`--database=pool_shared_1`**: nombre de la conexión; debe coincidir con `DB_POOL1_*` en `.env` y con lo que guardes en `tenants.db_pool`.
- **`--path=...`**: solo archivos de esa carpeta (incluye `create_tenant_users_table` y `create_permission_tables` del pool).

Si añades **otro pool** (`pool_shared_2`), repite el mismo comando cambiando `--database=pool_shared_2` contra la nueva BD.

## Orden de archivos

Las migraciones en `tenant/` se ejecutan en orden por nombre de archivo. La de permisos (`2026_04_06_100000_create_permission_tables.php`) va **después** de la creación de `users` del tenant.

## Permisos (Spatie) en el pool

La migración de permisos del pool usa **`tenant_id` como string** (mismo tipo que `tenants.id` en Stancl), alineado con `config/permission.php` (`teams` + `team_foreign_key` = `tenant_id`). La migración de permisos en la **BD central** puede usar otro tipo según cuándo se generó; son **bases distintas**.

## Forzar en producción

```bash
php artisan migrate --path=database/migrations/tenant --database=pool_shared_1 --force
```

## Crear tenant desde el panel central

Al guardar un tenant en el admin, `CreateTenant` llama a `TenantPoolAdminProvisioner`: crea el usuario admin en el pool y le asigna el rol `super_admin` (nombre según `config/filament-shield.php`) usando **teams** (`tenant_id`). Si la tabla `roles` no existe aún en el pool, verás un error claro y una notificación en Filament; en ese caso ejecuta primero el comando de migración de arriba.

## Panel cliente: guard `tenant` y sesión

El panel `client` usa el guard **`tenant`** (`TenantUser`). Tras este cambio, cierra sesión y vuelve a entrar en `/client` con el usuario del pool.

Si creaste roles/permisos con `guard_name = web` antes del cambio, alinea en la BD del pool (o borra roles/permisos de prueba y vuelve a generar con `shield:generate --panel=client` cuando la conexión por defecto apunte al pool, o actualiza `guard_name` a `tenant` en las tablas `roles` y `permissions`).
