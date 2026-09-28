<?php

namespace App\Http\Requests\Eventos;

use App\Concerns\EventoValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventoRequest extends FormRequest
{
    use EventoValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->eventoRules();
    }
}
