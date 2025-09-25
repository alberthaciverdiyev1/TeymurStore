<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'title.az' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],

            'description.az' => ['required', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.ru' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],

            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
            ],

            'brand_id' => ['nullable', 'exists:brands,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'kids'])],
            'category_id' => ['nullable', 'exists:categories,id'],

            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'views' => ['nullable', 'integer', 'min:0'],
            'sales_count' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['required', 'exists:users,id'],
            'is_suggest' => ['boolean'],

            'colors' => ['nullable', 'array'],
            'colors.*' => ['exists:colors,id'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['exists:sizes,id'],

            'images'   => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,tiff,avif'],
        ];
    }
}
