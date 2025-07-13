<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = User::where('user_type', 'patient')->get();

        foreach ($patients as $user) {
                Patient::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth'                 => Carbon::now()->subYears(rand(18, 60))->format('Y-m-d'),
                    'cpf'                           => 'CPF-' . Str::random(8),
                    'gender'                        => ['male', 'female', 'other'][rand(0, 2)],
                    'mother_name'                   => 'Mother of ' . $user->name,
                    'zipcode'                       => rand(10000, 99999) . '-' . rand(100, 999),
                    'house_number'                  => rand(1, 100),
                    'road'                          => 'Road ' . rand(1, 50),
                    'neighborhood'                  => 'Neighborhood ' . rand(1, 10),
                    'complement'                    => 'Block ' . chr(65 + rand(0, 4)),
                    'city'                          => 'City ' . rand(1, 5),
                    'state'                         => 'ST',
                    'profile_photo'                 => null,
                    'consulted'                     => 0,
                    'family_member_of_patient'      => 5,
                    'verification_status'           => 'pending',
                    'verification_rejection_reason' => null,
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ]
            );
        }
    }
}
