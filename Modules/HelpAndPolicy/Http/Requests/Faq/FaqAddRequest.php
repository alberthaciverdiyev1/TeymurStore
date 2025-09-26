<?php

namespace Modules\HelpAndPolicy\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

class FaqAddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return  [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required',
        ];
    }

}
