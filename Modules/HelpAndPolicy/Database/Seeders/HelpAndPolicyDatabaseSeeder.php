<?php

namespace Modules\HelpAndPolicy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpAndPolicy\Http\Entities\Faq;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;

class HelpAndPolicyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Faq::factory()->count(50)->create();

        LegalTerm::create([
            'type' => 'terms_of_service',
            'html' => '<h1>Terms of Service</h1><p>These are the terms of service...</p>'
        ]);
        LegalTerm::create([
            'type' => 'privacy_policy',
            'html' => '<h1>Privacy Policy</h1><p>This is the privacy policy...</p>'
        ]);
    }
}
