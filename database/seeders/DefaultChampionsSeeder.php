<?php

namespace Database\Seeders;

use App\Models\Champion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultChampionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Βρες τους πρώτους 3 champions και κάνε τους default unlocked
        $champions = Champion::orderBy('id')->limit(3)->get();
        
        foreach ($champions as $champion) {
            $champion->update(['is_default_unlocked' => true]);
        }

        // Ή αν θέλεις συγκεκριμένους champions (αν έχεις περάσει τους υποχρεωτικούς):
        // $mandatoryChampions = ['Malzahar', 'Shaco', 'Olaf'];
        // 
        // foreach ($mandatoryChampions as $championName) {
        //     Champion::where('name', $championName)->update(['is_default_unlocked' => true]);
        // }
        
        $this->command->info('Default unlocked champions set successfully!');
    }
}