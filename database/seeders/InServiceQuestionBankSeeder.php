<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class InServiceQuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a system admin user for creating questions
        $creator = User::whereHas('roles', function ($query) {
            $query->where('name', 'System Admin');
        })->first() ?? User::first();

        if (! $creator) {
            $this->command->error('No user found. Please run SystemAdminSeeder first.');

            return;
        }

        // Get all global topics
        $topics = Topic::where('is_global', true)->get();
        if ($topics->isEmpty()) {
            $this->command->error('No topics found. Please run TopicSeeder first.');

            return;
        }

        $topicLookup = $topics->pluck('id', 'name');

        // Check if questions already exist
        $existingCount = QuestionBank::where('owner_type', 'national')->count();
        if ($existingCount >= 100) {
            $this->command->info("Already have {$existingCount} national questions. Skipping seeder.");

            return;
        }

        $blueprints = $this->questionBlueprints();
        $created = 0;

        foreach ($blueprints as $blueprint) {
            // Skip if we already have 100 questions
            if ($created >= 100) {
                break;
            }

            $topicId = $topicLookup->get($blueprint['topic']) ?? $topics->random()->id;

            $question = QuestionBank::create([
                'organization_id' => null, // National questions don't belong to a specific organization
                'owner_type' => 'national',
                'topic_id' => $topicId,
                'created_by' => $creator->id,
                'question_type' => $blueprint['question_type'] ?? 'multiple_choice',
                'question_text' => $blueprint['question'],
                'points' => $blueprint['points'] ?? 1,
                'explanation' => $blueprint['explanation'],
                'difficulty_level' => $blueprint['difficulty'] ?? 'medium',
                'is_approved' => true,
                'approved_by' => $creator->id,
                'approved_at' => now(),
            ]);

            // Create choices
            foreach ($blueprint['choices'] as $order => $choice) {
                $question->choices()->create([
                    'choice_text' => is_array($choice) ? $choice['text'] : $choice,
                    'is_correct' => is_array($choice) ? ($choice['is_correct'] ?? false) : ($order === 0),
                    'order' => $order + 1,
                ]);
            }

            // Initialize statistics
            $question->statistics()->create([
                'question_id' => $question->id,
                'scope' => 'national',
                'institution_id' => null,
            ]);

            $created++;
        }

        $this->command->info("Created {$created} national in-service exam questions in the question bank.");
    }

    /**
     * Realistic multiple-choice question templates for in-service exams.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionBlueprints(): array
    {
        return [
            // Hematology (1-10)
            [
                'topic' => 'Hematology',
                'question' => 'A 45-year-old patient presents with fatigue and splenomegaly. Peripheral blood shows leukocytosis with all stages of granulocyte maturation. Which cytogenetic abnormality is most characteristic of this condition?',
                'choices' => [
                    't(9;22) Philadelphia chromosome (BCR-ABL fusion)',
                    't(8;14) MYC translocation',
                    't(15;17) PML-RARA fusion',
                    't(12;21) ETV6-RUNX1 rearrangement',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Chronic myeloid leukemia (CML) is characterized by the Philadelphia chromosome t(9;22), which creates the BCR-ABL fusion gene. This leads to uncontrolled proliferation of granulocytes.',
            ],
            [
                'topic' => 'Hematology',
                'question' => 'Which laboratory finding is most specific for iron deficiency anemia?',
                'choices' => [
                    'Low serum ferritin with elevated total iron-binding capacity',
                    'Elevated serum iron with low transferrin saturation',
                    'High serum ferritin with normal TIBC',
                    'Normal iron studies with microcytic anemia',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Iron deficiency anemia is characterized by low ferritin (reflecting depleted iron stores) and elevated TIBC (increased transferrin production).',
            ],
            [
                'topic' => 'Hematology',
                'question' => 'A patient with hemophilia A would have a deficiency in which clotting factor?',
                'choices' => [
                    'Factor VIII',
                    'Factor IX',
                    'Factor VII',
                    'Factor X',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Hemophilia A is an X-linked recessive disorder caused by deficiency of factor VIII. Hemophilia B (Christmas disease) involves factor IX deficiency.',
            ],
            [
                'topic' => 'Hematology',
                'question' => 'Disseminated intravascular coagulation (DIC) is best characterized by which laboratory pattern?',
                'choices' => [
                    'Prolonged PT/PTT, low fibrinogen, elevated D-dimer, thrombocytopenia',
                    'Shortened PT/PTT, high fibrinogen, normal D-dimer',
                    'Normal coagulation studies with isolated thrombocytosis',
                    'Elevated factor VIII with low protein C',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'DIC involves consumption of clotting factors and platelets, leading to prolonged coagulation times, low fibrinogen, elevated D-dimer (indicating fibrinolysis), and thrombocytopenia.',
            ],
            [
                'topic' => 'Hematology',
                'question' => 'Which condition is associated with "Auer rods" in the cytoplasm of blasts?',
                'choices' => [
                    'Acute myeloid leukemia (AML)',
                    'Acute lymphoblastic leukemia (ALL)',
                    'Chronic lymphocytic leukemia (CLL)',
                    'Multiple myeloma',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Auer rods are needle-shaped inclusions found in the cytoplasm of myeloblasts, characteristic of AML. They are never seen in ALL.',
            ],

            // Pathology (11-20)
            [
                'topic' => 'Pathology',
                'question' => 'Caseous necrosis is most commonly associated with which infectious agent?',
                'choices' => [
                    'Mycobacterium tuberculosis',
                    'Staphylococcus aureus',
                    'Escherichia coli',
                    'Candida albicans',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Caseous necrosis, with its characteristic "cheese-like" appearance, is a hallmark of tuberculosis and other granulomatous diseases.',
            ],
            [
                'topic' => 'Pathology',
                'question' => 'Which type of necrosis is characterized by preservation of tissue architecture with "ghost cells"?',
                'choices' => [
                    'Coagulative necrosis',
                    'Liquefactive necrosis',
                    'Fat necrosis',
                    'Fibrinoid necrosis',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Coagulative necrosis preserves tissue architecture, creating "ghost cells" where cell outlines remain visible. This is typical of ischemic injury in solid organs like heart and kidney.',
            ],
            [
                'topic' => 'Pathology',
                'question' => 'A patient with chronic hepatitis B is at highest risk for developing which type of liver cancer?',
                'choices' => [
                    'Hepatocellular carcinoma',
                    'Cholangiocarcinoma',
                    'Hepatoblastoma',
                    'Metastatic adenocarcinoma',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Chronic hepatitis B and C infections are major risk factors for hepatocellular carcinoma, the most common primary liver malignancy.',
            ],
            [
                'topic' => 'Pathology',
                'question' => 'Which histologic feature is most characteristic of Barrett esophagus?',
                'choices' => [
                    'Intestinal metaplasia with goblet cells replacing squamous epithelium',
                    'Squamous cell hyperplasia with hyperkeratosis',
                    'Adenocarcinoma in situ',
                    'Eosinophilic infiltration of the mucosa',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Barrett esophagus is defined by the replacement of normal squamous epithelium with columnar epithelium containing goblet cells (intestinal metaplasia), which is a premalignant condition.',
            ],
            [
                'topic' => 'Pathology',
                'question' => 'The presence of Reed-Sternberg cells is diagnostic for which condition?',
                'choices' => [
                    'Hodgkin lymphoma',
                    'Non-Hodgkin lymphoma',
                    'Multiple myeloma',
                    'Acute leukemia',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Reed-Sternberg cells, with their characteristic "owl-eye" appearance (binucleated or multinucleated), are pathognomonic for Hodgkin lymphoma.',
            ],

            // Cardiovascular System (21-30)
            [
                'topic' => 'Cardiovascular System',
                'question' => 'Which medication is first-line for acute ST-elevation myocardial infarction?',
                'choices' => [
                    'Aspirin and dual antiplatelet therapy with primary PCI or thrombolytics',
                    'Beta-blockers alone',
                    'Calcium channel blockers',
                    'Digoxin',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'STEMI requires immediate reperfusion therapy (primary PCI or thrombolytics) along with dual antiplatelet therapy (aspirin + P2Y12 inhibitor) to restore coronary blood flow.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'A patient with heart failure has elevated B-type natriuretic peptide (BNP). What is the primary source of BNP?',
                'choices' => [
                    'Ventricular cardiomyocytes in response to wall stress',
                    'Atrial myocytes',
                    'Hepatic synthesis',
                    'Renal tubular cells',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'BNP is primarily secreted by ventricular cardiomyocytes in response to increased wall stress and volume overload, making it a useful biomarker for heart failure.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'Which ECG finding is most characteristic of atrial fibrillation?',
                'choices' => [
                    'Irregularly irregular rhythm with absent P waves',
                    'Regular rhythm with sawtooth P waves',
                    'Prolonged PR interval with dropped beats',
                    'Wide QRS complexes with variable morphology',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Atrial fibrillation is characterized by an irregularly irregular ventricular response and the absence of distinct P waves, replaced by fibrillatory waves.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'A patient with chest pain and ST elevation in leads II, III, and aVF most likely has occlusion of which coronary artery?',
                'choices' => [
                    'Right coronary artery (RCA)',
                    'Left anterior descending (LAD)',
                    'Left circumflex (LCx)',
                    'Left main coronary artery',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'ST elevation in the inferior leads (II, III, aVF) indicates inferior wall myocardial infarction, typically caused by occlusion of the right coronary artery.',
            ],
            [
                'topic' => 'Cardiovascular System',
                'question' => 'Which condition is characterized by a wide pulse pressure and "water-hammer" pulse?',
                'choices' => [
                    'Aortic regurgitation',
                    'Aortic stenosis',
                    'Mitral stenosis',
                    'Tricuspid regurgitation',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Aortic regurgitation causes a wide pulse pressure (high systolic, low diastolic) and produces a "water-hammer" or collapsing pulse due to rapid runoff of blood back into the left ventricle.',
            ],

            // Respiratory System (31-40)
            [
                'topic' => 'Respiratory System',
                'question' => 'Which arterial blood gas pattern is most characteristic of acute severe asthma?',
                'choices' => [
                    'Respiratory alkalosis with low PaCO₂ and normal to low PaO₂',
                    'Respiratory acidosis with elevated PaCO₂',
                    'Metabolic acidosis with normal PaCO₂',
                    'Normal blood gas values',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Acute asthma causes hyperventilation (tachypnea), leading to respiratory alkalosis with low PaCO₂. Severe cases may progress to respiratory acidosis, indicating impending respiratory failure.',
            ],
            [
                'topic' => 'Respiratory System',
                'question' => 'A patient with sudden onset dyspnea, pleuritic chest pain, and hemoptysis most likely has which condition?',
                'choices' => [
                    'Pulmonary embolism',
                    'Pneumonia',
                    'Chronic obstructive pulmonary disease',
                    'Asthma',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'The classic triad of dyspnea, pleuritic chest pain, and hemoptysis is highly suggestive of pulmonary embolism, though not all patients present with all three symptoms.',
            ],
            [
                'topic' => 'Respiratory System',
                'question' => 'Which type of pneumocyte produces surfactant?',
                'choices' => [
                    'Type II pneumocytes',
                    'Type I pneumocytes',
                    'Clara cells',
                    'Alveolar macrophages',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Type II pneumocytes produce surfactant (rich in dipalmitoylphosphatidylcholine), which reduces surface tension and prevents alveolar collapse. Type I pneumocytes are involved in gas exchange.',
            ],
            [
                'topic' => 'Respiratory System',
                'question' => 'A patient with bilateral hilar lymphadenopathy, erythema nodosum, and arthralgias most likely has which condition?',
                'choices' => [
                    'Sarcoidosis (Löfgren syndrome)',
                    'Tuberculosis',
                    'Lung cancer',
                    'Pneumonia',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Löfgren syndrome is an acute presentation of sarcoidosis characterized by bilateral hilar lymphadenopathy, erythema nodosum, and polyarthralgia, typically in young adults.',
            ],
            [
                'topic' => 'Respiratory System',
                'question' => 'Which condition is characterized by destruction of alveolar walls leading to enlarged air spaces?',
                'choices' => [
                    'Emphysema',
                    'Asthma',
                    'Pulmonary fibrosis',
                    'Pneumonia',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Emphysema involves destruction of alveolar walls and loss of elastic recoil, leading to enlarged air spaces. This is often caused by alpha-1 antitrypsin deficiency or smoking.',
            ],

            // Gastrointestinal System (41-50)
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'Which serologic test is most specific for celiac disease?',
                'choices' => [
                    'IgA anti-tissue transglutaminase antibodies',
                    'Anti-nuclear antibodies',
                    'Anti-mitochondrial antibodies',
                    'Anti-smooth muscle antibodies',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'IgA anti-tissue transglutaminase (anti-tTG) antibodies are highly specific and sensitive for celiac disease and are the preferred screening test.',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'A patient with right upper quadrant pain, fever, and positive Murphy sign most likely has which condition?',
                'choices' => [
                    'Acute cholecystitis',
                    'Hepatitis',
                    'Pancreatitis',
                    'Appendicitis',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Acute cholecystitis presents with right upper quadrant pain, fever, and a positive Murphy sign (pain on inspiration during palpation of the right upper quadrant).',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'Which type of colonic polyp has the highest risk of malignant transformation?',
                'choices' => [
                    'Villous adenoma greater than 1 cm',
                    'Hyperplastic polyp',
                    'Inflammatory pseudopolyp',
                    'Hamartomatous polyp',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Villous adenomas, especially those larger than 1 cm, have the highest malignant potential. Hyperplastic polyps are generally benign.',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'A patient with chronic alcohol use presents with jaundice, ascites, and spider angiomata. Which condition is most likely?',
                'choices' => [
                    'Alcoholic cirrhosis',
                    'Acute hepatitis',
                    'Pancreatic cancer',
                    'Cholelithiasis',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Chronic alcohol abuse can lead to cirrhosis, characterized by jaundice, ascites, portal hypertension, and stigmata of chronic liver disease such as spider angiomata.',
            ],
            [
                'topic' => 'Gastrointestinal System',
                'question' => 'Which condition is associated with "pseudomembranes" in the colon?',
                'choices' => [
                    'Clostridioides difficile infection',
                    'Ulcerative colitis',
                    'Crohn disease',
                    'Ischemic colitis',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'C. difficile infection produces pseudomembranous colitis, characterized by yellow-white plaques (pseudomembranes) composed of fibrin, mucin, and inflammatory cells.',
            ],

            // Renal System (51-60)
            [
                'topic' => 'Renal System',
                'question' => 'Red blood cell casts in the urine are most specific for which condition?',
                'choices' => [
                    'Glomerulonephritis',
                    'Urinary tract infection',
                    'Nephrolithiasis',
                    'Bladder cancer',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'RBC casts indicate glomerular bleeding and are characteristic of glomerulonephritis or other glomerular diseases, distinguishing them from lower urinary tract sources.',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'ACE inhibitors provide renal protection in diabetic nephropathy primarily by:',
                'choices' => [
                    'Dilating efferent arterioles to reduce intraglomerular pressure',
                    'Constricting afferent arterioles',
                    'Increasing glomerular filtration rate',
                    'Reducing protein reabsorption',
                ],
                'difficulty' => 'hard',
                'points' => 3,
                'explanation' => 'ACE inhibitors preferentially dilate efferent arterioles, reducing intraglomerular pressure and proteinuria, which slows progression of diabetic nephropathy.',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'Which condition is characterized by "muddy brown" granular casts in the urine?',
                'choices' => [
                    'Acute tubular necrosis',
                    'Glomerulonephritis',
                    'Nephrotic syndrome',
                    'Pyelonephritis',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Acute tubular necrosis (ATN) produces muddy brown granular casts, which are composed of sloughed tubular epithelial cells and debris.',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'A patient with chronic kidney disease stage 4 has which estimated GFR range?',
                'choices' => [
                    '15-29 mL/min/1.73 m²',
                    '30-59 mL/min/1.73 m²',
                    '60-89 mL/min/1.73 m²',
                    '90+ mL/min/1.73 m²',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'CKD stage 4 is defined by eGFR 15-29 mL/min/1.73 m², representing severe reduction in kidney function. Stage 5 is <15 (kidney failure).',
            ],
            [
                'topic' => 'Renal System',
                'question' => 'Which electrolyte abnormality is most commonly associated with chronic kidney disease?',
                'choices' => [
                    'Hyperphosphatemia and hypocalcemia',
                    'Hypophosphatemia and hypercalcemia',
                    'Hyponatremia and hypokalemia',
                    'Hypernatremia and hyperkalemia',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'CKD leads to phosphate retention (hyperphosphatemia) and impaired vitamin D activation, causing hypocalcemia and secondary hyperparathyroidism.',
            ],

            // Endocrine System (61-70)
            [
                'topic' => 'Endocrine System',
                'question' => 'Which medication is preferred for treating hyperthyroidism in the first trimester of pregnancy?',
                'choices' => [
                    'Propylthiouracil (PTU)',
                    'Methimazole',
                    'Radioactive iodine',
                    'Beta-blockers alone',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'PTU is preferred in the first trimester due to lower risk of teratogenicity. Methimazole is associated with embryopathy. Radioactive iodine is contraindicated in pregnancy.',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'A patient with polyuria, polydipsia, and polyphagia with random glucose >200 mg/dL most likely has which condition?',
                'choices' => [
                    'Diabetes mellitus',
                    'Diabetes insipidus',
                    'Hyperthyroidism',
                    'Cushing syndrome',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'The classic triad of polyuria, polydipsia, and polyphagia with hyperglycemia is characteristic of diabetes mellitus. Diabetes insipidus causes polyuria without hyperglycemia.',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'Which laboratory finding is most characteristic of primary hyperparathyroidism?',
                'choices' => [
                    'Elevated PTH with hypercalcemia',
                    'Elevated PTH with hypocalcemia',
                    'Low PTH with hypercalcemia',
                    'Low PTH with hypocalcemia',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Primary hyperparathyroidism is characterized by elevated PTH with hypercalcemia. Secondary hyperparathyroidism shows elevated PTH with hypocalcemia (e.g., in CKD).',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'A patient with moon facies, buffalo hump, and central obesity most likely has which condition?',
                'choices' => [
                    'Cushing syndrome',
                    'Addison disease',
                    'Hyperthyroidism',
                    'Acromegaly',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Cushing syndrome (excess cortisol) causes characteristic physical findings including moon facies, buffalo hump, central obesity, and purple striae.',
            ],
            [
                'topic' => 'Endocrine System',
                'question' => 'Which condition is associated with low serum cortisol and elevated ACTH?',
                'choices' => [
                    'Primary adrenal insufficiency (Addison disease)',
                    'Cushing syndrome',
                    'Secondary adrenal insufficiency',
                    'Pheochromocytoma',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Primary adrenal insufficiency (Addison disease) results from adrenal gland failure, leading to low cortisol and high ACTH due to lack of negative feedback.',
            ],

            // Nervous System (71-80)
            [
                'topic' => 'Nervous System',
                'question' => 'Which imaging study is first-line for evaluating acute ischemic stroke?',
                'choices' => [
                    'Non-contrast head CT',
                    'MRI with gadolinium',
                    'CT angiography',
                    'Lumbar puncture',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Non-contrast head CT is performed emergently to exclude hemorrhage before administering thrombolytics. It is fast and widely available.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'Loss of dopaminergic neurons in the substantia nigra pars compacta causes which condition?',
                'choices' => [
                    'Parkinson disease',
                    'Alzheimer disease',
                    'Huntington disease',
                    'Amyotrophic lateral sclerosis',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Parkinson disease results from degeneration of dopaminergic neurons in the substantia nigra, leading to bradykinesia, rigidity, tremor, and postural instability.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'Which condition is characterized by demyelination with "plaques" visible on MRI?',
                'choices' => [
                    'Multiple sclerosis',
                    'Alzheimer disease',
                    'Parkinson disease',
                    'Epilepsy',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Multiple sclerosis is an autoimmune demyelinating disease of the central nervous system, characterized by plaques (areas of demyelination) visible on MRI.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'A patient with unilateral facial droop, arm weakness, and slurred speech most likely has which condition?',
                'choices' => [
                    'Acute ischemic stroke',
                    'Bell palsy',
                    'Migraine',
                    'Seizure',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'The sudden onset of unilateral facial droop, arm weakness, and speech difficulty is classic for acute ischemic stroke and requires immediate evaluation.',
            ],
            [
                'topic' => 'Nervous System',
                'question' => 'Which neurotransmitter is primarily affected in myasthenia gravis?',
                'choices' => [
                    'Acetylcholine at the neuromuscular junction',
                    'Dopamine in the basal ganglia',
                    'Serotonin in the brainstem',
                    'GABA in the cortex',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Myasthenia gravis is an autoimmune disorder targeting acetylcholine receptors at the neuromuscular junction, causing muscle weakness and fatigue.',
            ],

            // Microbiology (81-90)
            [
                'topic' => 'Microbiology',
                'question' => 'Which antibiotic is first-line for methicillin-resistant Staphylococcus aureus (MRSA)?',
                'choices' => [
                    'Vancomycin',
                    'Penicillin',
                    'Ceftriaxone',
                    'Azithromycin',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Vancomycin is the first-line treatment for MRSA infections. MRSA is resistant to beta-lactam antibiotics like penicillin and ceftriaxone.',
            ],
            [
                'topic' => 'Microbiology',
                'question' => 'A patient with catheter-associated urinary tract infection grows coagulase-negative staphylococci. Which treatment is most appropriate?',
                'choices' => [
                    'Vancomycin (empiric coverage for likely methicillin resistance)',
                    'Ampicillin-sulbactam',
                    'Ceftriaxone',
                    'Azithromycin',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Coagulase-negative staphylococci (especially S. epidermidis) are commonly methicillin-resistant in healthcare settings, requiring vancomycin for reliable coverage.',
            ],
            [
                'topic' => 'Microbiology',
                'question' => 'Which organism is most commonly associated with community-acquired pneumonia in adults?',
                'choices' => [
                    'Streptococcus pneumoniae',
                    'Staphylococcus aureus',
                    'Escherichia coli',
                    'Pseudomonas aeruginosa',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Streptococcus pneumoniae (pneumococcus) is the most common cause of community-acquired pneumonia in adults.',
            ],
            [
                'topic' => 'Microbiology',
                'question' => 'A diabetic patient with severe otalgia and granulation tissue in the ear canal most likely has infection with which organism?',
                'choices' => [
                    'Pseudomonas aeruginosa (malignant otitis externa)',
                    'Streptococcus pyogenes',
                    'Candida albicans',
                    'Herpes simplex virus',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Malignant otitis externa in diabetics is typically caused by Pseudomonas aeruginosa and requires aggressive antipseudomonal therapy.',
            ],
            [
                'topic' => 'Microbiology',
                'question' => 'Which staining method is used to identify acid-fast bacteria like Mycobacterium tuberculosis?',
                'choices' => [
                    'Ziehl-Neelsen or Kinyoun stain',
                    'Gram stain',
                    'Wright stain',
                    'Periodic acid-Schiff (PAS) stain',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Acid-fast staining (Ziehl-Neelsen or Kinyoun) is used to identify mycobacteria, which retain the carbol fuchsin dye after acid-alcohol decolorization.',
            ],

            // Pharmacology (91-100)
            [
                'topic' => 'Pharmacology',
                'question' => 'Which medication is the antidote for heparin overdose?',
                'choices' => [
                    'Protamine sulfate',
                    'Vitamin K',
                    'Idarucizumab',
                    'Naloxone',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Protamine sulfate, a positively charged peptide, neutralizes the negatively charged heparin molecules, reversing its anticoagulant effects.',
            ],
            [
                'topic' => 'Pharmacology',
                'question' => 'Which medication is first-line for acute gout flare?',
                'choices' => [
                    'Nonsteroidal anti-inflammatory drugs (NSAIDs) such as indomethacin',
                    'Allopurinol (started during the flare)',
                    'Probenecid',
                    'Colchicine (as long-term prophylaxis only)',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'NSAIDs are first-line for acute gout flares. Urate-lowering drugs like allopurinol should not be started during an active flare.',
            ],
            [
                'topic' => 'Pharmacology',
                'question' => 'Which medication is the antidote for malignant hyperthermia?',
                'choices' => [
                    'Dantrolene sodium',
                    'Sugammadex',
                    'Physostigmine',
                    'Flumazenil',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Dantrolene sodium blocks ryanodine receptors, preventing calcium release from the sarcoplasmic reticulum and treating malignant hyperthermia triggered by volatile anesthetics.',
            ],
            [
                'topic' => 'Pharmacology',
                'question' => 'Which class of medications is first-line for obsessive-compulsive disorder?',
                'choices' => [
                    'Selective serotonin reuptake inhibitors (SSRIs) at high doses',
                    'Benzodiazepines',
                    'First-generation antipsychotics',
                    'Tricyclic antidepressants',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'High-dose SSRIs combined with cognitive-behavioral therapy are first-line for OCD. Higher doses than used for depression are typically required.',
            ],
            [
                'topic' => 'Pharmacology',
                'question' => 'Which medication rapidly reverses the effects of warfarin?',
                'choices' => [
                    'Vitamin K and fresh frozen plasma',
                    'Protamine sulfate',
                    'Idarucizumab',
                    'Andexanet alfa',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Warfarin reversal requires vitamin K (for long-term reversal) and fresh frozen plasma or prothrombin complex concentrate (for immediate reversal). Protamine is for heparin.',
            ],

            // Additional questions to reach 100 (51-100)
            [
                'topic' => 'Internal Medicine',
                'question' => 'A patient with diabetic ketoacidosis should receive which initial treatment?',
                'choices' => [
                    'Aggressive isotonic fluid resuscitation followed by insulin infusion',
                    'Immediate bicarbonate administration',
                    'High-dose subcutaneous insulin',
                    'Fluid restriction',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'DKA management begins with aggressive volume repletion using normal saline, followed by continuous insulin infusion. Bicarbonate is rarely needed.',
            ],
            [
                'topic' => 'Internal Medicine',
                'question' => 'Which condition is characterized by bilateral hilar lymphadenopathy and noncaseating granulomas?',
                'choices' => [
                    'Sarcoidosis',
                    'Tuberculosis',
                    'Lymphoma',
                    'Fungal infection',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Sarcoidosis is characterized by noncaseating granulomas, often involving bilateral hilar lymphadenopathy. TB produces caseating granulomas.',
            ],
            [
                'topic' => 'Emergency Medicine',
                'question' => 'Which intervention should be performed immediately for tension pneumothorax?',
                'choices' => [
                    'Needle decompression in the second intercostal space midclavicular line',
                    'Chest radiograph first',
                    'Endotracheal intubation',
                    'Placement of chest tube without decompression',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Tension pneumothorax is a life-threatening emergency requiring immediate needle decompression before definitive chest tube placement.',
            ],
            [
                'topic' => 'Emergency Medicine',
                'question' => 'Massive transfusion protocol for hemorrhagic shock uses which ratio of plasma:platelets:RBCs?',
                'choices' => [
                    '1:1:1 to approximate whole blood',
                    '1:2:4',
                    '1:4:1',
                    '0:1:1',
                ],
                'difficulty' => 'hard',
                'points' => 3,
                'explanation' => 'Balanced resuscitation with a 1:1:1 ratio (plasma:platelets:RBCs) improves survival in massive hemorrhage by maintaining coagulation function.',
            ],
            [
                'topic' => 'Pediatrics',
                'question' => 'At what age should the first MMR vaccine be administered?',
                'choices' => [
                    '12-15 months',
                    'At birth',
                    '2 months',
                    'School entry',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'MMR vaccine is given at 12-15 months with a booster at 4-6 years. Maternal antibodies interfere with earlier administration.',
            ],
            [
                'topic' => 'Pediatrics',
                'question' => 'Which treatment reduces coronary aneurysm risk in Kawasaki disease?',
                'choices' => [
                    'High-dose IV immunoglobulin plus aspirin',
                    'Corticosteroids alone',
                    'Antibiotics',
                    'Observation',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Timely administration of IVIG and aspirin within 10 days of symptom onset significantly reduces the risk of coronary artery aneurysms.',
            ],
            [
                'topic' => 'Obstetrics & Gynecology',
                'question' => 'A painless third-trimester bleed with low-lying placenta suggests placenta previa. What is the management?',
                'choices' => [
                    'Plan scheduled cesarean delivery at 36-37 weeks if stable',
                    'Induce labor with oxytocin',
                    'Immediate cesarean regardless of gestation',
                    'Vaginal delivery if head engaged',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Placenta previa requires pelvic rest and planned cesarean delivery once fetal lung maturity is achieved, typically at 36-37 weeks.',
            ],
            [
                'topic' => 'Obstetrics & Gynecology',
                'question' => 'A pregnant patient with seizures and severe hypertension has eclampsia. What is the immediate priority?',
                'choices' => [
                    'Administer IV magnesium sulfate and prepare for delivery',
                    'Diazepam infusion',
                    'Delay until fetal lung maturity',
                    'Tocolytics',
                ],
                'difficulty' => 'hard',
                'points' => 3,
                'explanation' => 'Eclampsia requires immediate magnesium sulfate to prevent recurrent seizures and expedited delivery. This is a medical emergency.',
            ],
            [
                'topic' => 'Surgery',
                'question' => 'Using the Parkland formula, how much fluid should a 70-kg patient with 30% TBSA burns receive in 24 hours?',
                'choices' => [
                    '8.4 liters (half in first 8 hours)',
                    '2.1 liters evenly',
                    '4.2 liters front-loaded',
                    '10.5 liters continuously',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Parkland formula: 4 mL × weight (kg) × %TBSA = 4 × 70 × 30 = 8,400 mL. Half is given in the first 8 hours, remainder over next 16 hours.',
            ],
            [
                'topic' => 'Surgery',
                'question' => 'Which imaging is first-line for suspected acute cholecystitis?',
                'choices' => [
                    'Right upper quadrant ultrasound',
                    'Plain abdominal X-ray',
                    'CT abdomen without contrast',
                    'Barium upper GI series',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Ultrasound is the preferred initial imaging for cholecystitis, showing gallstones, wall thickening, pericholecystic fluid, and sonographic Murphy sign.',
            ],
            [
                'topic' => 'Radiology',
                'question' => 'What is the best initial imaging for suspected acute subarachnoid hemorrhage?',
                'choices' => [
                    'Non-contrast head CT',
                    'MRI with gadolinium',
                    'CT angiography first',
                    'Lumbar puncture before imaging',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Non-contrast head CT rapidly identifies acute subarachnoid blood and is the first-line study for suspected SAH.',
            ],
            [
                'topic' => 'Radiology',
                'question' => 'A stable patient with high suspicion for pulmonary embolism should undergo which test?',
                'choices' => [
                    'CT pulmonary angiography',
                    'V/Q scan in all cases',
                    'Lower extremity Doppler only',
                    'Chest X-ray sufficient',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'CTPA is first-line for PE in stable patients without contraindications. V/Q scan is used when contrast is contraindicated.',
            ],
            [
                'topic' => 'Anesthesiology',
                'question' => 'Rapid-sequence induction for emergent intubation includes which essential component?',
                'choices' => [
                    'Cricoid pressure with immediate paralysis and no bag-mask ventilation',
                    'Slow escalation of inhaled anesthetics',
                    'Spinal anesthesia',
                    'Naloxone before induction',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Rapid-sequence induction minimizes aspiration risk using cricoid pressure (Sellick maneuver) and prompt neuromuscular blockade without bag-mask ventilation.',
            ],
            [
                'topic' => 'Family Medicine',
                'question' => 'Average-risk adults should begin colorectal cancer screening at what age?',
                'choices' => [
                    'Age 45',
                    'Age 30',
                    'Age 55',
                    'Not needed if FOBT normal',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Updated guidelines recommend initiating colorectal cancer screening at age 45 for average-risk individuals using colonoscopy or equivalent tests.',
            ],
            [
                'topic' => 'Ophthalmology',
                'question' => 'Acute angle-closure glaucoma should first be treated with which medication?',
                'choices' => [
                    'Topical timolol combined with systemic acetazolamide',
                    'Long-term prostaglandin analogs only',
                    'Topical corticosteroids',
                    'Observation',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Acute angle-closure glaucoma requires immediate pressure reduction using beta-blockers (timolol) and carbonic anhydrase inhibitors (acetazolamide), followed by laser iridotomy.',
            ],
            [
                'topic' => 'Otolaryngology',
                'question' => 'A diabetic swimmer with severe otalgia and granulation tissue in the ear canal most likely has infection with:',
                'choices' => [
                    'Pseudomonas aeruginosa (malignant otitis externa)',
                    'Streptococcus pyogenes',
                    'Candida albicans',
                    'Herpesvirus',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Malignant otitis externa in diabetics is typically caused by Pseudomonas aeruginosa and requires aggressive antipseudomonal therapy to prevent skull base osteomyelitis.',
            ],
            [
                'topic' => 'Urology',
                'question' => 'An elevated PSA after radical prostatectomy is best interpreted as:',
                'choices' => [
                    'Biochemical recurrence requiring further staging',
                    'Residual benign hyperplasia',
                    'Expected PSA bounce',
                    'Laboratory error',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Any detectable PSA after radical prostatectomy suggests recurrent disease and warrants further evaluation with imaging and possible salvage therapy.',
            ],
            [
                'topic' => 'Psychiatry',
                'question' => 'Which medication is first-line for long-term management of obsessive-compulsive disorder?',
                'choices' => [
                    'High-dose selective serotonin reuptake inhibitors',
                    'Short-acting benzodiazepines',
                    'Low-dose first-generation antipsychotics',
                    'Tricyclic antidepressants',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'SSRIs at higher doses (often 2-3x antidepressant doses) combined with CBT are first-line for OCD. Higher doses are typically required than for depression.',
            ],
            [
                'topic' => 'Immunology',
                'question' => 'Recurrent swelling of face and airway without urticaria after minor trauma is most consistent with deficiency of:',
                'choices' => [
                    'C1 esterase inhibitor (hereditary angioedema)',
                    'Factor V',
                    'Selective IgA',
                    'Properdin',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'C1 inhibitor deficiency permits unchecked bradykinin production, causing hereditary angioedema with recurrent swelling without urticaria.',
            ],
            [
                'topic' => 'Anatomy',
                'question' => 'Compression within the carpal tunnel most directly injures which structure?',
                'choices' => [
                    'Median nerve beneath the flexor retinaculum',
                    'Ulnar nerve in Guyon canal',
                    'Radial artery',
                    'Flexor carpi radialis tendon',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Carpal tunnel syndrome results from compression of the median nerve as it passes through the carpal tunnel beneath the flexor retinaculum.',
            ],
            [
                'topic' => 'Physiology',
                'question' => 'The plateau (phase 2) of the ventricular action potential is primarily due to which ionic current?',
                'choices' => [
                    'Slow influx of calcium through L-type channels',
                    'Rapid efflux of potassium',
                    'Funny current sodium influx',
                    'Chloride influx',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Phase 2 (plateau) of the cardiac action potential is sustained by Ca2+ entry via L-type channels balanced by K+ efflux, creating the characteristic plateau.',
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
                'explanation' => 'PFK-1 commits glucose to glycolysis and is the rate-limiting step. It is inhibited by ATP and citrate (indicating high energy status) and activated by AMP and fructose-2,6-bisphosphate.',
            ],
            [
                'topic' => 'Histology',
                'question' => 'Podocytes within the renal corpuscle perform which function?',
                'choices' => [
                    'Form the visceral layer of Bowman capsule with filtration slits',
                    'Line the parietal layer',
                    'Reabsorb filtered glucose',
                    'Secrete renin',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Podocytes form the visceral layer of Bowman capsule, with foot processes that interdigitate to create the slit diaphragm of the glomerular filtration barrier.',
            ],
            [
                'topic' => 'Musculoskeletal System',
                'question' => 'Which laboratory finding is most characteristic of rheumatoid arthritis?',
                'choices' => [
                    'Positive anti-citrullinated peptide antibodies with elevated ESR',
                    'High-titer anti-dsDNA antibodies',
                    'Elevated creatine kinase',
                    'Hyperuricemia',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Anti-CCP antibodies are highly specific for rheumatoid arthritis and correlate with erosive disease. Elevated ESR indicates inflammation.',
            ],
            [
                'topic' => 'Musculoskeletal System',
                'question' => 'Which therapy provides rapid relief for acute gout flare in a patient with preserved renal function?',
                'choices' => [
                    'High-dose NSAIDs such as indomethacin',
                    'Initiate allopurinol immediately',
                    'Start probenecid during flare',
                    'Colchicine only as prophylaxis',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'NSAIDs are first-line for acute gout flares. Urate-lowering drugs (allopurinol, probenecid) should not be started during an active flare.',
            ],
            [
                'topic' => 'Dermatology',
                'question' => 'An asymmetric pigmented lesion with irregular borders and color variegation should prompt concern for:',
                'choices' => [
                    'Malignant melanoma (ABCDE criteria)',
                    'Seborrheic keratosis',
                    'Benign junctional nevus',
                    'Cherry angioma',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'ABCDE warning signs (Asymmetry, Border irregularity, Color variegation, Diameter >6mm, Evolution) point toward melanoma and require excisional biopsy.',
            ],
            [
                'topic' => 'Dermatology',
                'question' => 'Stevens-Johnson syndrome is most commonly precipitated by:',
                'choices' => [
                    'Sulfonamide antibiotics',
                    'Chronic UV light',
                    'HPV infection',
                    'Staphylococcus colonization',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'High-risk medications such as sulfonamides, allopurinol, and some anticonvulsants trigger SJS/TEN, severe mucocutaneous reactions.',
            ],
            [
                'topic' => 'Oncology',
                'question' => 'Alpha-fetoprotein is most useful for surveillance of which malignancy?',
                'choices' => [
                    'Hepatocellular carcinoma in cirrhotic patients',
                    'Medullary thyroid carcinoma',
                    'Pancreatic adenocarcinoma',
                    'Colorectal carcinoma',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Serum AFP levels correlate with tumor burden in hepatocellular carcinoma and are used for surveillance in high-risk patients (cirrhosis, chronic hepatitis B/C).',
            ],
            [
                'topic' => 'Oncology',
                'question' => 'BRCA1 and BRCA2 gene products normally participate in which cellular process?',
                'choices' => [
                    'Homologous recombination repair of double-strand DNA breaks',
                    'Activation of EGFR receptors',
                    'Promotion of angiogenesis',
                    'Regulation of telomerase',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'BRCA1 and BRCA2 are tumor suppressor genes involved in DNA repair via homologous recombination. Mutations increase risk of breast and ovarian cancer.',
            ],
            [
                'topic' => 'Infectious Diseases',
                'question' => 'What is the recommended prophylaxis for latent tuberculosis infection in an immunocompetent adult?',
                'choices' => [
                    'Daily isoniazid for 9 months with pyridoxine',
                    'Empiric four-drug therapy for 2 months',
                    'Azithromycin weekly for 3 months',
                    'Observation if chest X-ray normal',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Latent TB is treated with 9 months of daily isoniazid (with pyridoxine to prevent neuropathy) or rifamycin-based regimens to prevent reactivation.',
            ],
            [
                'topic' => 'Infectious Diseases',
                'question' => 'What is the recommended prophylaxis for a newborn whose mother has untreated HIV during labor?',
                'choices' => [
                    'Initiate zidovudine monotherapy immediately after birth',
                    'Ceftriaxone for bacterial sepsis',
                    'Palivizumab for RSV',
                    'Oseltamivir if mother has influenza',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Intrapartum HIV exposure requires neonatal zidovudine (AZT) for 6 weeks to reduce vertical transmission risk, along with maternal antiretroviral therapy.',
            ],
            [
                'topic' => 'Public Health',
                'question' => 'How is test sensitivity best defined?',
                'choices' => [
                    'The proportion of true positives correctly identified by the test',
                    'The probability that a positive result truly indicates disease',
                    'The likelihood ratio comparing positive to negative results',
                    'The prevalence of disease in the population',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Sensitivity = true positives / (true positives + false negatives). It measures how well a test detects disease among those who truly have it.',
            ],
            [
                'topic' => 'Public Health',
                'question' => 'How is number needed to treat (NNT) calculated?',
                'choices' => [
                    'It is the inverse of the absolute risk reduction',
                    'It equals the inverse of the relative risk reduction',
                    'It matches the reciprocal of the hazard ratio',
                    'It equals sensitivity divided by specificity',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'NNT = 1 / |control event rate - experimental event rate|. It represents the number of patients who need to be treated to prevent one adverse outcome.',
            ],
            [
                'topic' => 'Evidence-Based Medicine',
                'question' => 'A hazard ratio of 0.70 (95% CI 0.60-0.90) for mortality implies:',
                'choices' => [
                    'The intervention reduces the hazard of death by 30%',
                    'No statistically significant difference',
                    'Control arm had better survival',
                    'Absolute risk reduction of 30 percentage points',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'A hazard ratio below 1 with CI not crossing 1 means the intervention reduces the instantaneous risk of the event by that proportion (30% reduction).',
            ],
            [
                'topic' => 'Evidence-Based Medicine',
                'question' => 'Analyzing trial participants in the groups to which they were randomized, regardless of adherence, reflects:',
                'choices' => [
                    'Intention-to-treat analysis',
                    'Per-protocol analysis',
                    'Noninferiority design',
                    'Cross-over methodology',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Intention-to-treat analysis preserves randomization by analyzing participants in their assigned groups regardless of adherence, protecting against attrition bias.',
            ],
            [
                'topic' => 'Medical Ethics',
                'question' => 'Respecting a patient\'s informed refusal of chemotherapy despite high cure rates exemplifies:',
                'choices' => [
                    'Autonomy (honoring patient values and decisions)',
                    'Beneficence (prioritizing best interest)',
                    'Nonmaleficence (avoiding harm)',
                    'Justice (fair resource allocation)',
                ],
                'difficulty' => 'easy',
                'points' => 1,
                'explanation' => 'Autonomy obligates clinicians to respect competent patients\' choices even when they differ from medical recommendations, as long as the patient is informed.',
            ],
            [
                'topic' => 'Medical Ethics',
                'question' => 'Breaking patient confidentiality is ethically acceptable when:',
                'choices' => [
                    'A patient makes a credible threat to harm an identifiable person',
                    'Family members request updates without consent',
                    'Physician disagrees with patient lifestyle',
                    'Diagnosis carries social stigma',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Duty to warn or protect may override confidentiality when there is imminent threat of harm to an identifiable person (Tarasoff principle).',
            ],
            [
                'topic' => 'Clinical Skills',
                'question' => 'Detection of pulsus paradoxus greater than 12 mm Hg most strongly suggests:',
                'choices' => [
                    'Cardiac tamponade in appropriate clinical context',
                    'Aortic regurgitation',
                    'Hypertrophic obstructive cardiomyopathy',
                    'Peripheral arterial disease',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'Marked pulsus paradoxus (>10-12 mm Hg) is classic for cardiac tamponade, severe asthma, or constrictive pericarditis.',
            ],
            [
                'topic' => 'Clinical Skills',
                'question' => 'An ankle-brachial index of 0.60 indicates:',
                'choices' => [
                    'Moderate peripheral arterial disease',
                    'Normal perfusion',
                    'Mild disease requiring no intervention',
                    'Noncompressible calcified vessels',
                ],
                'difficulty' => 'medium',
                'points' => 2,
                'explanation' => 'ABI 0.40-0.69 corresponds to moderate PAD associated with exertional claudication and warrants vascular referral.',
            ],
        ];
    }
}
