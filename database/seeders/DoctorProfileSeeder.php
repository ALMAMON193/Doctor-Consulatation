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
        $doctors = User::where('user_type', 'doctor')->get();
        $specializations = [
            'Allergy and Immunology',
            'Anesthesiology',
            'Cardiology',
            'Dermatology',
            'Emergency Medicine',
            'Endocrinology',
            'Family Medicine',
            'Gastroenterology',
            'Geriatrics',
            'Hematology',
            'Infectious Disease',
            'Internal Medicine',
            'Nephrology',
            'Neurology',
            'Neurosurgery',
            'Obstetrics and Gynecology',
            'Oncology',
            'Ophthalmology',
            'Orthopedic Surgery',
            'Otolaryngology',
            'Pathology',
            'Pediatrics',
            'Physical Medicine and Rehabilitation',
            'Plastic Surgery',
            'Psychiatry',
            'Pulmonology',
            'Radiology',
            'Rheumatology',
            'Surgery',
            'Thoracic Surgery',
            'Urology',
            'Vascular Surgery',
        ];

        foreach ($doctors as $doctor) {
            // Skip if profile already exists
            $profileExists = DB::table('doctor_profiles')->where('user_id', $doctor->id)->exists();
            if ($profileExists) continue;

            // Insert into doctor_profiles
            DB::table('doctor_profiles')->insert([
                'user_id'                     => $doctor->id,
                'additional_medical_record_number' => Str::random(10),
                'specialization' => $specializations[array_rand($specializations)],
                'cpf_bank'                   => '12345678900',
                'bank_name'                  => 'Health Bank',
                'account_type'               => 'Savings',
                'account_number'             => '987654321',
                'dv'                         => '01',
                'crm'                        => 'CRM' . str_pad($doctor->id, 5, '0', STR_PAD_LEFT),
                'uf'                         => 'SP',
                'consultation_fee'           => rand(300 ,500),
                'monthly_income'             => rand(10000, 40000),
                'company_income'             => rand(5000, 20000),
                'company_phone'              => '0123456789',
                'company_name'               => 'MediaCenter ' . $doctor->id,
                'address_zipcode'            => '12345-678',
                'address_number'             => '12A',
                'address_street'             => 'Medical Street',
                'address_neighborhood'       => 'Doctor Zone',
                'address_city'               => 'Dhaka',
                'address_state'              => 'BD',
                'address_complement'         => 'Block B',
                'personal_name'              => $doctor->name,
                'date_of_birth'              => now()->subYears(rand(30, 50)),
                'cpf_personal'               => 'CPF-PERSONAL-' . $doctor->id . '-' . Str::random(6),
                'email'                      => $doctor->email,
                'phone_number'               => $doctor->phone_number,
                'video_path'                 => 'null',
                'profile_picture'            => 'null',
                'verification_status'        => 'pending',
                'verification_rejection_reason' => null,
                'is_active'                  => true,
                'last_seen'                  => now(),
                'created_at'                 => now(),
                'updated_at'                 => now(),
            ]);

            // Insert into user_addresses
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

            // Insert into user_personal_details
            $personalExists = DB::table('user_personal_details')->where('user_id', $doctor->id)->exists();
            if (!$personalExists) {
                DB::table('user_personal_details')->insert([
                    'user_id'      => $doctor->id,
                    'date_of_birth'=> now()->subYears(rand(30, 50)),
                    'cpf'          => 'CPF-' . $doctor->id . '-' . Str::random(6),  // Unique CPF here
                    'gender'       => ['male', 'female', 'other'][array_rand(['male', 'female', 'other'])],
                    'account_type' => ['individual', 'legalEntity'][array_rand(['individual', 'legalEntity'])],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
