<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // 1. Logueamos el intento en producción
        \Illuminate\Support\Facades\Log::info("Intentando login web. Email: " . $this->email);
        
        try {
            $hasRole = $this->hasRole(config('filament-shield.super_admin.name'));
            \Illuminate\Support\Facades\Log::info("¿Tiene el rol en BD (Spatie)? " . ($hasRole ? 'SI' : 'NO'));
            \Illuminate\Support\Facades\Log::info("Roles actuales: ", $this->roles->pluck('name')->toArray());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error leyendo roles: " . $e->getMessage());
        }

        // 2. FORZAMOS EL ACCESO A TRUE para descartar fallas de Spatie
        return true;
    }
}
