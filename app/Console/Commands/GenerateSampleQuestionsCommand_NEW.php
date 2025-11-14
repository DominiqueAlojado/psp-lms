<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class GenerateSampleQuestionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:generate-sample {--output=sample-questions.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a sample Excel file with 25 realistic medical exam questions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $questions = $this->getSampleQuestions();

        $filename = $this->option('output');
        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $filename);

        // Create Excel file - using 'public' disk instead of path
        Excel::store(new \App\Exports\SampleQuestionsExport($questions), $filename, 'public');

        $this->info("✓ Generated {$filename} with " . count($questions) . ' sample questions');
        $this->info("✓ Location: {$path}");
        $this->info("✓ Download URL: /question-bank/sample-template");

        return self::SUCCESS;
    }

    private function getSampleQuestions(): array
    {
        // Format: choice_1_correct_answer is ALWAYS correct
        // choice_2, choice_3, choice_4 are incorrect
        return [
            // Question 1
            [
                'question_text' => 'What is the normal resting heart rate for adults?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => '60-100 bpm',
                'choice_2' => '40-60 bpm',
                'choice_3' => '100-120 bpm',
                'choice_4' => '120-140 bpm',
                'explanation_optional' => 'The normal resting heart rate for adults ranges from 60 to 100 beats per minute.',
                'topic_optional' => 'Cardiology',
            ],
            // Question 2
            [
                'question_text' => 'Which chamber of the heart receives oxygenated blood from the lungs?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Left atrium',
                'choice_2' => 'Right atrium',
                'choice_3' => 'Right ventricle',
                'choice_4' => 'Left ventricle',
                'explanation_optional' => 'The left atrium receives oxygenated blood from the pulmonary veins.',
                'topic_optional' => 'Anatomy',
            ],
            // Question 3
            [
                'question_text' => 'Insulin is produced by which cells in the pancreas?',
                'type' => 'multiple_choice',
                'points' => 2,
                'choice_1_correct_answer' => 'Beta cells',
                'choice_2' => 'Alpha cells',
                'choice_3' => 'Delta cells',
                'choice_4' => 'Acinar cells',
                'explanation_optional' => 'Beta cells in the islets of Langerhans produce insulin.',
                'topic_optional' => 'Endocrinology',
            ],
            // Question 4
            [
                'question_text' => 'The first line of defense against infection includes the skin and mucous membranes.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Physical barriers like skin and mucous membranes are part of the innate immune system.',
                'topic_optional' => 'Immunology',
            ],
            // Question 5
            [
                'question_text' => 'What is the most common cause of bacterial pneumonia in adults?',
                'type' => 'multiple_choice',
                'points' => 2,
                'choice_1_correct_answer' => 'Streptococcus pneumoniae',
                'choice_2' => 'Staphylococcus aureus',
                'choice_3' => 'Haemophilus influenzae',
                'choice_4' => 'Mycoplasma pneumoniae',
                'explanation_optional' => 'Streptococcus pneumoniae is the most common bacterial cause of pneumonia in adults.',
                'topic_optional' => 'Infectious Disease',
            ],
            // Question 6
            [
                'question_text' => 'Which hormone regulates calcium levels in the blood?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Parathyroid hormone',
                'choice_2' => 'Insulin',
                'choice_3' => 'Thyroxine',
                'choice_4' => 'Cortisol',
                'explanation_optional' => 'Parathyroid hormone (PTH) regulates calcium and phosphate metabolism.',
                'topic_optional' => 'Endocrinology',
            ],
            // Question 7
            [
                'question_text' => 'The kidneys are retroperitoneal organs.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'The kidneys are located behind the peritoneum, making them retroperitoneal organs.',
                'topic_optional' => 'Anatomy',
            ],
            // Question 8
            [
                'question_text' => 'What is the primary function of hemoglobin?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Transport oxygen',
                'choice_2' => 'Fight infection',
                'choice_3' => 'Clot blood',
                'choice_4' => 'Regulate temperature',
                'explanation_optional' => 'Hemoglobin binds to oxygen in the lungs and transports it to tissues throughout the body.',
                'topic_optional' => 'Hematology',
            ],
            // Question 9
            [
                'question_text' => 'Which part of the brain controls balance and coordination?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Cerebellum',
                'choice_2' => 'Cerebrum',
                'choice_3' => 'Medulla oblongata',
                'choice_4' => 'Hypothalamus',
                'explanation_optional' => 'The cerebellum is responsible for coordination, balance, and fine motor control.',
                'topic_optional' => 'Neurology',
            ],
            // Question 10
            [
                'question_text' => 'What is the normal pH range of human blood?',
                'type' => 'multiple_choice',
                'points' => 2,
                'choice_1_correct_answer' => '7.35 - 7.45',
                'choice_2' => '7.00 - 7.20',
                'choice_3' => '7.50 - 7.70',
                'choice_4' => '7.80 - 8.00',
                'explanation_optional' => 'Normal blood pH is tightly regulated between 7.35 and 7.45.',
                'topic_optional' => 'Biochemistry',
            ],
            // Question 11
            [
                'question_text' => 'Antibiotics are effective against viral infections.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'False',
                'choice_2' => 'True',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Antibiotics only work against bacteria, not viruses.',
                'topic_optional' => 'Pharmacology',
            ],
            // Question 12
            [
                'question_text' => 'What type of immunity is provided by vaccination?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Artificial active immunity',
                'choice_2' => 'Natural passive immunity',
                'choice_3' => 'Artificial passive immunity',
                'choice_4' => 'Natural active immunity',
                'explanation_optional' => 'Vaccines provide active artificial immunity by stimulating the immune system.',
                'topic_optional' => 'Immunology',
            ],
            // Question 13
            [
                'question_text' => 'Which enzyme breaks down proteins in the stomach?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Pepsin',
                'choice_2' => 'Amylase',
                'choice_3' => 'Lipase',
                'choice_4' => 'Trypsin',
                'explanation_optional' => 'Pepsin is the main enzyme responsible for protein digestion in the stomach.',
                'topic_optional' => 'Gastroenterology',
            ],
            // Question 14
            [
                'question_text' => 'The liver produces bile.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'The liver produces bile, which is stored in the gallbladder and released to aid in fat digestion.',
                'topic_optional' => 'Gastroenterology',
            ],
            // Question 15
            [
                'question_text' => 'What is the largest organ in the human body?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Skin',
                'choice_2' => 'Liver',
                'choice_3' => 'Brain',
                'choice_4' => 'Heart',
                'explanation_optional' => 'The skin is the largest organ, covering approximately 20 square feet in adults.',
                'topic_optional' => 'Anatomy',
            ],
            // Question 16
            [
                'question_text' => 'Which bone is the longest in the human body?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Femur',
                'choice_2' => 'Tibia',
                'choice_3' => 'Humerus',
                'choice_4' => 'Fibula',
                'explanation_optional' => 'The femur (thigh bone) is the longest and strongest bone in the human body.',
                'topic_optional' => 'Anatomy',
            ],
            // Question 17
            [
                'question_text' => 'What is the normal adult respiratory rate at rest?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => '12-20 breaths per minute',
                'choice_2' => '8-12 breaths per minute',
                'choice_3' => '20-30 breaths per minute',
                'choice_4' => '30-40 breaths per minute',
                'explanation_optional' => 'The normal respiratory rate for adults at rest is 12-20 breaths per minute.',
                'topic_optional' => 'Respiratory Medicine',
            ],
            // Question 18
            [
                'question_text' => 'Type 1 diabetes is an autoimmune disease.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Type 1 diabetes occurs when the immune system attacks and destroys insulin-producing beta cells.',
                'topic_optional' => 'Endocrinology',
            ],
            // Question 19
            [
                'question_text' => 'What is the primary neurotransmitter in the parasympathetic nervous system?',
                'type' => 'multiple_choice',
                'points' => 2,
                'choice_1_correct_answer' => 'Acetylcholine',
                'choice_2' => 'Dopamine',
                'choice_3' => 'Serotonin',
                'choice_4' => 'Norepinephrine',
                'explanation_optional' => 'Acetylcholine is the main neurotransmitter of the parasympathetic nervous system.',
                'topic_optional' => 'Neurology',
            ],
            // Question 20
            [
                'question_text' => 'Which valve prevents backflow of blood from the left ventricle to the left atrium?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Mitral valve',
                'choice_2' => 'Tricuspid valve',
                'choice_3' => 'Aortic valve',
                'choice_4' => 'Pulmonary valve',
                'explanation_optional' => 'The mitral (bicuspid) valve prevents backflow from the left ventricle to the left atrium.',
                'topic_optional' => 'Cardiology',
            ],
            // Question 21
            [
                'question_text' => 'Osteoporosis is characterized by decreased bone density.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Osteoporosis is a condition where bones become weak and brittle due to loss of bone density.',
                'topic_optional' => 'Rheumatology',
            ],
            // Question 22
            [
                'question_text' => 'What is the most common type of white blood cell?',
                'type' => 'multiple_choice',
                'points' => 1,
                'choice_1_correct_answer' => 'Neutrophil',
                'choice_2' => 'Lymphocyte',
                'choice_3' => 'Monocyte',
                'choice_4' => 'Eosinophil',
                'explanation_optional' => 'Neutrophils make up 50-70% of white blood cells and are first responders to infection.',
                'topic_optional' => 'Hematology',
            ],
            // Question 23
            [
                'question_text' => 'The mitochondria is known as the powerhouse of the cell.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Mitochondria produce ATP through cellular respiration, providing energy for the cell.',
                'topic_optional' => 'Biochemistry',
            ],
            // Question 24
            [
                'question_text' => 'Which organ produces erythropoietin?',
                'type' => 'multiple_choice',
                'points' => 2,
                'choice_1_correct_answer' => 'Kidneys',
                'choice_2' => 'Liver',
                'choice_3' => 'Spleen',
                'choice_4' => 'Bone marrow',
                'explanation_optional' => 'The kidneys produce erythropoietin in response to low oxygen levels to stimulate red blood cell production.',
                'topic_optional' => 'Nephrology',
            ],
            // Question 25
            [
                'question_text' => 'Hypertension is defined as blood pressure consistently above 140/90 mmHg.',
                'type' => 'true_false',
                'points' => 1,
                'choice_1_correct_answer' => 'True',
                'choice_2' => 'False',
                'choice_3' => '',
                'choice_4' => '',
                'explanation_optional' => 'Blood pressure of 140/90 mmHg or higher is considered hypertension and requires medical attention.',
                'topic_optional' => 'Cardiology',
            ],
        ];
    }
}

