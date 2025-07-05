<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
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

        // 20 Doctors
        for ($i = 1; $i <= 20; $i++) {
            DB::table('users')->insert([
                'name' => "Doctor $i",
                'email' => "doctor$i@gmail.com",
                'password' => Hash::make('12345678'),
                'phone_number' => '12345678' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'user_type' => 'doctor',
                'is_verified' => true,
                'terms_and_conditions' => true,
                'email_verified_at' => now(),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        // 20 Doctors
        for ($i = 1; $i <= 20; $i++) {
            DB::table('users')->insert([
                'name' => "Patient $i",
                'email' => "patient$i@gmail.com",
                'password' => Hash::make('12345678'),
                'phone_number' => '1234567' . str_pad($i, 2, '0', STR_PAD_LEFT),
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
}
