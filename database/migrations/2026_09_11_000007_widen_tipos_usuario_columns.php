<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->soltarSiExiste('acceso', 'fk_acceso_tipo_usuario');
        $this->soltarSiExiste('users', 'fk_users_tipo_usuario');
        $this->soltarSiExiste('tipo_usuario_permiso', 'fk_tipo_usuario_permiso_tipo');

        DB::statement('ALTER TABLE tipos_usuario MODIFY tipo_usuario VARCHAR(100)');
        DB::statement('ALTER TABLE acceso MODIFY tipo_usuario VARCHAR(100)');
        DB::statement('ALTER TABLE users MODIFY tipo VARCHAR(100) NULL');
        DB::statement('ALTER TABLE tipo_usuario_permiso MODIFY tipo_usuario VARCHAR(100)');

        DB::statement('ALTER TABLE acceso ADD CONSTRAINT fk_acceso_tipo_usuario FOREIGN KEY (tipo_usuario) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE RESTRICT');
        DB::statement('ALTER TABLE users ADD CONSTRAINT fk_users_tipo_usuario FOREIGN KEY (tipo) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE RESTRICT');
        DB::statement('ALTER TABLE tipo_usuario_permiso ADD CONSTRAINT fk_tipo_usuario_permiso_tipo FOREIGN KEY (tipo_usuario) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->soltarSiExiste('acceso', 'fk_acceso_tipo_usuario');
        $this->soltarSiExiste('users', 'fk_users_tipo_usuario');
        $this->soltarSiExiste('tipo_usuario_permiso', 'fk_tipo_usuario_permiso_tipo');

        DB::statement('ALTER TABLE tipos_usuario MODIFY tipo_usuario CHAR(3)');
        DB::statement('ALTER TABLE acceso MODIFY tipo_usuario CHAR(3)');
        DB::statement('ALTER TABLE users MODIFY tipo CHAR(3) NULL');
        DB::statement('ALTER TABLE tipo_usuario_permiso MODIFY tipo_usuario CHAR(3)');

        DB::statement('ALTER TABLE acceso ADD CONSTRAINT fk_acceso_tipo_usuario FOREIGN KEY (tipo_usuario) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE RESTRICT');
        DB::statement('ALTER TABLE users ADD CONSTRAINT fk_users_tipo_usuario FOREIGN KEY (tipo) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE RESTRICT');
        DB::statement('ALTER TABLE tipo_usuario_permiso ADD CONSTRAINT fk_tipo_usuario_permiso_tipo FOREIGN KEY (tipo_usuario) REFERENCES tipos_usuario (tipo_usuario) ON UPDATE CASCADE ON DELETE CASCADE');
    }

    private function soltarSiExiste(string $tabla, string $constraint): void
    {
        try {
            DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$constraint}");
        } catch (Throwable) {

        }
    }
};
