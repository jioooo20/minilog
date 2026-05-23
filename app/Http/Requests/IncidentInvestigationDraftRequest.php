<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentInvestigationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investigation_notes' => ['nullable', 'string'],
            'root_cause_hypothesis' => ['nullable', 'string'],
        ];
    }
}