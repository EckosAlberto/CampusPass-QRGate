<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Personal;
use Illuminate\Support\Facades\DB;

class IdentificadorAcceso
{
    /** @return array{alumno: ?Alumno, personal: ?Personal} */
    public function resolver(string $valor): array
    {
        $valor = mb_strtoupper(trim($valor));

        $alumno = Alumno::query()
            ->where('no_de_control', $valor)
            ->orWhere('curp_alumno', $valor)
            ->first();

        if ($alumno) {
            return ['alumno' => $alumno, 'personal' => null];
        }

        $personal = Personal::query()
            ->where('rfc', $valor)
            ->first();

        if ($personal) {
            return ['alumno' => null, 'personal' => $personal];
        }

        $curp = DB::table('identificador_fotos')
            ->where('hash_foto', $valor)
            ->value('curp');

        if (! $curp) {
            return ['alumno' => null, 'personal' => null];
        }

        $alumno = Alumno::where('curp_alumno', mb_strtoupper($curp))->first();

        if ($alumno) {
            return ['alumno' => $alumno, 'personal' => null];
        }

        return [
            'alumno' => null,
            'personal' => Personal::where('curp_empleado', mb_strtoupper($curp))->first(),
        ];
    }
}
