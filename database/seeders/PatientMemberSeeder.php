<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\PatientMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PatientMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $relationships = ['Father', 'Mother', 'Sibling', 'Child', 'Spouse'];
        $patients = \App\Models\Patient::all();

        foreach ($patients as $patient) {
            $user = $patient->user; // Ensure relation exists

            foreach ($relationships as $relation) {
                \App\Models\PatientMember::create([
                    'patient_id'    => $patient->id,
                    'name'          => $relation . ' of ' . $user->name,
                    'date_of_birth' => now()->subYears(rand(10, 50)),
                    'relationship'  => $relation,
                    'cpf'           => 'CPF-' . Str::random(6),
                    'gender'        => ['male', 'female', 'other'][rand(0, 2)],
                    'profile_photo' => null,
                ]);
            }
        }
    }


}
