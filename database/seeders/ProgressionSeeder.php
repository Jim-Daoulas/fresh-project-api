<?php

namespace Database\Seeders;

use App\Models\Champion;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgressionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting ProgressionSeeder...');
        
        // Ενημέρωση υπαρχόντων users με αρχικά points
        $updatedUsers = User::whereNull('points')->orWhere('points', 0)->update(['points' => 100]);
        $this->command->info("Updated {$updatedUsers} users with initial points");
        
        // Κάνε τους 3 πρώτους champions unlocked by default
        $defaultChampions = Champion::take(3)->update(['is_unlocked_by_default' => true]);
        $this->command->info("Set 3 champions as unlocked by default");
        
        // Κάνε το πρώτο skin κάθε champion unlocked by default
        $champions = Champion::with('skins')->get();
        $defaultSkins = 0;
        
        foreach ($champions as $champion) {
            if ($champion->skins->isNotEmpty()) {
                $champion->skins->first()->update(['is_unlocked_by_default' => true]);
                $defaultSkins++;
            }
        }
        
        $this->command->info("Set {$defaultSkins} skins as unlocked by default");
        
        // Ενημέρωση costs αν δεν είναι ήδη set
        $updatedChampionCosts = Champion::where('unlock_cost', 0)->update(['unlock_cost' => 30]);
        $updatedSkinCosts = Skin::where('unlock_cost', 0)->update(['unlock_cost' => 10]);
        
        $this->command->info("Updated {$updatedChampionCosts} champion costs to 30 points");
        $this->command->info("Updated {$updatedSkinCosts} skin costs to 10 points");
        
        $this->command->info('ProgressionSeeder completed successfully!');
    }
}