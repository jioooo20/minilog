<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'item_id' => ['required', 'integer', 'exists:items,item_id'],
            'component_item_id' => ['nullable', 'integer', 'exists:items,item_id'],
            'location_id' => ['required', 'integer', 'exists:locations,location_id'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'detected_at' => ['nullable', 'date'],
            'incident_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
