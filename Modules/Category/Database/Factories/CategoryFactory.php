<?php

namespace Modules\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Category\Http\Entities\Category;

class CategoryFactory extends Factory
{
protected $model = Category::class;

    public function definition()
    {
       return [
           'name' => $this->faker->name(),
           'image' => $this->faker->imageUrl(),
           'description' => $this->faker->text(),
           'parent_id' => null,

       ];
    }
}
