<?php

namespace Modules\Product\Http\Resources;

use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Brand\Http\Transformers\BrandResource;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Color\Http\Transformers\ColorResource;
use Modules\Size\Http\Transformers\SizeResource;
use Modules\User\Http\UserResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title'=>$this->title,
            'description'=>$this->description,
            'sku' => $this->sku,
            'rate'=>$this->rate,
            'rate_count'=>$this->rate_count,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'gender' => $this->gender !== null ? Gender::fromInt($this->gender)->label() : null,
            'price' => $this->price,
            'views' => $this->views,
            'discount' => $this->discount ?? 0,
            'stock_count' => $this->stock_count ?? 0,
            'is_active' => $this->is_active,
            'sale_count' => $this->sale_count ?? 0,
           // 'title' => $this->getTranslation('title', $locale, false) ?? $this->getTranslation('title', 'az'),
            //'description' => $this->getTranslation('description', $locale, false) ?? $this->getTranslation('description', 'az'),
            'colors' => ColorResource::collection($this->whenLoaded('colors')),
            'sizes' => SizeResource::collection($this->whenLoaded('sizes')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => new UserResource($this->whenLoaded('user')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
