# Guía: Autenticación Multi-Tenant con Laravel + Filament + Tenancy

## 📋 Resumen del Problema Original

En una arquitectura multi-tenant con **base de datos compartida (pool-based)**, los usuarios de cada tenant se almacenan en una BD pool específica (ej: `centro_medico_pool_1`), pero la autenticación intentaba buscarlos en la BD central.

**Síntoma:** Login fallaba con "credenciales no existen" aunque el usuario existía en la BD correcta.

---

## 🎯 Solución: Middleware de Cambio de Conexión

La clave es cambiar la conexión de BD **ANTES** de que Filament intente autenticar usuarios.

### Flujo Correcto:

```
1. InitializeTenancyByDomain   → Identifica qué tenant es
2. SetTenantDatabaseConnection  → Cambia a la BD pool del tenant
3. StartSession                 → Inicia sesión en BD correcta
4. AuthenticateSession          → Autentica contra BD correcta
5. (otros middleware)
```

---

## 🛠️ Implementación Paso a Paso

### 1. Crear el Middleware

**Archivo:** `app/Http/Middleware/SetTenantDatabaseConnection.php`

```php
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
```

**¿Qué hace?**
- Verifica si existe un tenant en la solicitud (después de `InitializeTenancyByDomain`)
- Obtiene el nombre de la BD pool del tenant (ej: `pool_shared_1`)
- Establece esa conexión como la conexión BD por defecto

---

### 2. Configurar Conexiones de BD

**Archivo:** `config/database.php`

Añadir cada pool como conexión disponible:

```php
'connections' => [
    // ... otras conexiones ...
    
    'pool_shared_1' => [
        'driver'   => 'pgsql',
        'host'     => env('DB_POOL1_HOST', '127.0.0.1'),
        'port'     => env('DB_POOL1_PORT', '5432'),
        'database' => env('DB_POOL1_DATABASE', 'centro_medico_pool_1'),
        'username' => env('DB_POOL1_USERNAME', 'postgres'),
        'password' => env('DB_POOL1_PASSWORD', ''),
        'charset'  => 'utf8',
        'prefix'   => '',
        'search_path' => 'public',  
    ],
]
```

**Archivo:** `.env`

```env
# Conexión central (tenants y dominios)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=centro_medico_central
DB_USERNAME=postgres
DB_PASSWORD=tu_password

# Pool 1
DB_POOL1_HOST=127.0.0.1
DB_POOL1_PORT=5432
DB_POOL1_DATABASE=centro_medico_pool_1
DB_POOL1_USERNAME=postgres
DB_POOL1_PASSWORD=tu_password
```

---

### 3. Registrar el Middleware en Filament

**Archivo:** `app/Providers/Filament/ClientPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetTenantDatabaseConnection;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class ClientPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('client')
            ->path('client')
            ->viteTheme('resources/css/filament/client/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Client/Resources'), for: 'App\Filament\Client\Resources')
            ->discoverPages(in: app_path('Filament/Client/Pages'), for: 'App\Filament\Client\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Client/Widgets'), for: 'App\Filament\Client\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            // ⭐ IMPORTANTE: El orden IMPORTA
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                InitializeTenancyByDomain::class,           // ← Primero: identifica tenant
                SetTenantDatabaseConnection::class,         // ← Segundo: cambia BD
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                PreventAccessFromCentralDomains::class,
            ], true)  // ← El 'true' significa: persistent (corre en Livewire también)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

**Puntos Críticos:**
- `InitializeTenancyByDomain` ANTES de `SetTenantDatabaseConnection`
- `SetTenantDatabaseConnection` ANTES de `StartSession` (sesión usa BD actual)
- Segundo parámetro `true` = middleware **persistent** (importante para Filament + Livewire)

---

### 4. Registrar el Middleware en Rutas

**Archivo:** `routes/tenant.php`

```php
<?php

declare(strict_types=1);

use App\Http\Middleware\SetTenantDatabaseConnection;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    SetTenantDatabaseConnection::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });
});
```

---

### 5. Modelo Tenant

**Archivo:** `app/Models/Tenant.php`

Asegurar que el modelo tiene la columna `db_pool`:

```php
<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
{
    use HasDomains;

    protected $fillable = [
        'id',
        'clinic_name',
        'legal_name',
        'nit',
        'email',
        'phone',
        'city',
        'country',
        'timezone',
        'currency',
        'db_pool',      // ← IMPORTANTE
        'is_active',
        'onboarding_completed',
        'data',
    ];

    protected $casts = [
        'data'                 => 'array',
        'is_active'            => 'boolean',
        'onboarding_completed' => 'boolean',
    ];
}
```

---

### 6. Migración para Columna db_pool

**Asegurar que la tabla `tenants` tiene la columna:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'db_pool')) {
                $table->string('db_pool')->default('pool_shared_1')->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('db_pool');
        });
    }
};
```

---

## 📚 Estructura de BD Recomendada

```
CENTRAL DB (centro_medico_central)
├── tenants (id, db_pool, clinic_name, ...)
├── domains (id, domain, tenant_id)
└── sessions (solo si usas session_driver=database)

POOL 1 (centro_medico_pool_1)
├── users (id, email, password, tenant_id)
├── appointments
├── patients
└── ... (datos del tenant)

POOL 2 (centro_medico_pool_2)
├── users (id, email, password, tenant_id)
├── appointments
├── patients
└── ... (datos del tenant)
```

---

