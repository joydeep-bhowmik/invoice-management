<?php

namespace Database\Seeders;

use App\Models\Note;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure user with ID 1 exists
        if (! \App\Models\User::where('email', 'test@example.com')->exists()) {
            $this->command->warn('User with ID 1 does not exist. Skipping NoteSeeder.');

            return;
        }

        Note::factory()
            ->count(50)
            ->create([
                'user_id' => 1,
            ]);
    }
}
