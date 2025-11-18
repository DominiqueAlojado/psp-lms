<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstitutionQuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all institutions ONLY (exclude national organizations)
        $institutions = Organization::where('type', 'institution')
            ->where('is_active', true)
            ->get();

        // Double-check: filter out any national organizations that might have slipped through
        $institutions = $institutions->filter(function ($org) {
            return $org->type === 'institution';
        });

        if ($institutions->isEmpty()) {
            $this->command->warn('No institutions found. Please run OrganizationSeeder first.');

            return;
        }

        $this->command->info("Found {$institutions->count()} institution(s) to seed (excluding national organizations).");

        // Get or create a system admin user for creating questions
        $creator = User::whereHas('roles', function ($query) {
            $query->where('name', 'System Admin');
        })->first() ?? User::first();

        if (! $creator) {
            $this->command->error('No user found. Please run SystemAdminSeeder first.');

            return;
        }

        // Get all topics (global and organization-specific)
        $topics = Topic::all();
        if ($topics->isEmpty()) {
            $this->command->warn('No topics found. Please run TopicSeeder first.');

            return;
        }

        $topicLookup = $topics->pluck('id', 'name');

        $blueprints = $this->questionBlueprints();
        $totalCreated = 0;

        foreach ($institutions as $institution) {
            $this->command->info("Processing: {$institution->name}");

            // Check existing questions for this institution
            $existingCount = QuestionBank::where('organization_id', $institution->id)
                ->where('owner_type', 'institution')
                ->count();

            if ($existingCount >= 30) {
                $this->command->info("  Already has {$existingCount} questions. Skipping.");

                continue;
            }

            $needed = 30 - $existingCount;
            $created = 0;

            // Shuffle blueprints to randomize
            $randomizedBlueprints = collect($blueprints)->shuffle()->take($needed);

            foreach ($randomizedBlueprints as $blueprint) {
                // Ensure this is an institution, not a national organization
                if ($institution->type !== 'institution') {
                    $this->command->warn("  Skipping {$institution->name} - not an institution type.");

                    continue;
                }

                $topicId = $topicLookup->get($blueprint['topic']) ?? $topics->random()->id;

                // Randomly assign question type if not specified
                $questionType = $blueprint['question_type'] ?? $this->randomQuestionType();
                $choices = $this->prepareChoices($blueprint, $questionType);

                $question = QuestionBank::create([
                    'organization_id' => $institution->id,
                    'owner_type' => 'institution',
                    'topic_id' => $topicId,
                    'created_by' => $creator->id,
                    'question_type' => $questionType,
                    'question_text' => $blueprint['question'],
                    'points' => $blueprint['points'] ?? 1,
                    'explanation' => $blueprint['explanation'] ?? null,
                    'difficulty_level' => $blueprint['difficulty'] ?? $this->randomDifficulty(),
                    'is_approved' => true,
                    'approved_by' => $creator->id,
                    'approved_at' => now(),
                ]);

                // Create choices
                foreach ($choices as $order => $choiceData) {
                    $question->choices()->create([
                        'choice_text' => $choiceData['text'],
                        'is_correct' => $choiceData['is_correct'],
                        'order' => $order + 1,
                    ]);
                }

                // Initialize statistics
                $question->statistics()->create([
                    'question_id' => $question->id,
                    'scope' => 'institution',
                    'institution_id' => $institution->id,
                ]);

                $created++;
                $totalCreated++;
            }

            $this->command->info("  Created {$created} questions for {$institution->name}");
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$totalCreated} questions across all institutions");
    }

    /**
     * Randomly select question type.
     */
    private function randomQuestionType(): string
    {
        $types = ['multiple_choice', 'multiple_select', 'true_false'];
        $weights = [0.7, 0.2, 0.1]; // 70% multiple choice, 20% multiple select, 10% true/false

        $random = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($types as $index => $type) {
            $cumulative += $weights[$index] * 100;
            if ($random <= $cumulative) {
                return $type;
            }
        }

        return 'multiple_choice';
    }

    /**
     * Randomly select difficulty level.
     */
    private function randomDifficulty(): string
    {
        $difficulties = ['easy', 'medium', 'hard'];
        $weights = [0.4, 0.5, 0.1]; // 40% easy, 50% medium, 10% hard

        $random = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($difficulties as $index => $difficulty) {
            $cumulative += $weights[$index] * 100;
            if ($random <= $cumulative) {
                return $difficulty;
            }
        }

        return 'medium';
    }

    /**
     * Prepare choices based on question type.
     */
    private function prepareChoices(array $blueprint, string $questionType): array
    {
        $choices = $blueprint['choices'] ?? [];

        if ($questionType === 'true_false') {
            // Randomly assign true or false as correct
            $correctAnswer = (bool) mt_rand(0, 1);

            return [
                ['text' => 'True', 'is_correct' => $correctAnswer],
                ['text' => 'False', 'is_correct' => ! $correctAnswer],
            ];
        }

        if ($questionType === 'multiple_select') {
            // For multiple select, randomly make 1-3 choices correct
            $correctCount = min(mt_rand(1, 3), count($choices));
            $choiceIndices = range(0, count($choices) - 1);
            shuffle($choiceIndices);

            $preparedChoices = [];
            foreach ($choices as $index => $choiceText) {
                $preparedChoices[] = [
                    'text' => $choiceText,
                    'is_correct' => in_array($index, array_slice($choiceIndices, 0, $correctCount)),
                ];
            }

            return $preparedChoices;
        }

        // Multiple choice: first choice is correct
        $preparedChoices = [];
        foreach ($choices as $index => $choiceText) {
            $preparedChoices[] = [
                'text' => $choiceText,
                'is_correct' => $index === 0,
            ];
        }

        return $preparedChoices;
    }

    /**
     * Realistic medical question templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionBlueprints(): array
    {
        return [
            [
                'topic' => 'Hematology',
                'question' => 'A 32-year-old man with progressive leukocytosis is diagnosed with chronic myeloid leukemia. Which cytogenetic abnormality confirms the diagnosis?',
                'choices' => [
                    't(9;22) BCR-ABL fusion gene (Philadelphia chromosome)',
                    't(8;14) MYC translocation involving the heavy-chain enhancer',
                    't(15;17) PML-RARA fusion characteristic of APL',
                    't(12;21) ETV6-RUNX1 rearrangement seen in ALL',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'The Philadelphia chromosome t(9;22) creating BCR-ABL is pathognomonic for chronic myeloid leukemia.',
            ],
            [
                'topic' => 'Pathology',
                'question' => 'Which necrotic pattern is classically associated with Mycobacterium tuberculosis infection in lymph nodes?',
                'choices' => [
                    'Caseous necrosis with amorphous granular debris',
                    'Liquefactive necrosis secondary to lysosomal enzymes',
                    'Fat necrosis with saponification of adipocytes',
                    'Fibrinoid necrosis of vascular walls',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Granulomatous inflammation from TB produces caseous necrosis with cheese-like gross appearance.',
            ],
            [
                'topic' => 'Histology',
                'question' => 'Type I pneumocytes primarily serve which function within the alveolus?',
                'choices' => [
                    'Form the simple squamous barrier for gas exchange',
                    'Produce surfactant rich in dipalmitoylphosphatidylcholine',
                    'Secrete mucus to humidify inspired air',
                    'Differentiate into Clara cells after injury',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Type I pneumocytes are squamous cells that line most of the alveolar surface to permit gas diffusion.',
            ],
            [
                'topic' => 'Microbiology',
                'question' => 'A catheter-associated bloodstream infection grows coagulase-negative staphylococci. Which empiric therapy best covers this organism?',
                'choices' => [
                    'Vancomycin targeting methicillin-resistant staphylococci',
                    'Ampicillin-sulbactam covering beta-lactamase producers',
                    'Ceftriaxone providing broad gram-negative coverage',
                    'Azithromycin for intracellular atypical organisms',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Coagulase-negative staphylococci are usually methicillin resistant; vancomycin provides reliable empiric coverage.',
            ],
            [
                'topic' => 'Pharmacology',
                'question' => 'Which medication rapidly reverses unfractionated heparin in the setting of major bleeding?',
                'choices' => [
                    'Protamine sulfate forming an inactive complex with heparin',
                    'Vitamin K restoring hepatic clotting factor synthesis',
                    'Idarucizumab binding direct thrombin inhibitors',
                    'Andexanet alfa decoying factor Xa inhibitors',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Protamine sulfate is a positively charged peptide that neutralizes negatively charged heparin.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'Persistent ST-segment elevation with Q waves in anterior leads most likely indicates which condition?',
                'choices' => [
                    'Transmural infarction of the left anterior descending artery territory',
                    'Acute pericarditis with diffuse subepicardial injury',
                    'Left ventricular hypertrophy causing repolarization abnormalities',
                    'Electrolyte disturbance resulting in early repolarization',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Q waves with localized ST elevation reflect completed transmural infarction supplied by the LAD.',
            ],
            [
                'topic' => 'Respiratory System',
                'question' => 'A patient with severe asthma arrives with silent chest and pulsus paradoxus. What is the most appropriate immediate intervention?',
                'choices' => [
                    'Administer nebulized beta-2 agonist plus systemic corticosteroids while preparing for intubation',
                    'Obtain a chest radiograph prior to bronchodilator therapy',
                    'Begin long-acting muscarinic antagonist monotherapy',
                    'Start high-dose oral theophylline and observe response',
                ],
                'difficulty' => 'hard',
                'points' => 3,
                'explanation' => 'A near-fatal asthma exacerbation requires aggressive bronchodilation, systemic steroids, and airway preparation.',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'Which colonic polyp carries the highest risk for malignant transformation?',
                'choices' => [
                    'Villous adenoma larger than 1 cm',
                    'Hyperplastic polyp in the rectosigmoid colon',
                    'Inflammatory pseudopolyp in ulcerative colitis',
                    'Hamartomatous Peutz-Jeghers polyp',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Villous histology and size greater than 1 cm markedly increase malignancy risk.',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'Microscopic hematuria with red blood cell casts is most specific for which diagnosis?',
                'choices' => [
                    'Glomerulonephritis causing nephritic syndrome',
                    'Nephrolithiasis with ureteral obstruction',
                    'Papillary necrosis from analgesic overuse',
                    'Bladder carcinoma involving the trigone',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'RBC casts signify glomerular inflammation or damage, distinguishing nephritic processes from lower tract sources.',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'Which antithyroid therapy is preferred for a pregnant patient in her first trimester with Graves disease?',
                'choices' => [
                    'Propylthiouracil because it has lower teratogenic risk early in pregnancy',
                    'Methimazole due to once-daily dosing and better adherence',
                    'Radioactive iodine ablation to achieve definitive cure',
                    'Beta-blocker monotherapy to blunt adrenergic symptoms',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Propylthiouracil is favored in the first trimester as methimazole is associated with embryopathy.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'Loss of dopaminergic neurons in the substantia nigra pars compacta leads to which neurotransmitter imbalance?',
                'choices' => [
                    'Decreased dopamine with relative excess acetylcholine in the striatum',
                    'Increased dopamine stimulating direct basal ganglia pathways',
                    'Reduced serotonin causing impaired mood regulation',
                    'Elevated norepinephrine within the locus coeruleus',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Parkinson disease features dopamine depletion resulting in cholinergic overactivity in the basal ganglia.',
            ],
            [
                'topic' => 'Musculoskeletal System',
                'question' => 'Which laboratory finding is most characteristic of rheumatoid arthritis?',
                'choices' => [
                    'Positive anti-citrullinated peptide antibodies with elevated ESR',
                    'High-titer anti-dsDNA antibodies with low complement',
                    'Elevated creatine kinase reflecting myocyte injury',
                    'Hyperuricemia with needle-shaped negatively birefringent crystals',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Anti-CCP antibodies are specific for rheumatoid arthritis and correlate with erosive disease.',
            ],
            [
                'topic' => 'Infectious Diseases',
                'question' => 'What is the recommended prophylaxis for a newborn whose mother has untreated HIV infection during labor?',
                'choices' => [
                    'Initiate zidovudine monotherapy immediately after birth',
                    'Administer ceftriaxone to prevent bacterial sepsis',
                    'Give palivizumab to reduce RSV complications',
                    'Start oseltamivir if the mother has influenza symptoms',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Intrapartum exposure requires neonatal zidovudine for six weeks to reduce vertical transmission.',
            ],
            [
                'topic' => 'Public Health',
                'question' => 'How is test sensitivity best defined?',
                'choices' => [
                    'The proportion of true positives correctly identified by the test',
                    'The probability that a positive result truly indicates disease',
                    'The likelihood ratio comparing positive to negative results',
                    'The prevalence of disease within the screened population',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Sensitivity measures how well a test detects disease among those who truly have it.',
            ],
            [
                'topic' => 'Evidence-Based Medicine',
                'question' => 'A hazard ratio of 0.70 (95% CI 0.60–0.90) for mortality in a trial implies what?',
                'choices' => [
                    'The intervention reduces the hazard of death by 30% compared with control',
                    'There is no statistically significant difference in outcomes',
                    'The control arm had better survival throughout follow-up',
                    'Absolute risk reduction of 30 percentage points was achieved',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'A hazard ratio below 1 with CI not crossing 1 means the intervention lowers the instantaneous risk by that proportion.',
            ],
            [
                'topic' => 'Medical Ethics',
                'question' => 'Respecting a patient\'s informed refusal of chemotherapy despite high cure rates exemplifies which ethical principle?',
                'choices' => [
                    'Autonomy honoring the patient\'s values and decisions',
                    'Beneficence prioritizing the patient\'s best interest',
                    'Nonmaleficence avoiding harm through overtreatment',
                    'Justice ensuring fair allocation of resources',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Autonomy obligates clinicians to respect competent patients\' choices even when they differ from medical recommendations.',
            ],
            [
                'topic' => 'Emergency Medicine',
                'question' => 'Which intervention should be performed immediately when a patient develops a tension pneumothorax after trauma?',
                'choices' => [
                    'Needle decompression in the second intercostal space midclavicular line',
                    'Chest radiograph to confirm lung collapse before treatment',
                    'Endotracheal intubation prior to any thoracic intervention',
                    'Placement of a chest tube without prior decompression',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Tension physiology mandates emergent needle decompression before definitive tube thoracostomy.',
            ],
            [
                'topic' => 'Pediatrics',
                'question' => 'At what age should the first dose of the MMR vaccine typically be administered?',
                'choices' => [
                    'Between 12 and 15 months of age',
                    'At birth during the initial newborn visit',
                    'At 2 months along with the primary series',
                    'Delayed until the child enters elementary school',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'MMR is scheduled at 12–15 months with a booster at 4–6 years.',
            ],
            [
                'topic' => 'Obstetrics & Gynecology',
                'question' => 'A painless third-trimester bleed with a low-lying placenta on ultrasound suggests placenta previa. What is the recommended management?',
                'choices' => [
                    'Plan a scheduled cesarean delivery at 36–37 weeks if bleeding stabilizes',
                    'Attempt induction of labor with oxytocin at term',
                    'Perform immediate classical cesarean at diagnosis regardless of gestation',
                    'Offer vaginal delivery if fetal head is engaged',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Placenta previa warrants pelvic rest and planned cesarean once fetal maturity is reached.',
            ],
            [
                'topic' => 'Surgery',
                'question' => 'Using the Parkland formula, how much lactated Ringer\'s solution should be given in the first 24 hours to a 70-kg adult with 30% TBSA burns?',
                'choices' => [
                    '8.4 liters with half administered in the first 8 hours',
                    '2.1 liters delivered evenly over 24 hours',
                    '4.2 liters front-loaded over 8 hours',
                    '10.5 liters infused continuously over 24 hours',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Parkland formula: 4 mL × weight × %TBSA = 8,400 mL; deliver half in first 8 hours.',
            ],
            [
                'topic' => 'Internal Medicine',
                'question' => 'Bilateral hilar adenopathy with erythema nodosum in a young woman most strongly indicates which diagnosis?',
                'choices' => [
                    'Sarcoidosis causing noncaseating granulomas',
                    'Primary tuberculosis infection',
                    'Coccidioidomycosis acquired in the desert Southwest',
                    'Pulmonary lymphoma with mediastinal involvement',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Löfgren syndrome in sarcoidosis features hilar adenopathy, erythema nodosum, and arthralgias.',
            ],
            [
                'topic' => 'Radiology',
                'question' => 'What is the best initial imaging study for suspected acute subarachnoid hemorrhage?',
                'choices' => [
                    'Non-contrast head CT performed emergently',
                    'MRI brain with gadolinium contrast',
                    'CT angiography of the head and neck first',
                    'Lumbar puncture before any imaging',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'A non-contrast CT rapidly identifies acute blood and guides further management.',
            ],
            [
                'topic' => 'Anesthesiology',
                'question' => 'Rapid-sequence induction for emergent intubation includes which essential component?',
                'choices' => [
                    'Application of cricoid pressure with immediate paralysis and no bag-mask ventilation',
                    'Slow escalation of inhaled anesthetics before neuromuscular blockade',
                    'Routine use of spinal anesthesia to blunt reflexes',
                    'Administration of naloxone before induction medications',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Rapid-sequence induction minimizes aspiration risk using cricoid pressure and prompt neuromuscular blockade.',
            ],
            [
                'topic' => 'Family Medicine',
                'question' => 'Average-risk adults should begin colorectal cancer screening at what age?',
                'choices' => [
                    'Age 45 with colonoscopy or equivalent screening test',
                    'Age 30 if there is any family history of cancer',
                    'Age 55 provided there are no symptoms',
                    'Screening is unnecessary if annual fecal occult blood tests are normal',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Updated guidelines recommend initiating colorectal cancer screening at age 45 for average-risk individuals.',
            ],
            [
                'topic' => 'Ophthalmology',
                'question' => 'Acute angle-closure glaucoma should first be treated with which medication?',
                'choices' => [
                    'Topical timolol combined with systemic acetazolamide',
                    'Long-term prostaglandin analogs only',
                    'Topical corticosteroids to reduce inflammation',
                    'Observation until corneal edema resolves spontaneously',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Immediate pressure reduction using beta-blockers plus carbonic anhydrase inhibitors is critical.',
            ],
            [
                'topic' => 'Otolaryngology',
                'question' => 'A diabetic swimmer presents with severe otalgia and granulation tissue in the ear canal. The most likely pathogen is:',
                'choices' => [
                    'Pseudomonas aeruginosa causing malignant otitis externa',
                    'Streptococcus pyogenes producing beta-hemolysis',
                    'Candida albicans leading to otomycosis',
                    'Human herpesvirus 3 producing Ramsay Hunt syndrome',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Malignant otitis externa in diabetics is usually due to Pseudomonas and requires antipseudomonal therapy.',
            ],
            [
                'topic' => 'Urology',
                'question' => 'An elevated PSA after prostatectomy is best interpreted as:',
                'choices' => [
                    'Biochemical recurrence requiring further staging',
                    'Residual benign hyperplasia of the transition zone',
                    'Expected PSA bounce from radiation therapy',
                    'Laboratory error that can be ignored',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Any detectable PSA after prostate removal suggests recurrent disease and warrants evaluation.',
            ],
            [
                'topic' => 'Psychiatry',
                'question' => 'Which medication is first-line for long-term management of obsessive-compulsive disorder?',
                'choices' => [
                    'High-dose selective serotonin reuptake inhibitor therapy',
                    'Short-acting benzodiazepines used as monotherapy',
                    'Low-dose first-generation antipsychotics alone',
                    'Tricyclic antidepressants as initial therapy for all patients',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'SSRIs at higher doses combined with CBT are first-line for OCD.',
            ],
            [
                'topic' => 'Immunology',
                'question' => 'Recurrent swelling of the face and airway without urticaria after minor trauma is most consistent with deficiency of which protein?',
                'choices' => [
                    'C1 esterase inhibitor leading to hereditary angioedema',
                    'Factor V causing impaired coagulation cascade',
                    'Selective IgA resulting in sinopulmonary infections',
                    'Properdin involved in the alternative complement pathway',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'C1 inhibitor deficiency permits unchecked bradykinin production, producing hereditary angioedema.',
            ],
            [
                'topic' => 'Anatomy',
                'question' => 'Compression within the carpal tunnel most directly injures which structure?',
                'choices' => [
                    'Median nerve beneath the flexor retinaculum',
                    'Ulnar nerve as it traverses Guyon canal',
                    'Radial artery within the anatomical snuffbox',
                    'Flexor carpi radialis tendon lying superficial to the tunnel',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'The carpal tunnel contains the median nerve and flexor tendons, so neuropathy produces paresthesias.',
            ],
            [
                'topic' => 'Physiology',
                'question' => 'The plateau (phase 2) of the ventricular action potential is primarily due to which ionic current?',
                'choices' => [
                    'Slow influx of calcium through L-type channels',
                    'Rapid efflux of potassium through delayed rectifier channels',
                    'Funny current sodium influx during pacemaker depolarization',
                    'Chloride influx activated by GABA receptors',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Phase 2 is sustained by Ca2+ entry via L-type channels balanced by K+ efflux.',
            ],
            [
                'topic' => 'Biochemistry',
                'question' => 'Which enzyme catalyzes the rate-limiting step of glycolysis and is inhibited by ATP and citrate?',
                'choices' => [
                    'Phosphofructokinase-1',
                    'Glucokinase',
                    'Pyruvate kinase',
                    'Enolase',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'PFK-1 commits glucose to glycolysis and responds to the cell\'s energy status.',
            ],
            [
                'topic' => 'Oncology',
                'question' => 'Alpha-fetoprotein is most useful for surveillance of which malignancy?',
                'choices' => [
                    'Hepatocellular carcinoma in a cirrhotic patient',
                    'Medullary thyroid carcinoma associated with MEN2',
                    'Pancreatic adenocarcinoma of the head of pancreas',
                    'Colorectal carcinoma after right hemicolectomy',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Serum AFP levels correlate with tumor burden in hepatocellular carcinoma and yolk sac tumors.',
            ],
            [
                'topic' => 'Dermatology',
                'question' => 'An asymmetric pigmented lesion with irregular borders and color variegation should prompt concern for which diagnosis?',
                'choices' => [
                    'Malignant melanoma meeting ABCDE criteria',
                    'Seborrheic keratosis demonstrating the Leser-Trélat sign',
                    'Benign junctional nevus in a child',
                    'Cherry angioma commonly seen with aging',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'ABCDE warning signs point toward melanoma, necessitating excisional biopsy.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'Which biomarker correlates with ventricular wall stress and is elevated in decompensated heart failure?',
                'choices' => [
                    'B-type natriuretic peptide (BNP)',
                    'Cardiac troponin I',
                    'Creatine kinase-MB fraction',
                    'D-dimer fragments',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'BNP is released from ventricles in response to stretch and guides heart failure therapy.',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'Which serologic assay is most specific for celiac disease?',
                'choices' => [
                    'IgA anti–tissue transglutaminase antibodies',
                    'IgM anti-mitochondrial antibodies',
                    'Anti-centromere antibodies',
                    'Anti-topoisomerase I antibodies',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'IgA anti-tTG antibodies are the preferred screening and monitoring test for celiac disease.',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'Angiotensin-converting enzyme inhibitors provide renal protection in diabetes primarily by:',
                'choices' => [
                    'Dilating the efferent arteriole to lower intraglomerular pressure',
                    'Constraining the afferent arteriole to reduce filtration',
                    'Stimulating aldosterone to increase distal sodium reabsorption',
                    'Inducing mesangial cell proliferation to support the basement membrane',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'ACE inhibitors preferentially dilate efferent arterioles, reducing glomerular hypertension and proteinuria.',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'Secondary hyperparathyroidism from chronic kidney disease typically demonstrates which laboratory triad?',
                'choices' => [
                    'Hypocalcemia, hyperphosphatemia, elevated PTH',
                    'Hypercalcemia, hypophosphatemia, suppressed PTH',
                    'Normocalcemia, hyperphosphatemia, low PTH',
                    'Hypercalcemia, hyperphosphatemia, low PTH',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Phosphate retention and low vitamin D cause hypocalcemia, stimulating secondary PTH elevation.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'What is the preferred initial imaging modality when evaluating an acute ischemic stroke for thrombolysis eligibility?',
                'choices' => [
                    'Non-contrast CT of the head',
                    'MRI brain with gadolinium contrast',
                    'Carotid duplex ultrasonography',
                    'Positron emission tomography scan',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'A non-contrast CT rapidly excludes intracranial hemorrhage before administering thrombolytics.',
            ],
            [
                'topic' => 'Musculoskeletal System',
                'question' => 'Which therapy provides rapid relief for an acute gout flare in a patient with preserved renal function?',
                'choices' => [
                    'High-dose nonsteroidal anti-inflammatory drugs such as indomethacin',
                    'Initiate allopurinol immediately at a high dose',
                    'Start probenecid during the painful episode',
                    'Use colchicine only as long-term prophylaxis',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'NSAIDs are first-line for acute gout, whereas urate-lowering drugs are not started during flares.',
            ],
            [
                'topic' => 'Dermatology',
                'question' => 'Stevens-Johnson syndrome is most commonly precipitated by which exposure?',
                'choices' => [
                    'Sulfonamide antibiotics leading to severe mucocutaneous reactions',
                    'Chronic ultraviolet light in outdoor workers',
                    'Human papillomavirus infection of keratinocytes',
                    'Localized Staphylococcus aureus colonization',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'High-risk medications such as sulfonamides, allopurinol, and some anticonvulsants trigger SJS/TEN.',
            ],
            [
                'topic' => 'Oncology',
                'question' => 'BRCA1 and BRCA2 gene products normally participate in which cellular process?',
                'choices' => [
                    'Homologous recombination repair of double-strand DNA breaks',
                    'Activation of epidermal growth factor receptors',
                    'Promotion of angiogenesis through VEGF production',
                    'Regulation of telomerase extension',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Loss of BRCA-mediated DNA repair increases genomic instability and cancer risk.',
            ],
            [
                'topic' => 'Clinical Skills',
                'question' => 'Detection of pulsus paradoxus greater than 12 mm Hg most strongly suggests which diagnosis in the appropriate clinical context?',
                'choices' => [
                    'Cardiac tamponade impairing diastolic filling',
                    'Aortic regurgitation with wide pulse pressure',
                    'Hypertrophic obstructive cardiomyopathy',
                    'Peripheral arterial disease with claudication',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Marked pulsus paradoxus is classic for tamponade, severe asthma, or constrictive pericarditis.',
            ],
        ];
    }
}
