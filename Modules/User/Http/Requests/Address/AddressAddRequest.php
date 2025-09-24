<?php

namespace Modules\User\Http\Requests\Address;

use App\Enums\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AddressAddRequest extends FormRequest
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
            'city' => ['required', new Enum(City::class)],
            'town_village_district' => 'required|string|max:255',
            'street_building_number' => ['required', 'string','max:255'],
            'unit_floor_apartment' => ['required', 'string','max:255'],
            'is_default' => ['nullable', 'boolean'],
            'full_name' => ['required', 'string','max:255'],
            'contact_number' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
        ];
    }
}
