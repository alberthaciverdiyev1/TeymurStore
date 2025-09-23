<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'icon' => 'nullable|url|max:2048',
            'data' => 'nullable|array',
            'users' => ['nullable', 'array'],
            'users.*' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }


}
