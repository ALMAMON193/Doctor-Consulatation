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
            'Allergy and Immunology',
            'Anesthesiology',
            'Cardiology',
            'Cardiothoracic Surgery',
            'Critical Care Medicine',
            'Dermatology',
            'Emergency Medicine',
            'Endocrinology',
            'Family Medicine',
            'Gastroenterology',
            'General Surgery',
            'Geriatrics',
            'Hematology',
            'Infectious Disease',
            'Internal Medicine',
            'Medical Genetics',
            'Nephrology',
            'Neurology',
            'Neurosurgery',
            'Nuclear Medicine',
            'Obstetrics and Gynecology (OB/GYN)',
            'Oncology',
            'Ophthalmology',
            'Orthopedic Surgery',
            'Otolaryngology (ENT)',
            'Pathology',
            'Pediatrics',
            'Physical Medicine and Rehabilitation',
            'Plastic Surgery',
            'Podiatry',
            'Preventive Medicine',
            'Psychiatry',
            'Pulmonology',
            'Radiology',
            'Rheumatology',
            'Sleep Medicine',
            'Sports Medicine',
            'Surgical Oncology',
            'Thoracic Surgery',
            'Transplant Surgery',
            'Urology',
            'Vascular Surgery',
            'General Practice',
            'Occupational Medicine',
            'Pain Management',
            'Public Health',
            'Dentistry',
            'Chiropractic',
            'Osteopathic Medicine',
            'Speech-Language Pathology',
            'Audiology',
            'Genomic Medicine',
        ];

        foreach ($specializations as $name) {
            Specialization::firstOrCreate(['name' => $name]);
        }
    }
}
