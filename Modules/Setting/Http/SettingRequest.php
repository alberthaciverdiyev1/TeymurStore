<?php

namespace Modules\Setting\Http;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instagram_url' => ['sometimes','nullable', 'string'],
            'tiktok_url' => ['sometimes','nullable', 'string'],
            'whatsapp_number' => ['sometimes','nullable', 'string'],
            'phone_number_1' => ['sometimes','nullable', 'string'],
            'phone_number_2' => ['sometimes','nullable', 'string'],
            'phone_number_3' => ['sometimes','nullable', 'string'],
            'phone_number_4' => ['sometimes','nullable', 'string'],
            'google_map_url' => ['sometimes','nullable', 'string'],
            'address' => ['sometimes','nullable', 'string'],

        ];
    }
}
