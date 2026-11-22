<?php

namespace Database\Seeders;

use App\Models\ConfiguracionBiblioteca;
use App\Models\EventosUsuario;
use App\Models\GraduacionUsuario;
use App\Models\TipoUsuario;
use App\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \LogicException('DatabaseSeeder contiene usuarios y registros de demostración; no debe ejecutarse en producción.');
        }

        $this->call([
            TipoUsuarioSeeder::class,
            PermisoBibliotecaSeeder::class,
            HorarioBibliotecaSeeder::class,
            ExcepcionHorarioBibliotecaSeeder::class,
            NotificacionEventoBibliotecaSeeder::class,
        ]);

        ConfiguracionBiblioteca::actual();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'tipo' => TipoUsuario::ADMINISTRADOR,
            'activo' => true,
        ]);

        EventosUsuario::query()->firstOrCreate(
            ['email' => 'eventos@campuspass.test'],
            [
                'name' => 'Coordinador de Eventos',
                'password' => Hash::make('password'),
            ],
        );

        GraduacionUsuario::query()->firstOrCreate(
            ['email' => 'graduacion@campuspass.test'],
            [
                'name' => 'Coordinador de Graduación',
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            PeriodoEscolarSeeder::class,
            CarreraSeeder::class,
            TipoTutorSeeder::class,
            TipoEventoSeeder::class,
            PeriodoTutoriasSeeder::class,
            PersonalSeeder::class,
            TutorSeeder::class,
            AlumnoSeeder::class,
            EventoTutoriasSeeder::class,
            RegistroAccesoSeeder::class,
            CeremoniaGraduacionSeeder::class,
            RolPermisoEventosSeeder::class,
            RolPermisoGraduacionSeeder::class,
        ]);
    }
}
