<?php

namespace Database\Seeders;

use App\Models\Institution\InstitutionAssessment;
use App\Models\Institution\InstitutionQuestion;
use App\Models\Institution\InstitutionQuestionChoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class BataanGeneralHospitalExamsSeeder extends Seeder
{
    public function run(): void
    {
        // Find Bataan General Hospital
        $organization = Organization::where('slug', 'bataan-general-hospital')->first();

        if (! $organization) {
            $this->command->error('Bataan General Hospital not found. Please create it first.');

            return;
        }

        // Find a user to be the creator (any user in the organization or first admin)
        $creator = $organization->users()->first() ?? User::role('System Admin')->first();

        if (! $creator) {
            $this->command->error('No user found to be the creator.');

            return;
        }

        $this->command->info("Creating exams for {$organization->name}...");

        // Exam 1: General Nursing Knowledge
        $exam1 = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'General Nursing Knowledge Assessment',
            'description' => 'Comprehensive assessment covering fundamental nursing concepts, patient care, and clinical procedures.',
            'duration_minutes' => 90,
            'total_points' => 50,
            'passing_score' => 75,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        $this->createGeneralNursingQuestions($exam1);
        $this->command->info("✓ Created: {$exam1->title} with 50 questions");

        // Exam 2: Clinical Skills and Patient Safety
        $exam2 = InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Clinical Skills and Patient Safety Examination',
            'description' => 'Assessment focused on clinical skills, patient safety protocols, medication administration, and infection control.',
            'duration_minutes' => 90,
            'total_points' => 50,
            'passing_score' => 75,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        $this->createClinicalSkillsQuestions($exam2);
        $this->command->info("✓ Created: {$exam2->title} with 50 questions");

        $this->command->info('✅ Successfully created 2 exams with 50 questions each!');
    }

    private function createGeneralNursingQuestions(InstitutionAssessment $exam): void
    {
        $questions = [
            // Vital Signs & Assessment
            [
                'text' => 'What is the normal range for an adult\'s resting heart rate?',
                'choices' => ['40-50 bpm', '60-100 bpm', '100-120 bpm', '120-140 bpm'],
                'correct' => 1,
            ],
            [
                'text' => 'Which of the following blood pressure readings would be considered hypertensive crisis?',
                'choices' => ['120/80 mmHg', '140/90 mmHg', '160/100 mmHg', '180/120 mmHg'],
                'correct' => 3,
            ],
            [
                'text' => 'Normal respiratory rate for an adult is:',
                'choices' => ['8-10 breaths/min', '12-20 breaths/min', '20-30 breaths/min', '30-40 breaths/min'],
                'correct' => 1,
            ],
            [
                'text' => 'The proper sequence for abdominal assessment is:',
                'choices' => ['Inspect, palpate, percuss, auscultate', 'Inspect, auscultate, percuss, palpate', 'Auscultate, inspect, palpate, percuss', 'Palpate, percuss, inspect, auscultate'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient has a temperature of 38.5°C (101.3°F). This is classified as:',
                'choices' => ['Hypothermia', 'Normal', 'Low-grade fever', 'High fever'],
                'correct' => 2,
            ],

            // Patient Care & Comfort
            [
                'text' => 'What is the most important nursing action to prevent pressure ulcers in bedridden patients?',
                'choices' => ['Apply moisturizing lotion', 'Reposition every 2 hours', 'Use oxygen therapy', 'Increase protein intake'],
                'correct' => 1,
            ],
            [
                'text' => 'Which position is best for a patient with dyspnea?',
                'choices' => ['Supine', 'Prone', 'Fowler\'s', 'Trendelenburg'],
                'correct' => 2,
            ],
            [
                'text' => 'The primary purpose of ROM (Range of Motion) exercises is to:',
                'choices' => ['Increase blood pressure', 'Prevent joint contractures', 'Reduce fever', 'Improve vision'],
                'correct' => 1,
            ],
            [
                'text' => 'When providing oral care to an unconscious patient, the patient should be positioned:',
                'choices' => ['Supine', 'Side-lying', 'Prone', 'Trendelenburg'],
                'correct' => 1,
            ],
            [
                'text' => 'The most effective method to prevent healthcare-associated infections is:',
                'choices' => ['Wearing gloves at all times', 'Hand hygiene', 'Using antibiotics', 'Isolating all patients'],
                'correct' => 1,
            ],

            // Medication Administration
            [
                'text' => 'The "Five Rights" of medication administration include all EXCEPT:',
                'choices' => ['Right patient', 'Right documentation', 'Right insurance', 'Right route'],
                'correct' => 2,
            ],
            [
                'text' => 'When administering an intramuscular injection to an adult, the needle should be inserted at what angle?',
                'choices' => ['15 degrees', '45 degrees', '90 degrees', '180 degrees'],
                'correct' => 2,
            ],
            [
                'text' => 'The preferred site for IM injection in adults is:',
                'choices' => ['Deltoid', 'Ventrogluteal', 'Dorsogluteal', 'Vastus lateralis'],
                'correct' => 1,
            ],
            [
                'text' => 'Before administering digoxin, the nurse must assess:',
                'choices' => ['Blood pressure', 'Apical pulse', 'Temperature', 'Respiratory rate'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient is ordered 500mg of medication. The medication is supplied as 250mg/tablet. How many tablets should be given?',
                'choices' => ['1 tablet', '2 tablets', '3 tablets', '4 tablets'],
                'correct' => 1,
            ],

            // Infection Control
            [
                'text' => 'Which type of isolation precaution requires an N95 respirator?',
                'choices' => ['Contact', 'Droplet', 'Airborne', 'Standard'],
                'correct' => 2,
            ],
            [
                'text' => 'The proper order for donning PPE is:',
                'choices' => ['Gloves, gown, mask, goggles', 'Gown, mask, goggles, gloves', 'Mask, goggles, gown, gloves', 'Goggles, mask, gloves, gown'],
                'correct' => 1,
            ],
            [
                'text' => 'When removing PPE, which item should be removed first?',
                'choices' => ['Mask', 'Gloves', 'Gown', 'Goggles'],
                'correct' => 1,
            ],
            [
                'text' => 'Standard precautions apply to:',
                'choices' => ['Only infected patients', 'All patients', 'Only immunocompromised patients', 'Only surgical patients'],
                'correct' => 1,
            ],
            [
                'text' => 'How long should hands be scrubbed when performing surgical hand antisepsis?',
                'choices' => ['15 seconds', '30 seconds', '2-3 minutes', '10 minutes'],
                'correct' => 2,
            ],

            // Documentation & Communication
            [
                'text' => 'Which documentation principle is violated if the nurse charts before performing care?',
                'choices' => ['Accuracy', 'Completeness', 'Timeliness', 'Legibility'],
                'correct' => 0,
            ],
            [
                'text' => 'The SBAR communication tool stands for:',
                'choices' => ['Situation, Background, Assessment, Recommendation', 'Safety, Background, Action, Results', 'Situation, Baseline, Action, Report', 'Safety, Behavior, Assessment, Recommendation'],
                'correct' => 0,
            ],
            [
                'text' => 'When documenting an error, the nurse should:',
                'choices' => ['Use white-out to correct', 'Draw a single line through the error', 'Erase completely', 'Start a new chart'],
                'correct' => 1,
            ],
            [
                'text' => 'Objective data in nursing assessment includes:',
                'choices' => ['Patient\'s pain rating', 'Patient\'s feelings', 'Vital signs', 'Patient\'s concerns'],
                'correct' => 2,
            ],
            [
                'text' => 'HIPAA regulations protect:',
                'choices' => ['Hospital liability', 'Patient privacy', 'Nurse credentials', 'Insurance coverage'],
                'correct' => 1,
            ],

            // Nutrition & Fluid Balance
            [
                'text' => 'A patient on a low-sodium diet should avoid:',
                'choices' => ['Fresh fruits', 'Processed meats', 'Whole grains', 'Lean poultry'],
                'correct' => 1,
            ],
            [
                'text' => 'Signs of dehydration include all EXCEPT:',
                'choices' => ['Dark urine', 'Dry mucous membranes', 'Bradycardia', 'Decreased skin turgor'],
                'correct' => 2,
            ],
            [
                'text' => 'A patient with dysphagia should be instructed to:',
                'choices' => ['Eat quickly', 'Tilt head back', 'Tuck chin when swallowing', 'Lie down while eating'],
                'correct' => 2,
            ],
            [
                'text' => 'Normal urine output for an adult is approximately:',
                'choices' => ['10-20 mL/hr', '30-50 mL/hr', '100-150 mL/hr', '200-300 mL/hr'],
                'correct' => 1,
            ],
            [
                'text' => 'The most reliable indicator of fluid balance is:',
                'choices' => ['Skin turgor', 'Daily weight', 'Blood pressure', 'Urine color'],
                'correct' => 1,
            ],

            // Safety & Emergency
            [
                'text' => 'The first action when discovering a fire is to:',
                'choices' => ['Pull the fire alarm', 'Remove patients from danger', 'Contain the fire', 'Extinguish the fire'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient is found unresponsive. The first nursing action should be:',
                'choices' => ['Start CPR', 'Check for breathing and pulse', 'Call for help', 'Get the crash cart'],
                'correct' => 1,
            ],
            [
                'text' => 'Which color of hospital code typically indicates a fire?',
                'choices' => ['Code Blue', 'Code Red', 'Code Pink', 'Code White'],
                'correct' => 1,
            ],
            [
                'text' => 'The proper compression-to-ventilation ratio for adult CPR by a single rescuer is:',
                'choices' => ['15:2', '30:2', '15:1', '30:1'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient who is at risk for falls should have:',
                'choices' => ['Bed in high position', 'Call light within reach', 'Side rails all up', 'Restraints applied'],
                'correct' => 1,
            ],

            // Medication Knowledge
            [
                'text' => 'Morphine sulfate is classified as a:',
                'choices' => ['Antibiotic', 'Opioid analgesic', 'Anticoagulant', 'Diuretic'],
                'correct' => 1,
            ],
            [
                'text' => 'The antidote for warfarin overdose is:',
                'choices' => ['Protamine sulfate', 'Naloxone', 'Vitamin K', 'Activated charcoal'],
                'correct' => 2,
            ],
            [
                'text' => 'A patient on warfarin therapy should avoid foods high in:',
                'choices' => ['Calcium', 'Vitamin K', 'Protein', 'Iron'],
                'correct' => 1,
            ],
            [
                'text' => 'Which lab value must be monitored for a patient receiving heparin?',
                'choices' => ['PT/INR', 'aPTT', 'CBC', 'BUN'],
                'correct' => 1,
            ],
            [
                'text' => 'The therapeutic level of potassium is:',
                'choices' => ['1.5-3.0 mEq/L', '3.5-5.0 mEq/L', '5.5-7.0 mEq/L', '7.5-9.0 mEq/L'],
                'correct' => 1,
            ],

            // Patient Education
            [
                'text' => 'When teaching a diabetic patient about foot care, the nurse should emphasize:',
                'choices' => ['Soaking feet daily', 'Walking barefoot', 'Daily inspection of feet', 'Using heating pads'],
                'correct' => 2,
            ],
            [
                'text' => 'A patient with hypertension should be taught to:',
                'choices' => ['Increase sodium intake', 'Stop medication when BP is normal', 'Monitor BP regularly', 'Avoid all exercise'],
                'correct' => 2,
            ],
            [
                'text' => 'The best time to provide patient education is:',
                'choices' => ['During acute pain', 'When patient is anxious', 'When patient is comfortable and alert', 'Right before discharge'],
                'correct' => 2,
            ],
            [
                'text' => 'Teach-back method is used to:',
                'choices' => ['Save time', 'Verify patient understanding', 'Document teaching', 'Reduce anxiety'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient prescribed antibiotics should be taught to:',
                'choices' => ['Stop when feeling better', 'Complete full course', 'Take with alcohol', 'Share with family'],
                'correct' => 1,
            ],

            // Wound Care
            [
                'text' => 'A stage 2 pressure ulcer is characterized by:',
                'choices' => ['Intact skin with redness', 'Partial thickness skin loss', 'Full thickness skin loss', 'Bone exposure'],
                'correct' => 1,
            ],
            [
                'text' => 'The proper technique for wound irrigation is:',
                'choices' => ['Use forceful pressure', 'Use gentle, steady stream', 'Use circular motions', 'Use dry gauze only'],
                'correct' => 1,
            ],
            [
                'text' => 'When removing a dressing, the nurse should:',
                'choices' => ['Pull quickly', 'Remove toward the wound', 'Remove away from the wound', 'Soak with alcohol first'],
                'correct' => 2,
            ],
            [
                'text' => 'Signs of wound infection include all EXCEPT:',
                'choices' => ['Redness', 'Warmth', 'Decreased pain', 'Purulent drainage'],
                'correct' => 2,
            ],
            [
                'text' => 'A surgical wound healing by primary intention means:',
                'choices' => ['Wound edges approximated', 'Wound left open to heal', 'Wound packed with gauze', 'Wound requires skin graft'],
                'correct' => 0,
            ],
        ];

        $this->createQuestionsFromArray($exam, $questions);
    }

    private function createClinicalSkillsQuestions(InstitutionAssessment $exam): void
    {
        $questions = [
            // IV Therapy
            [
                'text' => 'The most common complication of IV therapy is:',
                'choices' => ['Air embolism', 'Phlebitis', 'Pulmonary edema', 'Cardiac arrest'],
                'correct' => 1,
            ],
            [
                'text' => 'When selecting a vein for IV insertion, the nurse should start:',
                'choices' => ['At the most proximal site', 'At the most distal site', 'At the antecubital fossa', 'At the hand dorsum'],
                'correct' => 1,
            ],
            [
                'text' => 'IV fluid with 0.9% sodium chloride is classified as:',
                'choices' => ['Hypotonic', 'Isotonic', 'Hypertonic', 'Colloid'],
                'correct' => 1,
            ],
            [
                'text' => 'Signs of IV infiltration include:',
                'choices' => ['Redness along vein', 'Coolness at site', 'Rapid infusion', 'Decreased blood pressure'],
                'correct' => 1,
            ],
            [
                'text' => 'The appropriate gauge needle for blood transfusion is:',
                'choices' => ['25 gauge', '22 gauge', '18 gauge', '14 gauge'],
                'correct' => 2,
            ],

            // Catheter Care
            [
                'text' => 'When inserting a urinary catheter, the nurse encounters resistance. The appropriate action is:',
                'choices' => ['Apply more force', 'Remove and reinsert', 'Notify physician', 'Continue slowly'],
                'correct' => 2,
            ],
            [
                'text' => 'The drainage bag of a urinary catheter should be positioned:',
                'choices' => ['Above the bladder', 'At bladder level', 'Below the bladder', 'On the bed'],
                'correct' => 2,
            ],
            [
                'text' => 'Catheter-associated UTI can be prevented by:',
                'choices' => ['Daily catheter changes', 'Keeping drainage bag elevated', 'Maintaining closed system', 'Using antibiotic ointment'],
                'correct' => 2,
            ],
            [
                'text' => 'When collecting urine specimen from a catheter, the nurse should:',
                'choices' => ['Disconnect catheter', 'Use sampling port', 'Empty drainage bag', 'Clamp for 24 hours'],
                'correct' => 1,
            ],
            [
                'text' => 'Before removing a urinary catheter, the nurse should:',
                'choices' => ['Inflate balloon', 'Deflate balloon', 'Cut the tubing', 'Clamp the catheter'],
                'correct' => 1,
            ],

            // Oxygen Therapy
            [
                'text' => 'A nasal cannula can safely deliver oxygen at:',
                'choices' => ['1-6 L/min', '8-10 L/min', '12-15 L/min', '15-20 L/min'],
                'correct' => 0,
            ],
            [
                'text' => 'The device that delivers the highest concentration of oxygen is:',
                'choices' => ['Nasal cannula', 'Simple face mask', 'Non-rebreather mask', 'Venturi mask'],
                'correct' => 2,
            ],
            [
                'text' => 'When administering oxygen, the nurse should:',
                'choices' => ['Ensure tubing is kinked', 'Humidify oxygen > 4L/min', 'Use oil-based lubricants', 'Keep away from water'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient receiving oxygen therapy should be monitored for:',
                'choices' => ['Hyperglycemia', 'Oxygen toxicity', 'Hypothermia', 'Alkalosis'],
                'correct' => 1,
            ],
            [
                'text' => 'Pulse oximetry measures:',
                'choices' => ['Heart rate only', 'Blood pressure', 'Oxygen saturation', 'Respiratory rate'],
                'correct' => 2,
            ],

            // Blood Transfusion
            [
                'text' => 'Before starting a blood transfusion, two nurses must verify:',
                'choices' => ['Patient age', 'Blood type and patient ID', 'Insurance information', 'Room number'],
                'correct' => 1,
            ],
            [
                'text' => 'Vital signs during blood transfusion should be monitored:',
                'choices' => ['Only at start', 'Every hour', 'Every 15 min for first hour', 'Only at end'],
                'correct' => 2,
            ],
            [
                'text' => 'A hemolytic transfusion reaction is characterized by:',
                'choices' => ['Mild itching', 'Fever and chills', 'Shortness of breath', 'All of the above'],
                'correct' => 3,
            ],
            [
                'text' => 'If a transfusion reaction is suspected, the first action is to:',
                'choices' => ['Slow the rate', 'Stop the transfusion', 'Give antihistamine', 'Call family'],
                'correct' => 1,
            ],
            [
                'text' => 'Blood products should not be infused with:',
                'choices' => ['Normal saline', 'Lactated Ringer\'s', 'Medications', 'All of the above'],
                'correct' => 3,
            ],

            // Wound Drains
            [
                'text' => 'A Jackson-Pratt drain works by:',
                'choices' => ['Gravity', 'Negative pressure', 'Positive pressure', 'Osmosis'],
                'correct' => 1,
            ],
            [
                'text' => 'When emptying a JP drain, the nurse should:',
                'choices' => ['Leave drain open', 'Compress drain before closing', 'Fill with saline', 'Remove drain'],
                'correct' => 1,
            ],
            [
                'text' => 'A sudden increase in drainage from a surgical drain may indicate:',
                'choices' => ['Normal healing', 'Internal bleeding', 'Dehydration', 'Infection resolved'],
                'correct' => 1,
            ],
            [
                'text' => 'Hemovac drain output should be documented:',
                'choices' => ['Weekly', 'Daily', 'Each shift', 'Only when full'],
                'correct' => 2,
            ],
            [
                'text' => 'The color of normal post-operative drainage initially is:',
                'choices' => ['Clear', 'Sanguineous', 'Purulent', 'Green'],
                'correct' => 1,
            ],

            // Nasogastric Tube
            [
                'text' => 'Proper placement of an NG tube is verified by:',
                'choices' => ['Patient comfort', 'X-ray confirmation', 'pH testing of aspirate', 'Both B and C'],
                'correct' => 3,
            ],
            [
                'text' => 'When irrigating an NG tube, the nurse should use:',
                'choices' => ['Sterile water', 'Normal saline', 'Distilled water', 'Tap water'],
                'correct' => 1,
            ],
            [
                'text' => 'The appropriate position for NG tube insertion is:',
                'choices' => ['Supine', 'High Fowler\'s', 'Prone', 'Trendelenburg'],
                'correct' => 1,
            ],
            [
                'text' => 'Complications of NG tube insertion include:',
                'choices' => ['Aspiration', 'Esophageal perforation', 'Sinusitis', 'All of the above'],
                'correct' => 3,
            ],
            [
                'text' => 'An NG tube connected to suction should be checked for:',
                'choices' => ['Color of drainage', 'Patency', 'Amount of drainage', 'All of the above'],
                'correct' => 3,
            ],

            // Diabetic Care
            [
                'text' => 'Signs of hypoglycemia include:',
                'choices' => ['Dry skin, thirst', 'Shakiness, sweating', 'Fruity breath', 'Kussmaul respirations'],
                'correct' => 1,
            ],
            [
                'text' => 'A blood glucose reading of 50 mg/dL should be treated with:',
                'choices' => ['Insulin', '15g fast-acting carbohydrate', 'Exercise', 'Water'],
                'correct' => 1,
            ],
            [
                'text' => 'Regular insulin should be given:',
                'choices' => ['After meals', '30 min before meals', '1 hour after meals', 'At bedtime only'],
                'correct' => 1,
            ],
            [
                'text' => 'The preferred site for insulin injection is:',
                'choices' => ['Deltoid', 'Abdomen', 'Dorsogluteal', 'Vastus lateralis'],
                'correct' => 1,
            ],
            [
                'text' => 'HbA1C test measures blood glucose control over:',
                'choices' => ['24 hours', '1 week', '2-3 months', '6 months'],
                'correct' => 2,
            ],

            // Respiratory Care
            [
                'text' => 'Incentive spirometry is used to:',
                'choices' => ['Measure oxygen saturation', 'Prevent atelectasis', 'Increase heart rate', 'Deliver medications'],
                'correct' => 1,
            ],
            [
                'text' => 'A patient should use incentive spirometry:',
                'choices' => ['Once daily', 'Every hour while awake', 'Only when dyspneic', 'Before bed only'],
                'correct' => 1,
            ],
            [
                'text' => 'Pursed-lip breathing helps patients with COPD by:',
                'choices' => ['Increasing oxygen intake', 'Preventing airway collapse', 'Reducing heart rate', 'Lowering blood pressure'],
                'correct' => 1,
            ],
            [
                'text' => 'Chest physiotherapy is contraindicated in patients with:',
                'choices' => ['Pneumonia', 'Rib fractures', 'Atelectasis', 'Cystic fibrosis'],
                'correct' => 1,
            ],
            [
                'text' => 'Normal arterial blood gas pH is:',
                'choices' => ['7.15-7.25', '7.35-7.45', '7.55-7.65', '7.75-7.85'],
                'correct' => 1,
            ],

            // Pain Management
            [
                'text' => 'The most reliable indicator of pain is:',
                'choices' => ['Vital signs', 'Patient\'s report', 'Facial expressions', 'Body language'],
                'correct' => 1,
            ],
            [
                'text' => 'A pain scale of 0-10 where 10 is worst pain is called:',
                'choices' => ['FACES scale', 'Numeric rating scale', 'FLACC scale', 'Wong-Baker scale'],
                'correct' => 1,
            ],
            [
                'text' => 'Non-pharmacological pain relief includes:',
                'choices' => ['Distraction', 'Heat/cold therapy', 'Positioning', 'All of the above'],
                'correct' => 3,
            ],
            [
                'text' => 'Patient-controlled analgesia (PCA) allows patients to:',
                'choices' => ['Give unlimited medication', 'Self-administer preset doses', 'Change medication type', 'Share with others'],
                'correct' => 1,
            ],
            [
                'text' => 'The antidote for opioid overdose is:',
                'choices' => ['Flumazenil', 'Naloxone', 'Atropine', 'Epinephrine'],
                'correct' => 1,
            ],

            // Postoperative Care
            [
                'text' => 'The priority assessment in immediate postoperative period is:',
                'choices' => ['Pain level', 'Airway patency', 'Wound drainage', 'Urine output'],
                'correct' => 1,
            ],
            [
                'text' => 'Deep breathing and coughing exercises should be performed:',
                'choices' => ['Once daily', 'Every 2 hours', 'Only if dyspneic', 'Only with pain'],
                'correct' => 1,
            ],
            [
                'text' => 'Signs of postoperative hemorrhage include:',
                'choices' => ['Decreased heart rate', 'Increased blood pressure', 'Restlessness and tachycardia', 'Decreased respirations'],
                'correct' => 2,
            ],
            [
                'text' => 'Postoperative nausea and vomiting can be reduced by:',
                'choices' => ['Rapid position changes', 'Early ambulation after anesthesia', 'Slow position changes', 'Large meals'],
                'correct' => 2,
            ],
            [
                'text' => 'The earliest sign of postoperative surgical site infection is:',
                'choices' => ['Fever', 'Purulent drainage', 'Increased pain', 'Wound dehiscence'],
                'correct' => 2,
            ],
        ];

        $this->createQuestionsFromArray($exam, $questions);
    }

    private function createQuestionsFromArray(InstitutionAssessment $exam, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            $question = InstitutionQuestion::create([
                'assessment_id' => $exam->id,
                'question_type' => 'multiple_choice',
                'question_text' => $questionData['text'],
                'points' => 1,
                'order' => $index + 1,
            ]);

            foreach ($questionData['choices'] as $choiceIndex => $choiceText) {
                InstitutionQuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $choiceText,
                    'is_correct' => $choiceIndex === $questionData['correct'],
                    'order' => $choiceIndex + 1,
                ]);
            }
        }
    }
}
