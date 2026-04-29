<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proforma\Tarif;

class ProformaTarifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tarifs = [
            // Tarifs séjour 20 pieds
            ['prscod' => 'TRA20P', 'prslib' => 'Transit 20P (1-3 jours)', 'prspun' => 150.00],
            ['prscod' => 'S1J20P', 'prslib' => 'Séjour 20P (4-15 jours)', 'prspun' => 200.00],
            ['prscod' => 'S2J20P', 'prslib' => 'Séjour 20P (16-25 jours)', 'prspun' => 300.00],
            ['prscod' => 'S3J20P', 'prslib' => 'Séjour 20P (26-35 jours)', 'prspun' => 400.00],
            ['prscod' => 'S4J20P', 'prslib' => 'Séjour 20P (36+ jours)', 'prspun' => 500.00],

            // Tarifs séjour 40 pieds
            ['prscod' => 'TRA40P', 'prslib' => 'Transit 40P (1-3 jours)', 'prspun' => 250.00],
            ['prscod' => 'S1J40P', 'prslib' => 'Séjour 40P (4-15 jours)', 'prspun' => 350.00],
            ['prscod' => 'S2J40P', 'prslib' => 'Séjour 40P (16-25 jours)', 'prspun' => 450.00],
            ['prscod' => 'S3J40P', 'prslib' => 'Séjour 40P (26-35 jours)', 'prspun' => 550.00],
            ['prscod' => 'S4J40P', 'prslib' => 'Séjour 40P (36+ jours)', 'prspun' => 650.00],

            // Tarifs gardiennage
            ['prscod' => 'GD0110', 'prslib' => 'Gardiennage (1-10 jours)', 'prspun' => 50.00],
            ['prscod' => 'GD1121', 'prslib' => 'Gardiennage (11-21 jours)', 'prspun' => 75.00],
            ['prscod' => 'GDS021', 'prslib' => 'Gardiennage (22+ jours)', 'prspun' => 100.00],

            // Tarifs acconage et manutention
            ['prscod' => 'ACCCAM', 'prslib' => 'Accès camion', 'prspun' => 500.00],
            ['prscod' => 'AC20PU', 'prslib' => 'Acconage 20P', 'prspun' => 1200.00],
            ['prscod' => 'AC40PU', 'prslib' => 'Acconage 40P', 'prspun' => 1800.00],
            ['prscod' => 'CDC20A', 'prslib' => 'Chargement/Déchargement 20P', 'prspun' => 800.00],
            ['prscod' => 'CDC40A', 'prslib' => 'Chargement/Déchargement 40P', 'prspun' => 1200.00],

            // Tarifs scanner
            ['prscod' => 'CRX20P', 'prslib' => 'Contrôle radiographique 20P', 'prspun' => 2500.00],
            ['prscod' => 'CRX40P', 'prslib' => 'Contrôle radiographique 40P', 'prspun' => 3500.00],
        ];

        foreach ($tarifs as $tarif) {
            Tarif::updateOrCreate(
                ['prscod' => $tarif['prscod']],
                $tarif
            );
        }

        $this->command->info('Tarifs proforma créés avec succès!');
    }
}