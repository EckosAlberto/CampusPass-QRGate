<?php

namespace App\Models;

use Database\Factories\EventosUsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'apellido_paterno', 'apellido_materno', 'email', 'password', 'rol_id', 'activo', 'fecha_nacimiento', 'curp', 'rfc', 'telefono'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
#[Appends('rol_label')]
class EventosUsuario extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'usuarios_eventos';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'fecha_nacimiento' => 'date',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(RolEventos::class, 'rol_id');
    }

    protected function rolLabel(): Attribute
    {
        return Attribute::get(fn () => $this->rol?->nombre);
    }

    public function tienePermiso(string $permisoId): bool
    {
        if (! $this->rol_id) {
            return false;
        }

        return $this->rol?->permisos()->where('permisos_eventos.id', $permisoId)->exists() ?? false;
    }
}
