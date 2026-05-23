<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentProposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'root_cause_hypothesis' => ['required', 'string', 'min:10'],
            'investigation_notes' => ['nullable', 'string'],
        ];
    }
}
