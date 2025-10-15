<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\User\Http\Entities\User;
use Spatie\Permission\Models\Role;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole  = Role::where('name', 'user')->first();

        User::factory()->count(100)->create()->each(function ($user) use ($userRole) {
            $user->assignRole($userRole);
        });

        $admin = User::create([
            'name'             => 'albert haciverdiyev',
            'email'            => 'alberthaciverdiyev55@gmail.com',
            'password'         => bcrypt('123456'),
            'email_verified_at'=> Carbon::now(),
        ]);
        $admin->assignRole($adminRole);

        $user = User::create([
            'name'             => 'maharram paputu',
            'email'            => 'm.aliyev@gmail.com',
            'password'         => bcrypt('123456'),
            'email_verified_at'=> Carbon::now(),
        ]);
        $user->assignRole($adminRole);
    }
}
