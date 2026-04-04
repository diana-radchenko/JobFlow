<?php

namespace Database\Seeders;

use Database\Factories\WorkJobFactory;
use Illuminate\Database\Seeder;

class WorkJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (WorkJobFactory::mockReadyData() as $data) {
            \App\Models\WorkJob::create($data);
        }
    }
}
