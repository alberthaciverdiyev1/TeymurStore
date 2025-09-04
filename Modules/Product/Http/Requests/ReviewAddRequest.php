<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAddRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => auth()->id()
        ]);
    }

    public function rules()
    {
        return [
            'product_id' => 'required,exists:products,id',
            'rating' => 'required,between:1,5',
            'comment' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id'
        ];
    }
}
