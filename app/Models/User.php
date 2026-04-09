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

    /**
     * Define the access for Filament panels (Central Admin vs Tenant Client).
     * This overrides the default HasPanelShield implementation to properly
     * support Stancl/Tenancy and Livewire 3 lifecycle.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            // Query RAW a la BD ignorando el filtro de team_id de Spatie
            // ($this->roles() filtra por team_id internamente y falla durante el login)
            return \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $this->id)
                ->where('model_has_roles.model_type', static::class)
                ->where('roles.name', config('filament-shield.super_admin.name', 'super_admin'))
                ->exists();
        }

        if ($panel->getId() === 'client') {
            // Aquí puedes colocar tu lógica para cuando los usuarios entran al panel del tenant.
            // Usualmente si el usuario está atado a un TenantUser, devolver true.
            return true; 
        }

        return false;
    }
}
