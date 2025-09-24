<?php

namespace Modules\Delivery\Http\Requests;

use App\Enums\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DeliveryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name'     => ['sometimes', 'required', new Enum(City::class)],
            'price'         => ['sometimes', 'required', 'numeric', 'min:0'],
            'free_from'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'delivery_time' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}
