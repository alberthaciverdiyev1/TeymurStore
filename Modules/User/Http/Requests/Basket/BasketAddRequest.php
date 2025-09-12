<?php

namespace Modules\User\Http\Requests\Basket;

use Illuminate\Foundation\Http\FormRequest;

class BasketAddRequest extends FormRequest
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
            'product_id' => ['required', 'integer','exists:products,id'],
            'user_id' => ['required', 'integer','exists:users,id'],
            'gender' => ['nullable'],
            'size_id' => ['nullable'],
            'color_id' => ['nullable'],
        ];
    }
}
