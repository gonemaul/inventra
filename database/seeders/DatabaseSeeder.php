<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $data = [
            ['code' => 'CT001', 'name' => 'Elektronik'],
            ['code' => 'CT002', 'name' => 'Pakaian'],
            ['code' => 'CT003', 'name' => 'Software'],
            ['code' => 'CT004', 'name' => 'Kesehatan'],
            ['code' => 'CT005', 'name' => 'Makanan'],
        ];

        foreach ($data as $item) {
            Category::create($item);
        }
    }
}
