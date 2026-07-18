<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf',
            ],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string', 'max:200'],
            'source' => ['nullable', 'string', 'in:create,investigation,repair,verification,closing'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Pilih minimal satu file untuk diupload.',
            'files.max' => 'Maksimal 5 file dalam satu kali upload.',
            'files.*.max' => 'Ukuran file maksimal 10MB.',
            'files.*.mimes' => 'Tipe file harus: jpg, jpeg, png, gif, webp, atau pdf.',
        ];
    }
}
