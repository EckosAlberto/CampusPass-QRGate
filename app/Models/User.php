<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'apellido_paterno', 'apellido_materno', 'email', 'password', 'username', 'tipo', 'activo', 'fecha_nacimiento', 'curp', 'rfc', 'telefono'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[Appends('rol_label')]
class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'activo' => 'boolean',
            'fecha_nacimiento' => 'date',
        ];
    }

    public function tipoUsuario()
    {
        return $this->belongsTo(TipoUsuario::class, 'tipo', 'tipo_usuario');
    }

    protected function rolLabel(): Attribute
    {
        return Attribute::get(fn () => $this->tipoUsuario?->descripcion_tipo);
    }

    public function tienePermiso(string $permisoId): bool
    {
        if (! $this->tipo) {
            return false;
        }

        return $this->tipoUsuario?->permisos()->where('permisos_biblioteca.id', $permisoId)->exists() ?? false;
    }
}
