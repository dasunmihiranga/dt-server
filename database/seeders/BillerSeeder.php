<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Biller;

class BillerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billers = [
            [
                'name' => 'Electricity Company',
                'category' => 'Utilities',
                'description' => 'Monthly electricity bill payment',
                'is_active' => true,
            ],
            [
                'name' => 'Water Department',
                'category' => 'Utilities',
                'description' => 'Monthly water bill payment',
                'is_active' => true,
            ],
            [
                'name' => 'Internet Provider',
                'category' => 'Telecommunications',
                'description' => 'Monthly internet service payment',
                'is_active' => true,
            ],
            [
                'name' => 'Gas Company',
                'category' => 'Utilities',
                'description' => 'Monthly gas bill payment',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Network',
                'category' => 'Telecommunications',
                'description' => 'Mobile phone bill payment',
                'is_active' => true,
            ],
        ];

        foreach ($billers as $biller) {
            Biller::create($biller);
        }
    }
}
