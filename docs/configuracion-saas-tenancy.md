# Configuración: SaaS multi-tenant (pool compartido + Filament)

Este proyecto usa **Laravel**, **Stancl Tenancy v3** en modo **una base de datos por pool** (muchos tenants en la misma BD, separados por `tenant_id`) y **Filament** con dos paneles: administración central y panel cliente por dominio de tenant.

---

## Arquitectura resumida

| Capa | Base de datos | Conexión Laravel | Contenido típico |
|------|----------------|------------------|-------------------|
| **Central** | BD “central” | `DB_CONNECTION` (p. ej. `pgsql`) | `tenants`, `domains`, usuarios admin global, migraciones estándar |
| **Pool de tenants** | BD compartida (p. ej. `centro_medico_pool_1`) | `pool_shared_1` (y futuros `pool_shared_2`, etc.) | `users` con `tenant_id`, datos de negocio del tenant |

- **No** se crea una base de datos por tenant: varios tenants comparten el pool.
- El modelo `Tenant` tiene el campo **`db_pool`**: nombre de la conexión (`pool_shared_1`, …) que debe usar ese cliente. Permite escalar moviendo un tenant a otro pool o a una BD dedicada más adelante.
- Al inicializar el tenancy, la aplicación fija `database.default` a esa conexión (ver `TenancyServiceProvider` y el middleware del panel cliente).

---

## Requisitos

- PHP 8.3+
- Laravel 13
- PostgreSQL (configuración actual del proyecto)
- Paquetes: `stancl/tenancy`, `filament/filament`

---

## 1. Variables de entorno (`.env`)

### Aplicación y URL

- `APP_URL`: URL base del entorno (útil para enlaces; en local suele ser `http://localhost:8000` o similar).

### Base de datos central

Debe apuntar a la BD donde viven `tenants` y `domains`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=centro_medico_central
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

(Ajusta nombres según tu entorno.)

### Base de datos del pool (tenants compartidos)

La conexión se define en `config/database.php` como `pool_shared_1`:

```env
DB_POOL1_HOST=127.0.0.1
DB_POOL1_PORT=5432
DB_POOL1_DATABASE=centro_medico_pool_1
DB_POOL1_USERNAME=postgres
DB_POOL1_PASSWORD=tu_password
```

Para **otro pool**, añade en `config/database.php` una conexión nueva (p. ej. `pool_shared_2`) y variables `DB_POOL2_*`, y guarda en `tenants.db_pool` el nombre de esa conexión.

### Dominios centrales (obligatorio para Stancl)

Lista **separada por comas** (sin espacios o con cuidado al hacer `explode`). Solo estos hosts se consideran “app central”; el resto se tratan como posibles dominios de tenant.

Ejemplo local:

```env
CENTRAL_DOMAINS=localhost,127.0.0.1
```

Si usas subdominios de prueba:

```env
CENTRAL_DOMAINS=localhost,127.0.0.1,misistema.localhost
```

El **dominio del tenant** (p. ej. `miafarma.localhost`) **no** debe estar en esta lista.

### Sesión (recomendación para subdominios)

Revisa `SESSION_DOMAIN` si compartes cookies entre subdominios; en desarrollo a menudo se deja `null` o se acota al dominio base según necesites.

---

## 2. DNS / hosts locales

Cada tenant necesita un registro en la tabla `domains` (relacionado con `tenants`). Para desarrollo, añade en el archivo hosts del sistema entradas que apunten al mismo servidor que la app, por ejemplo:

```text
127.0.0.1   miafarma.localhost
```

Accede al panel cliente con una URL como: `http://miafarma.localhost:8000/client` (puerto según `php artisan serve`).

---

## 3. Migraciones

### Base central

Crea tablas estándar + `tenants` + `domains`:

```bash
php artisan migrate
```

Las migraciones en `database/migrations/` (excepto la carpeta `tenant`) corren contra la conexión por defecto (central).

### Pool (esquema compartido de tenants)

Las migraciones en **`database/migrations/tenant/`** deben aplicarse en **cada** base de datos de pool (una vez por BD física). Laravel no ejecuta esa subcarpeta con un `migrate` sin `--path`.

