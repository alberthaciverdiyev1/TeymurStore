<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Brand\Database\Seeders\BrandDatabaseSeeder;
use Modules\Category\Database\Seeders\CategoryDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            CategoryDatabaseSeeder::class,
            BrandDatabaseSeeder::class,
        ]);
    }
}
