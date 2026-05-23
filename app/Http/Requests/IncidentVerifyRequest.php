<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'passed' => ['required', 'boolean'],
            'verification_notes' => ['required_if:passed,false', 'string', 'nullable'],
        ];
    }
}
