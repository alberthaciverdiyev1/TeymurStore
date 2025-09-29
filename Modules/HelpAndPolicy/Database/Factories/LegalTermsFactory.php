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
           'html' => $this->faker->randomHtml,
           'type' => $this->faker->randomElement(['privacy_policy', 'terms_of_service', 'cookie_policy']),
       ];
    }
}
