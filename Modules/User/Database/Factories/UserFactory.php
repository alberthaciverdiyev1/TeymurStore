<?php

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserFactory extends Factory
{
protected $model = \Modules\User\Http\Entities\User::class;
    public function definition()
    {
       return [
           'name' => $this->faker->name(),
           'email' => $this->faker->unique()->safeEmail(),
           'password' => bcrypt('123456'),
           'email_verified_at'=>Carbon::now()
       ];
    }
}
