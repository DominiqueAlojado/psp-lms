<?php

namespace Database\Seeders;

use App\Models\LearningResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearningResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Bataan General Hospital
        $organization = Organization::where('slug', 'bataan-general-hospital')->first();

        if (! $organization) {
            $this->command->error('Bataan General Hospital not found. Please create it first.');

            return;
        }

        // Find a user to be the uploader
        $uploader = $organization->users()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['System Admin', 'Training Officer', 'Admin']);
        })->first() ?? User::role('System Admin')->first();

        if (! $uploader) {
            $this->command->error('No user found to be the uploader.');

            return;
        }

        $this->command->info("Creating learning resources for {$organization->name}...");

        $resources = [
            [
                'title' => 'Internal Medicine Clerkship Handbook 2024',
                'description' => 'Comprehensive guide covering essential topics for internal medicine rotation including patient assessment, common conditions, and clinical procedures.',
                'category' => 'Study Guides',
                'file_name' => 'im-clerkship-handbook-2024.pdf',
                'file_type' => 'pdf',
                'file_size' => 5242880, // 5MB
                'target_year_levels' => ['First Year', 'Second Year'],
            ],
            [
                'title' => 'Advanced Cardiac Life Support (ACLS) Protocol',
                'description' => 'Step-by-step ACLS algorithms and emergency cardiac care protocols. Essential for all medical residents.',
                'category' => 'Clinical Guidelines',
                'file_name' => 'acls-protocol-2024.pdf',
                'file_type' => 'pdf',
                'file_size' => 2097152, // 2MB
                'target_year_levels' => null,
            ],
            [
                'title' => 'Pharmacology Quick Reference Guide',
                'description' => 'Essential drug information, dosing guidelines, and interactions for commonly prescribed medications.',
                'category' => 'Reference Materials',
                'file_name' => 'pharmacology-quick-ref.pdf',
                'file_type' => 'pdf',
                'file_size' => 3145728, // 3MB
                'target_year_levels' => ['First Year'],
            ],
            [
                'title' => 'Physical Examination Techniques - Video Series',
                'description' => 'Comprehensive video demonstrations of proper physical examination techniques for all major body systems.',
                'category' => 'Video Tutorials',
                'file_name' => 'pe-techniques.mp4',
                'file_type' => 'mp4',
                'file_size' => 20971520, // 20MB
                'target_year_levels' => ['First Year', 'Second Year'],
            ],
            [
                'title' => 'Clinical Case Studies - Cardiology',
                'description' => 'Collection of 25 cardiology cases with detailed presentations, diagnostic workups, and management plans.',
                'category' => 'Practice Cases',
                'file_name' => 'cardiology-cases.pdf',
                'file_type' => 'pdf',
                'file_size' => 4194304, // 4MB
                'target_year_levels' => ['Second Year', 'Third Year'],
            ],
            [
                'title' => 'Hospital Infection Control Protocols',
                'description' => 'Standard operating procedures for infection prevention and control in hospital settings.',
                'category' => 'Protocols & Procedures',
                'file_name' => 'infection-control-protocols.pdf',
                'file_type' => 'pdf',
                'file_size' => 1572864, // 1.5MB
                'target_year_levels' => null,
            ],
            [
                'title' => 'ECG Interpretation Lecture Notes',
                'description' => 'Comprehensive lecture slides on ECG interpretation including normal patterns, common abnormalities, and clinical correlations.',
                'category' => 'Lecture Notes',
                'file_name' => 'ecg-interpretation-notes.pptx',
                'file_type' => 'pptx',
                'file_size' => 8388608, // 8MB
                'target_year_levels' => ['First Year', 'Second Year'],
            ],
            [
                'title' => 'Emergency Medicine Procedures Checklist',
                'description' => 'Step-by-step checklists for common emergency procedures including intubation, central line placement, and chest tube insertion.',
                'category' => 'Handouts',
                'file_name' => 'em-procedures-checklist.pdf',
                'file_type' => 'pdf',
                'file_size' => 1048576, // 1MB
                'target_year_levels' => ['Second Year', 'Third Year', 'Fourth Year'],
            ],
            [
                'title' => 'Antibiotic Stewardship Guidelines 2024',
                'description' => 'Updated guidelines for appropriate antibiotic selection, dosing, and duration based on latest evidence-based practices.',
                'category' => 'Journal Articles',
                'file_name' => 'antibiotic-guidelines-2024.pdf',
                'file_type' => 'pdf',
                'file_size' => 2621440, // 2.5MB
                'target_year_levels' => null,
            ],
            [
                'title' => 'Radiology Imaging Basics',
                'description' => 'Introduction to interpreting X-rays, CT scans, and MRI images with annotated examples of common findings.',
                'category' => 'Study Guides',
                'file_name' => 'radiology-basics.pdf',
                'file_type' => 'pdf',
                'file_size' => 10485760, // 10MB
                'target_year_levels' => ['First Year'],
            ],
        ];

        foreach ($resources as $resourceData) {
            LearningResource::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'title' => $resourceData['title'],
                ],
                [
                    'uploaded_by' => $uploader->id,
                    'description' => $resourceData['description'],
                    'category' => $resourceData['category'],
                    'file_path' => 'resources/sample-'.\Str::slug($resourceData['file_name']),
                    'file_name' => $resourceData['file_name'],
                    'file_type' => $resourceData['file_type'],
                    'file_size' => $resourceData['file_size'],
                    'target_year_levels' => $resourceData['target_year_levels'],
                    'is_published' => true,
                ]
            );
        }

        $this->command->info('✅ Successfully created '.count($resources).' learning resources!');
    }
}
