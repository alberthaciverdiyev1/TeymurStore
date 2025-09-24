<?php

namespace Modules\Product\Database\Factories;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Brand\Http\Entities\Brand;
use Modules\Category\Http\Entities\Category;
use Modules\Product\Http\Entities\Product;
use Modules\Color\Http\Entities\Color;
use Modules\Size\Http\Entities\Size;
use Modules\User\Http\Entities\User;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'title' => [
                'az' => $this->faker->word(),
                'en' => $this->faker->word(),
                'ru' => $this->faker->word(),
                'tr' => $this->faker->word(),
            ],
            'description' => [
                'az' => $this->faker->sentence(),
                'en' => $this->faker->sentence(),
                'ru' => $this->faker->sentence(),
                'tr' => $this->faker->sentence(),
            ],
            'sku' => strtoupper($this->faker->unique()->lexify('???###')),
            'brand_id' => Brand::inRandomOrder()->first()->id,
            'gender' => $this->faker->randomElement([
                Gender::MALE->value,
                Gender::FEMALE->value,
                Gender::KIDS->value,
            ]),
            'category_id' => Category::inRandomOrder()->first()->id,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'discount' => $this->faker->optional()->randomFloat(2, 1, 100),
            'stock_count' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
            'views' => $this->faker->numberBetween(0, 1000),
            'sales_count' => $this->faker->numberBetween(0, 500),
            'user_id' => User::inRandomOrder()->first()->id,
        ];
    }


    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            $colors = Color::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $product->colors()->attach($colors);

            $sizes = Size::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $product->sizes()->attach($sizes);

            $images = [
                ['image_path' => 'https://i.ibb.co/TBtqFwVM/meherrem.jpg']
            ];

            $product->images()->createMany($images);
        });
    }
}
