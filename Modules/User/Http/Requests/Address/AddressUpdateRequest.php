<?php

namespace Modules\User\Http\Requests\Address;

use App\Enums\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AddressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city' => ['sometimes', 'required', new Enum(City::class)],
            'town_village_district' => ['sometimes', 'required', 'string', 'max:255'],
            'street_building_number' => ['sometimes', 'required', 'string', 'max:255'],
            'unit_floor_apartment' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
        ];
    }
}
