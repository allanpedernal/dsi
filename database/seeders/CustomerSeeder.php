<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(int $count = 20): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        Customer::factory($count)->create([
            'created_by' => $admin?->id,
        ]);
    }
}
