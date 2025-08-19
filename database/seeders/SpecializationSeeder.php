<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            'General Practice (General Medicine)',
            'Pediatrics',
            'Gynecology & Obstetrics (OB/GYN)',
            'Dermatology',
            'Psychiatry',
            'Psychology',
            'Cardiology',
            'Endocrinology (Diabetes, Thyroid, Hormonal disorders)',
            'Neurology',
            'Pulmonology (Respiratory medicine)',
            'Infectious Disease',
            'Rheumatology (Arthritis & autoimmune diseases)',
            'Ophthalmology (Eye care)',
            'Urology',
        ];

        foreach ($specializations as $name) {
            Specialization::firstOrCreate(
                ['name' => $name],
                ['price' => 109.00] // default price
            );
        }
    }
}
