<?php

namespace Modules\HelpAndPolicy\Http\Requests\LegalTerm;

use Illuminate\Foundation\Http\FormRequest;

class LegalTermAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return  [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'html' => 'nullable|string',
        ];
    }
}
