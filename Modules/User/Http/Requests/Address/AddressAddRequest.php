<?php

namespace Modules\User\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

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
            'city' => 'required|string|max:255',
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