Con single-database + pool, **no** dependas solo de `tenants:migrate`: en este proyecto lo habitual es:

```bash
php artisan migrate --path=database/migrations/tenant --database=pool_shared_1
```

(Ajusta el nombre de conexión si usas otro pool.)

Detalle: `config/tenancy.php` sigue apuntando `migration_parameters` a `database/migrations/tenant` por si usas comandos de Stancl en otros escenarios; la referencia operativa está en **`docs/migraciones-pool.md`**.

---

## 4. Filament: dos paneles

| Panel | Provider | Ruta típica | Tenancy |
|-------|-----------|-------------|---------|
| Admin central | `AdminPanelProvider` | `/admin` | No inicializa tenant; usa BD central. |
| Cliente | `ClientPanelProvider` | `/client` | `InitializeTenancyByDomain` + `SetTenantDatabaseConnection` + `PreventAccessFromCentralDomains`. |

Recursos del admin (crear tenants, etc.) viven bajo `App\Filament\Resources`.  
Recursos del cliente bajo `App\Filament\Client\Resources`.

Los proveedores se registran en `bootstrap/providers.php`.

---

## 5. Configuración clave de Tenancy (`config/tenancy.php`)

- **`tenant_model`**: `App\Models\Tenant` (con `db_pool` y columnas personalizadas en `getCustomColumns()`).
- **`central_domains`**: desde `CENTRAL_DOMAINS` en `.env`.
- **`DatabaseTenancyBootstrapper`**: desactivado (no se cambia de BD automáticamente por el bootstrapper estándar; el cambio de conexión es manual vía `db_pool`).
- **`FilesystemTenancyBootstrapper`**: activo.
- **`asset_helper_tenancy`**: **`false`** — necesario para que los assets de Filament (`/js`, `/css`, fuentes) no se reescriban a `/tenancy/assets/...` (eso solo sirve para archivos en `storage` del tenant). Para logos/archivos por tenant, usar **`tenant_asset()`** donde corresponda.
- **`template_connection`**: `pool_shared_1` (referencia para jobs/comandos del paquete si los usas).

---

## 6. Eventos personalizados (`TenancyServiceProvider`)

- En **`TenancyInitialized`**: se asigna `database.default` y `DB::setDefaultConnection()` según `$tenant->db_pool` o, si falta, la conexión central.
- **`TenantCreated`**: los jobs `CreateDatabase` / `MigrateDatabase` están comentados (coherente con pool compartido).
- **Pendiente de revisar:** en **`TenantDeleted`** sigue registrado `Jobs\DeleteDatabase::class`, pensado para una BD por tenant. En un **pool compartido** es peligroso; antes de borrar tenants en producción, sustituir por borrado lógico o borrado de filas por `tenant_id`.

---

## 7. Modelos y datos en el pool

- Las tablas de negocio en el pool deben incluir **`tenant_id`** (o relación clara a un modelo raíz con `tenant_id`).
- Para Eloquent, conviene el trait **`BelongsToTenant`** de Stancl en modelos **primarios** (ver [single-database tenancy](https://tenancyforlaravel.com/docs/v3/single-database-tenancy/)).
- Validación `unique` / `exists`: acotar por `tenant_id` o usar helpers del paquete (`HasScopedValidationRules` en el modelo `Tenant`).

---

## 8. Documentación oficial

- [Tenancy for Laravel — Single-database tenancy](https://tenancyforlaravel.com/docs/v3/single-database-tenancy/)
- [Filament — Overview](https://filamentphp.com/docs/5.x/introduction/overview)

---

## Checklist al clonar el proyecto en otra máquina

1. `composer install`
2. Copiar `.env` y configurar `DB_*`, `DB_POOL1_*`, `CENTRAL_DOMAINS`
3. `php artisan key:generate`
4. Crear BDs central y pool en PostgreSQL
5. `php artisan migrate`
6. `php artisan tenants:migrate` (u operación equivalente para el pool)
7. `npm install && npm run build` (tema Vite del panel cliente)
8. Registrar dominios de prueba en `hosts` y crear tenant + dominio desde el panel admin

---

*Última revisión alineada con la estructura actual del repositorio (pool compartido + Filament 5).*
