<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $globalTopics = [
            // Basic Sciences
            ['name' => 'Anatomy', 'description' => 'Structure of the human body'],
            ['name' => 'Physiology', 'description' => 'Function of organs and systems'],
            ['name' => 'Biochemistry', 'description' => 'Chemical processes in living organisms'],
            ['name' => 'Pathology', 'description' => 'Study of disease'],
            ['name' => 'Histology', 'description' => 'Microscopic anatomy of cells and tissues'],
            ['name' => 'Microbiology', 'description' => 'Study of microorganisms'],
            ['name' => 'Pharmacology', 'description' => 'Study of drugs and their effects'],
            ['name' => 'Immunology', 'description' => 'Study of the immune system'],

            // Clinical Sciences - Major Systems
            ['name' => 'Cardiovascular System', 'description' => 'Heart and blood vessels'],
            ['name' => 'Respiratory System', 'description' => 'Lungs and airways'],
            ['name' => 'Gastrointestinal System', 'description' => 'Digestive system'],
            ['name' => 'Renal System', 'description' => 'Kidneys and urinary system'],
            ['name' => 'Endocrine System', 'description' => 'Hormones and glands'],
            ['name' => 'Nervous System', 'description' => 'Brain, spinal cord, and nerves'],
            ['name' => 'Musculoskeletal System', 'description' => 'Bones, muscles, and joints'],
            ['name' => 'Hematology', 'description' => 'Blood and blood disorders'],

            // Clinical Specialties
            ['name' => 'Internal Medicine', 'description' => 'General adult medicine'],
            ['name' => 'Surgery', 'description' => 'Surgical procedures and principles'],
            ['name' => 'Pediatrics', 'description' => 'Medical care of children'],
            ['name' => 'Obstetrics & Gynecology', 'description' => 'Women\'s health and pregnancy'],
            ['name' => 'Psychiatry', 'description' => 'Mental health disorders'],
            ['name' => 'Emergency Medicine', 'description' => 'Acute care and emergencies'],
            ['name' => 'Family Medicine', 'description' => 'Primary care for all ages'],
            ['name' => 'Radiology', 'description' => 'Medical imaging'],
            ['name' => 'Anesthesiology', 'description' => 'Anesthesia and pain management'],
            ['name' => 'Dermatology', 'description' => 'Skin disorders'],
            ['name' => 'Ophthalmology', 'description' => 'Eye disorders'],
            ['name' => 'Otolaryngology', 'description' => 'Ear, nose, and throat'],
            ['name' => 'Orthopedics', 'description' => 'Bone and joint surgery'],
            ['name' => 'Urology', 'description' => 'Urinary tract and male reproductive system'],

            // Additional Topics
            ['name' => 'Infectious Diseases', 'description' => 'Bacterial, viral, and parasitic infections'],
            ['name' => 'Oncology', 'description' => 'Cancer and tumors'],
            ['name' => 'Clinical Skills', 'description' => 'Physical examination and procedures'],
            ['name' => 'Medical Ethics', 'description' => 'Ethical principles in medicine'],
            ['name' => 'Evidence-Based Medicine', 'description' => 'Research and statistics'],
            ['name' => 'Public Health', 'description' => 'Population health and prevention'],
        ];

        foreach ($globalTopics as $topic) {
            Topic::create([
                'name' => $topic['name'],
                'description' => $topic['description'],
                'organization_id' => null,
                'is_global' => true,
            ]);
        }
    }
}
