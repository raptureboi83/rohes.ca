<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // The stock Laravel seeder created a "test@example.com" account.
        // That is replaced by LegacyContentSeeder, which creates a single
        // real admin account and refuses to create one if an admin already
        // exists — so this stays safe to re-run on a live database.
        $this->call(LegacyContentSeeder::class);
    }
}
