<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del panel cliente (tabla `users` en la BD del pool, con tenant_id).
 */
class TenantUser extends Authenticatable
{
    use HasRoles, Notifiable;

    /**
     * Mismo guard que el panel cliente (`authGuard('tenant')`) y los roles en el pool.
     */
    protected string $guard_name = 'tenant';

    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
