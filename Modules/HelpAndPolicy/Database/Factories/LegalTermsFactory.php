<?php

namespace Modules\HelpAndPolicy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;

class LegalTermsFactory extends Factory
{
protected  $model = LegalTerm::class;

    public function definition()
    {
       return [
        'title' => $this->faker->sentence,
        'description' => $this->faker->paragraph,
       ];
    }
}
