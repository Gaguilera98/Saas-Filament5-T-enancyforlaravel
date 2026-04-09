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
            // Query directa a la relación de roles sin depender del team_id de Spatie,
            // para evitar falsos negativos en peticiones Livewire AJAX.
            return $this->roles()
                ->where('name', config('filament-shield.super_admin.name', 'super_admin'))
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
