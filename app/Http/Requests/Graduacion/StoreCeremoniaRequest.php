<?php

namespace App\Http\Requests\Graduacion;

use App\Concerns\CeremoniaValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCeremoniaRequest extends FormRequest
{
    use CeremoniaValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->ceremoniaRules();
    }
}
