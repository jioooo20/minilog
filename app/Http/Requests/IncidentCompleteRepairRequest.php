<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentCompleteRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'corrective_actions' => ['required', 'string'],
            'parts_replaced' => ['nullable', 'string'],
        ];
    }
}
