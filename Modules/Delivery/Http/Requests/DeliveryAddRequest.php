<?php

namespace Modules\Delivery\Http\Requests;

use App\Enums\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DeliveryAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_name'     => ['required', new Enum(City::class)],
            'price'         => ['required', 'numeric', 'min:0'],
            'free_from'     => ['nullable', 'numeric', 'min:0'],
            'delivery_time' => ['nullable', 'string', 'max:255'],
            'is_active'     => ['boolean'],
        ];
    }
}
