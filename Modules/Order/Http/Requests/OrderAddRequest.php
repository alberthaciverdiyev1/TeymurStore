<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => auth()->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
        ];
    }
}
