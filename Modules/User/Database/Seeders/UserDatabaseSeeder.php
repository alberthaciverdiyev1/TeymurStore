<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\User\Http\Entities\User;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(100)->create();
        User::create([
            'name'             => 'albert haciverdiyev',
            'email'              =>'alberthaciverdiyev55@gmail.com',
            'password'          => bcrypt('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        User::create([
            'name'             => 'maharram paputu',
            'email'              =>'m.aliyev@gmail.com',
            'password'          => bcrypt('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
