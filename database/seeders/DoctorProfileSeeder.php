<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class DoctorProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all specialization IDs
        $specializations = DB::table('specializations')->pluck('id')->toArray();

        if (empty($specializations)) {
            $this->command->warn('No specializations found. Please run SpecializationSeeder first.');
            return;
        }

        // Fetch all doctors
        $doctors = User::where('user_type', 'doctor')->get();

        foreach ($doctors as $doctor) {
            // Skip if profile already exists
            $profileExists = DB::table('doctor_profiles')->where('user_id', $doctor->id)->exists();
            if ($profileExists) continue;

            // Insert doctor profile (without JSON specialization)
            $doctorId = DB::table('doctor_profiles')->insertGetId([
                'user_id'                     => $doctor->id,
                'cpf_bank'                   => '12345678900',
                'bank_name'                  => 'Health Bank',
                'account_type'               => 'Savings',
                'account_number'             => '987654321',
                'dv'                         => '01',
                'current_account_number'     => '987654321',
                'current_dv'                 => '01',
                'crm'                        => 'CRM' . str_pad($doctor->id, 5, '0', STR_PAD_LEFT),
                'uf'                         => 'SP',
                'monthly_income'             => rand(10000, 40000),
                'company_income'             => rand(5000, 20000),
                'company_phone'              => '0123456789',
                'company_name'               => 'MediaCenter ' . $doctor->id,
                'zipcode'                    => '12345-678',
                'address'                    => 'Dhaka Bangladesh',
                'road_number'                => '12A',
                'house_number'               => '12A',
                'neighborhood'               => 'Doctor Zone',
                'city'                       => 'Dhaka',
                'state'                      => 'BD',
                'complement'                 => 'Block B',
                'personal_name'              => $doctor->name,
                'date_of_birth'              => now()->subYears(rand(30, 50)),
                'cpf_personal'               => 'CPF-PERSONAL-' . $doctor->id . '-' . Str::random(6),
                'email'                      => $doctor->email,
                'phone_number'               => $doctor->phone_number,
                'video_path'                 => null,
                'profile_picture'            => null,
                'bio'                        => Str::random(200),
                'verification_status'        => 'pending',
                'verification_rejection_reason' => null,
                'is_active'                  => true,
                'last_seen'                  => now(),
                'created_at'                 => now(),
                'updated_at'                 => now(),
            ]);

            // Assign 1–3 random specializations to pivot table
            $randomSpecializations = collect($specializations)
                ->random(rand(1, min(3, count($specializations))))
                ->values()
                ->all();

            $pivotData = [];
            foreach ($randomSpecializations as $specId) {
                $pivotData[] = [
                    'doctor_id' => $doctorId,
                    'specialization_id' => $specId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('doctor_specializations')->insert($pivotData);

            // Optional: insert addresses or personal details if needed
            $addressExists = DB::table('user_addresses')->where('user_id', $doctor->id)->exists();
            if (!$addressExists) {
                DB::table('user_addresses')->insert([
                    'user_id'                   => $doctor->id,
                    'monthly_income'            => rand(10000, 40000),
                    'annual_income_for_company' => rand(50000, 100000),
                    'company_telephone_number'  => '0123456789',
                    'business_name'             => 'MediCenter ' . $doctor->id,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }

            $personalExists = DB::table('user_personal_details')->where('user_id', $doctor->id)->exists();
            if (!$personalExists) {
                DB::table('user_personal_details')->insert([
                    'user_id'      => $doctor->id,
                    'date_of_birth'=> now()->subYears(rand(30, 50)),
                    'cpf'          => 'CPF-' . $doctor->id . '-' . Str::random(6),
                    'gender'       => ['male', 'female', 'other'][array_rand(['male', 'female', 'other'])],
                    'account_type' => ['individual', 'legalEntity'][array_rand(['individual', 'legalEntity'])],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
