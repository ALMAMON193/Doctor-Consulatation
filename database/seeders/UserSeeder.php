<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'phone_number' => '1234567891',
            'user_type' => 'admin',
            'is_verified' => true,
            'terms_and_conditions' => true,
            'email_verified_at' => now(),
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        //doctor
        DB::table('users')->insert([
            'name' => 'Doctor',
            'email' => 'doctor@doctor.com',
            'password' => Hash::make('12345678'),
            'phone_number' => '1234567892',
            'user_type' => 'doctor',
            'terms_and_conditions' => true,
            'email_verified_at' => now(),
            'is_verified' => true,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        //patient
        DB::table('users')->insert([
            'name' => 'Patient',
            'email' => 'patient@patient.com',
            'password' => Hash::make('12345678'),
            'phone_number' => '1234567893',
            'user_type' => 'patient',
            'is_verified' => true,
            'terms_and_conditions' => true,
            'email_verified_at' => now(),
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
