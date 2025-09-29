<?php

namespace Modules\HelpAndPolicy\Http\Requests\LegalTerm;

use Illuminate\Foundation\Http\FormRequest;

class LegalTermUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return  [
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'html' => 'sometimes|nullable|string',
        ];
    }
}