## ⚙️ Configuración Importante en `.env`

```env
# ⭐ Tenancy
APP_URL=http://miafarma.localhost:8000
CENTRAL_DOMAINS=localhost

# ⭐ Sessions (usar archivo, no database)
SESSION_DRIVER=file
# SESSION_CONNECTION=pgsql  ← Comentar esto
SESSION_LIFETIME=120

# ⭐ LOG para debugging
LOG_LEVEL=debug
APP_DEBUG=true
```

**¿Por qué `SESSION_DRIVER=file` y NO `database`?**

Si usas `SESSION_DRIVER=database`, la tabla `sessions` debe existir en la BD del pool. Si no existe, causará redirect loops. Es más simple usar archivos.

---

## 🔄 Flujo de Autenticación Completo

1. **Usuario accede a:** `http://miafarma.localhost:8000/client/login`

2. **Filament/Tenancy identifica:**
   - Host: `miafarma.localhost`
   - Busca en `domains` table la BD central
   - Encuentra: `tenant_id = 'miafarma'`, `db_pool = 'pool_shared_1'`

3. **Middleware SetTenantDatabaseConnection:**
   - Lee `tenant('db_pool')` → `'pool_shared_1'`
   - Ejecuta: `DB::setDefaultConnection('pool_shared_1')`
   - Todas las queries posteriores usan `centro_medico_pool_1`

4. **Filament autentica:**
   - User::where('email', $email) → busca en `pool_shared_1.users`
   - Verifica contraseña
   - Crea sesión

5. **En Dashboard:**
   - Todas las queries usan `pool_shared_1` automáticamente
   - Datos aislados por tenant

---

## 🐛 Debugging y Troubleshooting

### Problema: "Credenciales no existen"

**Verificar:**
1. ¿Existe el usuario en la BD pool?
   ```sql
   SELECT * FROM centro_medico_pool_1.public.users WHERE email = 'user@example.com';
   ```

2. ¿El tenant tiene `db_pool` asignado?
   ```sql
   SELECT id, db_pool FROM centro_medico_central.public.tenants WHERE id = 'miafarma';
   ```

3. ¿La conexión `pool_shared_1` está definida en `config/database.php`?

4. ¿El middleware está en el orden correcto?

### Problema: Redirect loop

**Causas comunes:**
- `SESSION_DRIVER=database` pero tabla `sessions` no existe en pool
- Middleware en orden incorrecto
- Middleware no marcado como persistent

**Solución:** Usar `SESSION_DRIVER=file`

### Problema: "Database [pool_shared_1] not configured"

**Verificar:**
- Que `pool_shared_1` esté definido en `config/database.php`
- Que las variables de entorno estén en `.env`
- Haber ejecutado `php artisan config:clear`

---

## ✅ Checklist de Implementación

- [ ] Crear middleware `SetTenantDatabaseConnection`
- [ ] Registrar middleware en `ClientPanelProvider` con orden correcto
- [ ] Registrar middleware en `routes/tenant.php`
- [ ] Definir conexión `pool_shared_1` en `config/database.php`
- [ ] Configurar variables de pool en `.env`
- [ ] Adicionar columna `db_pool` en tabla `tenants`
- [ ] Usar `SESSION_DRIVER=file` (no database)
- [ ] Ejecutar `php artisan config:clear && php artisan cache:clear`
- [ ] Probar login desde subdominio del tenant
- [ ] Verificar en `storage/logs/laravel.log` que no hay errores

---

## 🚀 Próximos Pasos Opcionales

### 1. Múltiples Pools
Crear múltiples pools para distribuir carga por tenant:

```php
// En el admin, permitir seleccionar pool al crear tenant
'pool_shared_1'  // Tenants pequeños
'pool_shared_2'  // Tenants medianos
'pool_vip'       // Tenants premium
```

### 2. Migraciones por Pool
Crear migraciones específicas para cada pool:

```bash
php artisan make:migration create_users_table --path=database/migrations/tenant
```

### 3. Backup y Disaster Recovery
Estrategia de respaldo para cada pool:
```bash
pg_dump centro_medico_pool_1 > backup_pool_1.sql
```

### 4. Monitoreo
Registrar queries lentas por tenant en `storage/logs/tenants/`

---

## 📝 Notas Importantes

> **⚠️ ORDEN DEL MIDDLEWARE ES CRÍTICO**
> 
> Si `InitializeTenancyByDomain` viene después de `StartSession`, la sesión se abre en la BD central.
> Si `SetTenantDatabaseConnection` viene después de `Authenticate`, la autenticación busca en la BD central.

> **⚠️ PERSISTENT MIDDLEWARE EN FILAMENT**
> 
> Filament usa Livewire. El segundo parámetro `true` en `->middleware([...], true)` es OBLIGATORIO
> para que el middleware se ejecute en las actualizaciones Livewire que no son cargas iniciales de página.

> **⚠️ SESSION DRIVER**
> 
> `SESSION_DRIVER=file` es más simple y evita problemas. Solo usa `database` si realmente necesitas
> compartir sesiones entre múltiples servidores.

---

## 📚 Referencias

- [Tenancy for Laravel - Docs](https://tenancyforlaravel.com/)
- [Filament Panels - Docs](https://filamentphp.com/docs)
- [Laravel Middleware - Docs](https://laravel.com/docs/middleware)

---

**Última actualización:** 2026-04-04
**Tested con:** Laravel 13, Filament 5.x, Tenancy for Laravel v3
