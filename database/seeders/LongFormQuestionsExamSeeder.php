<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LongFormQuestionsExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Bataan General Hospital
        $organization = \App\Models\Organization::where('slug', 'bataan-general-hospital')->first();

        if (! $organization) {
            $this->command->error('Bataan General Hospital not found. Please create it first.');

            return;
        }

        // Find a user to be the creator
        $creator = $organization->users()->first() ?? \App\Models\User::role('System Admin')->first();

        if (! $creator) {
            $this->command->error('No user found to be the creator.');

            return;
        }

        $this->command->info("Creating exam with long-form questions for {$organization->name}...");

        // Create exam with long questions
        $exam = \App\Models\Institution\InstitutionAssessment::create([
            'organization_id' => $organization->id,
            'title' => 'Complex Clinical Scenarios and Critical Thinking Assessment',
            'description' => 'This comprehensive assessment evaluates your ability to analyze complex clinical situations, apply critical thinking skills, and make appropriate clinical decisions in challenging healthcare scenarios. Each question presents detailed patient cases requiring careful consideration of multiple factors.',
            'exam_category' => 'Final Exam',
            'duration_minutes' => 120,
            'total_points' => 20,
            'passing_score' => 75,
            'randomize_questions' => true,
            'randomize_choices' => true,
            'show_results_immediately' => true,
            'allow_review' => true,
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        $this->createLongFormQuestions($exam);
        $this->command->info("✓ Created: {$exam->title} with 20 long-form questions");
    }

    private function createLongFormQuestions(\App\Models\Institution\InstitutionAssessment $exam): void
    {
        $questions = [
            [
                'text' => 'A 67-year-old male patient with a history of chronic obstructive pulmonary disease (COPD), type 2 diabetes mellitus, and hypertension presents to the emergency department with increasing shortness of breath, productive cough with yellow-green sputum, and fever of 39.2°C (102.5°F) for the past three days. His oxygen saturation is 88% on room air, respiratory rate is 28 breaths per minute, heart rate is 110 beats per minute, and blood pressure is 150/95 mmHg. He reports medication compliance but states he ran out of his albuterol inhaler two weeks ago. What is the most appropriate initial nursing intervention?',
                'choices' => [
                    'Administer prescribed bronchodilator therapy via nebulizer and apply supplemental oxygen via nasal cannula at 2-3 L/min, monitor oxygen saturation closely, and prepare for potential escalation to higher oxygen delivery methods while avoiding oxygen toxicity in COPD patients',
                    'Immediately place the patient on a non-rebreather mask at 15 L/min to rapidly increase oxygen saturation to 100%, then focus on obtaining vital signs and completing the initial nursing assessment before considering any respiratory treatments',
                    'Position the patient in supine position to facilitate lung expansion, encourage deep breathing exercises without supplemental oxygen to strengthen respiratory muscles, and wait for physician orders before administering any medications or oxygen therapy',
                    'Perform chest physiotherapy and postural drainage immediately to mobilize secretions, then administer high-flow oxygen via face mask at 10 L/min regardless of oxygen saturation levels, and prepare for immediate intubation',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are caring for a 45-year-old female patient who underwent total abdominal hysterectomy 24 hours ago. She has a patient-controlled analgesia (PCA) pump delivering morphine sulfate, an indwelling urinary catheter, sequential compression devices, and a surgical drain. During your assessment, you note that she has received minimal pain relief despite frequent PCA use (averaging 8-10 demands per hour with 3-4 actual doses delivered), her surgical dressing shows a small amount of serosanguineous drainage, urine output is 20 mL for the past 2 hours (previously 50-60 mL/hour), and she rates her pain as 9/10. She appears restless and her skin is cool and clammy. What is your priority action?',
                'choices' => [
                    'Immediately notify the physician about the concerning changes in vital signs, decreased urine output, and ineffective pain management, as these findings may indicate postoperative complications such as hemorrhage, hypovolemia, or inadequate analgesia requiring immediate medical intervention and possible adjustment of the pain management plan',
                    'Teach the patient proper use of the PCA pump, encourage her to use it more frequently before pain becomes severe, reassure her that some pain is normal after major surgery, and document that patient education was provided regarding pain management expectations',
                    'Remove the PCA pump and administer oral pain medication instead, as the patient is clearly not responding to intravenous morphine, then encourage early ambulation to promote healing and prevent complications such as deep vein thrombosis and pneumonia',
                    'Increase the rate of the PCA pump independently to deliver more morphine per dose, apply warm blankets for comfort, encourage the patient to rest quietly in bed, and reassess pain level in 4 hours or at the end of your shift',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 28-year-old primigravida at 38 weeks gestation arrives at the labor and delivery unit reporting regular contractions every 5 minutes, lasting 45-60 seconds, for the past 3 hours. Her membranes ruptured spontaneously at home about 1 hour ago, and she reports clear fluid. On examination, the cervix is 4 cm dilated, 80% effaced, and the fetal head is at -1 station. Fetal heart rate baseline is 145 beats per minute with moderate variability. Suddenly, you note variable decelerations on the fetal monitor dropping to 90 beats per minute, lasting 30-45 seconds, occurring with each contraction. The patient reports feeling increased pelvic pressure. What is the most appropriate nursing action?',
                'choices' => [
                    'Perform immediate vaginal examination to assess for umbilical cord prolapse, change maternal position to left lateral or knee-chest position to relieve potential cord compression, administer oxygen via face mask at 8-10 L/min, discontinue oxytocin if running, initiate continuous fetal monitoring, and notify the physician immediately while preparing for possible emergency cesarean delivery',
                    'Continue to monitor the situation as variable decelerations are completely normal during active labor, reassure the patient that everything is progressing well, encourage her to push with contractions since she reports pelvic pressure, and document the fetal heart rate patterns in the medical record',
                    'Immediately prepare the patient for emergency cesarean section without performing any further assessment, call a code blue to alert all available staff members, and begin rapid fluid resuscitation with two large-bore intravenous lines while restricting all visitors from the room',
                    'Turn off the fetal monitor to reduce the patient\'s anxiety about seeing the heart rate changes, focus on providing comfort measures such as massage and breathing techniques, and suggest that she take a warm shower to help her relax and facilitate labor progression',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are assigned to care for a 72-year-old male patient admitted with acute exacerbation of heart failure. He has a history of coronary artery disease, atrial fibrillation (on warfarin), and chronic kidney disease (Stage 3). Current medications include furosemide 40 mg IV twice daily, metoprolol 50 mg orally twice daily, lisinopril 10 mg orally daily, and warfarin 5 mg orally daily. Laboratory results show: BUN 45 mg/dL, creatinine 2.1 mg/dL, potassium 5.8 mEq/L, INR 4.5, and digoxin level 2.8 ng/mL. The patient is lethargic, reports nausea and seeing yellow-green halos around lights, and has an irregular heart rate of 45 beats per minute. What is your immediate priority?',
                'choices' => [
                    'Withhold all cardiac medications including digoxin, metoprolol, and warfarin; notify the physician immediately about signs of digoxin toxicity (elevated level, visual changes, nausea, bradycardia), hyperkalemia, and supratherapeutic INR; prepare for potential administration of digoxin-specific antibody fragments (Digibind), continuous cardiac monitoring, and urgent treatment of hyperkalemia while monitoring for dysrhythmias',
                    'Administer all scheduled medications as ordered since they were prescribed by the physician, encourage the patient to eat foods high in potassium such as bananas and oranges to correct any electrolyte imbalances, and reassure him that visual changes are a normal part of aging',
                    'Hold only the warfarin due to elevated INR, administer all other medications as scheduled, give the patient orange juice to help with nausea, and plan to recheck INR in 24 hours while continuing current medication regimen without any modifications',
                    'Immediately increase the furosemide dose to 80 mg IV to aggressively treat the heart failure exacerbation, add potassium chloride supplementation to prevent hypokalemia from diuretic therapy, and encourage increased oral fluid intake to prevent dehydration',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 19-year-old college student is brought to the emergency department by friends who report that he attended a party where alcohol and drugs were present. He is unresponsive to verbal stimuli but withdraws from painful stimuli. Vital signs are: blood pressure 95/60 mmHg, heart rate 52 beats per minute, respiratory rate 8 breaths per minute and shallow, temperature 35.8°C (96.4°F), and oxygen saturation 87% on room air. His pupils are pinpoint and equal bilaterally. Friends deny knowing exactly what he consumed but mention that someone at the party mentioned "popping pills." What is your priority intervention?',
                'choices' => [
                    'Establish airway patency, provide respiratory support with bag-valve-mask ventilation if needed, administer naloxone (Narcan) as ordered for suspected opioid overdose based on clinical presentation of respiratory depression and pinpoint pupils, apply supplemental oxygen, obtain intravenous access, initiate continuous monitoring of vital signs and oxygen saturation, and prepare for potential need for intubation and mechanical ventilation',
                    'Immediately start chest compressions since his heart rate is below 60 beats per minute, continue CPR until heart rate increases above 100 beats per minute, then focus on obtaining a full set of vital signs and completing the initial assessment',
                    'Allow the patient to "sleep it off" in a quiet room while monitoring vital signs every 4 hours, provide warm blankets for hypothermia, encourage friends to stay with him to provide support, and reassure everyone that young, healthy individuals typically recover from drug and alcohol intoxication without complications',
                    'Perform gastric lavage immediately to remove any remaining drugs or alcohol from the stomach, administer activated charcoal orally or via nasogastric tube, then place the patient in Trendelenburg position to improve blood pressure and cerebral perfusion',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are caring for a 55-year-old female patient who is postoperative day 2 following a right hemicolectomy for colon cancer. She has a nasogastric tube to low intermittent suction (draining green bilious fluid), a midline abdominal incision with staples, and a peripheral IV infusing D5 ½ NS with 20 mEq KCl at 100 mL/hour. During your morning assessment, she complains of severe abdominal pain (8/10), increasing abdominal distension, nausea, and inability to pass flatus. Her abdomen is firm and tender to palpation with diminished bowel sounds in all quadrants. Last documented bowel movement was 4 days ago (preoperatively). Temperature is 38.3°C (100.9°F), heart rate is 105 beats per minute, and blood pressure is 110/70 mmHg. What is your priority nursing action?',
                'choices' => [
                    'Conduct a comprehensive assessment including checking nasogastric tube placement and patency, measuring abdominal girth, assessing for signs of paralytic ileus versus bowel obstruction, and immediately notify the surgeon about findings suggestive of potential postoperative complications requiring urgent evaluation for possible anastomotic leak, perforation, or complete bowel obstruction that may require surgical intervention',
                    'Administer a strong laxative and enema to promote bowel movement, encourage the patient to ambulate frequently to stimulate peristalsis, increase oral fluid intake significantly, and reassure her that constipation is completely normal and expected after abdominal surgery',
                    'Remove the nasogastric tube since it may be causing nausea and discomfort, advance diet to clear liquids to stimulate bowel function, administer prescribed pain medication, and document that the patient is progressing normally in the postoperative period',
                    'Apply a heating pad to the abdomen for comfort, encourage the patient to rest in bed quietly to allow healing, give anti-gas medication, and wait to reassess the situation during the next shift before taking any further action',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 34-year-old female patient with type 1 diabetes mellitus is admitted to your unit with diabetic ketoacidosis (DKA). Initial laboratory results show: blood glucose 485 mg/dL, pH 7.21, bicarbonate 12 mEq/L, potassium 3.2 mEq/L, sodium 132 mEq/L, and positive serum ketones. She is receiving normal saline IV at 500 mL/hour and a continuous insulin infusion at 0.1 units/kg/hour. After 4 hours of treatment, her blood glucose has decreased to 245 mg/dL. She is now more alert and oriented, stating she feels much better and wants to eat. What is the most appropriate nursing action at this time?',
                'choices' => [
                    'Notify the physician that blood glucose has decreased to 245 mg/dL so that IV fluids can be changed to include dextrose (such as D5 ½ NS) to prevent hypoglycemia while continuing insulin infusion until ketoacidosis resolves, monitor potassium levels closely as they may drop during treatment, continue to monitor blood glucose hourly, and assess for signs of cerebral edema, a potential complication of DKA treatment especially with rapid correction',
                    'Discontinue the insulin infusion immediately since the blood glucose is approaching normal range, stop IV fluids as the patient is now alert and can drink, allow her to eat a full regular diet since she is hungry, and discharge her home with instructions to resume her normal insulin regimen',
                    'Increase the insulin infusion rate to 0.2 units/kg/hour to bring the blood glucose down to 100 mg/dL as quickly as possible, continue normal saline at the current rate, keep the patient NPO, and plan to transition to subcutaneous insulin only after blood glucose reaches 80-100 mg/dL',
                    'Switch from continuous insulin infusion to subcutaneous insulin injections immediately, reduce IV fluid rate to 50 mL/hour since the patient is more alert, allow her to eat anything she wants, and prepare for discharge within the next 2-3 hours',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are working in a pediatric intensive care unit caring for an 8-month-old infant admitted with respiratory syncytial virus (RSV) bronchiolitis. The infant has a history of prematurity (born at 32 weeks gestation) and chronic lung disease. Current interventions include oxygen via nasal cannula at 2 L/min maintaining saturation at 92-94%, IV fluids at maintenance rate, and nasopharyngeal suctioning every 2-3 hours as needed. During feeding, you notice increasing work of breathing with nasal flaring, intercostal and subcostal retractions, grunting, and oxygen saturation dropping to 85%. The infant becomes cyanotic around the mouth, appears exhausted, and has difficulty coordinating sucking, swallowing, and breathing. What is your immediate priority intervention?',
                'choices' => [
                    'Stop feeding immediately, position the infant upright or in semi-Fowler\'s position to optimize respiratory mechanics, increase oxygen delivery and flow as needed to maintain adequate saturation (may require high-flow nasal cannula, CPAP, or face mask), suction nares and oropharynx gently to clear secretions, provide respiratory support, notify the physician immediately about decompensation, and prepare for possible escalation to mechanical ventilation while maintaining NPO status until respiratory status stabilizes',
                    'Continue feeding but slow down the pace, hold the infant in a more upright position, pat the back frequently to help with burping, and encourage the infant to rest between swallows while monitoring oxygen saturation intermittently',
                    'Place the infant flat on the back to open the airway, perform back blows and chest thrusts assuming the infant is choking on the feeding, and prepare to perform infant CPR if the baby becomes unresponsive',
                    'Document that the infant has difficulty feeding (which is normal with RSV), complete the feeding over a longer period of time to ensure adequate nutrition, decrease oxygen flow to encourage the infant to breathe more deeply, and plan to discuss feeding challenges with the parents during the next visit',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 61-year-old male patient is admitted to your telemetry unit following cardiac catheterization with percutaneous coronary intervention (PCI) and stent placement in the left anterior descending artery. The procedure was performed via right femoral artery approach. Postprocedure orders include: bedrest with affected leg straight for 6 hours, monitor vital signs and neurovascular status every 15 minutes for 1 hour then every 30 minutes for 2 hours then every hour, monitor access site for bleeding or hematoma formation, and maintain pressure dressing. Three hours post-procedure, you note that the patient is restless and anxious, complaining of back pain. His blood pressure has decreased from 130/78 mmHg to 95/60 mmHg, heart rate has increased from 75 to 110 beats per minute, and the dressing at the right groin site appears saturated with bright red blood with a rapidly expanding hematoma. What is your immediate action?',
                'choices' => [
                    'Apply firm direct manual pressure above the arterial puncture site (approximately 2-3 cm above the skin puncture), maintain pressure continuously for at least 10-15 minutes, lower the head of the bed to supine position, call for immediate assistance, activate rapid response team or notify physician immediately, obtain vital signs, assess distal pulses and neurovascular status, prepare for potential return to catheterization lab or surgical intervention, establish second IV access, and prepare for possible blood transfusion',
                    'Remove the pressure dressing to better visualize the puncture site and assess the extent of bleeding, apply an ice pack to the area to cause vasoconstriction, elevate the affected leg on pillows to reduce bleeding, and document the incident in the medical record',
                    'Assist the patient to ambulate to the bathroom since he is complaining of back pain which may indicate he needs to void, reassure him that some oozing at the puncture site is normal after cardiac catheterization, and plan to reinforce the dressing with additional gauze at the next scheduled assessment',
                    'Immediately remove the pressure dressing and all arterial access devices, apply a band-aid to the site, allow the patient to bend his leg for comfort since maintaining leg extension may be causing the back pain, and encourage him to rest while you notify the physician during routine rounds',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are caring for a 42-year-old male patient in the intensive care unit who was admitted 5 days ago following a severe traumatic brain injury sustained in a motor vehicle accident. He has an intracranial pressure (ICP) monitor in place, mechanical ventilation, and is receiving continuous sedation. His baseline ICP has been 12-15 mmHg with cerebral perfusion pressure (CPP) of 70-80 mmHg. During your shift, you notice that his ICP suddenly increases to 28 mmHg and remains elevated, CPP decreases to 55 mmHg, he develops a fixed and dilated right pupil, and posturing movements are observed. Blood pressure is 180/95 mmHg (previously 130/75 mmHg), heart rate has decreased from 85 to 52 beats per minute, and respiratory pattern on the ventilator shows irregularity. What is your priority action?',
                'choices' => [
                    'Recognize signs of Cushing\'s triad (hypertension, bradycardia, irregular respirations) indicating increased intracranial pressure and potential herniation syndrome, immediately notify the neurosurgeon and intensivist, elevate head of bed to 30 degrees if not already done, ensure head and neck are in neutral alignment, optimize ventilation to maintain normocarbia, administer osmotic diuretics (mannitol or hypertonic saline) as ordered, prepare for possible emergency decompressive surgery, and initiate interventions to decrease ICP including maintaining adequate CPP, avoiding hyperthermia, and ensuring adequate sedation',
                    'Lower the head of the bed to flat position to improve cerebral blood flow, administer intravenous fluids rapidly to increase blood pressure further, encourage family to speak loudly to the patient to assess level of consciousness, and perform frequent suctioning to maintain airway patency',
                    'Assume the patient is having a seizure, administer emergency anti-seizure medications, place a bite block in the mouth to prevent tongue biting, and restrain all extremities to prevent injury during the seizure activity',
                    'Discontinue all sedation immediately to perform a neurological assessment, remove the ICP monitor as it may be malfunctioning and causing inaccurate readings, and wait for the physician to arrive for morning rounds to discuss the changes in the patient\'s condition',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 76-year-old female patient with end-stage renal disease arrives for her regularly scheduled hemodialysis session. She has a mature arteriovenous (AV) fistula in her left forearm. Pre-dialysis vital signs are: blood pressure 168/95 mmHg, heart rate 88 beats per minute, respiratory rate 22 breaths per minute, temperature 36.8°C (98.2°F), and weight is 68.5 kg (3.2 kg above dry weight). She reports mild shortness of breath and swelling in her ankles. After 2 hours of dialysis, she suddenly complains of severe chest pain radiating to her left arm and jaw, becomes diaphoretic, appears extremely anxious, and states "I feel like I\'m going to die." Her blood pressure drops to 85/50 mmHg, heart rate increases to 118 beats per minute, and oxygen saturation decreases to 88% on room air. What is your immediate priority action?',
                'choices' => [
                    'Recognize signs and symptoms of acute myocardial infarction (chest pain with radiation, diaphoresis, anxiety, hypotension, tachycardia, respiratory distress), immediately stop or slow the dialysis treatment and place patient in Trendelenburg or supine position with legs elevated, administer oxygen via face mask to maintain adequate saturation, return blood volume if significant fluid has been removed, obtain IV access if not already established, perform 12-lead ECG, administer aspirin if not contraindicated, notify physician immediately, activate rapid response or code team as appropriate, continuously monitor vital signs and cardiac rhythm, and prepare for possible transfer to emergency department or cardiac catheterization lab',
                    'Continue dialysis as scheduled since stopping treatment prematurely would prevent adequate fluid and toxin removal, reassure the patient that chest pain and shortness of breath are normal during dialysis, give her water to drink to help with anxiety, and document the symptoms for discussion with the nephrologist at the end of the session',
                    'Assume the patient is having a panic attack due to anxiety about the dialysis procedure, encourage deep breathing exercises and relaxation techniques, dim the lights in the room, and play calming music to help reduce her stress level',
                    'Immediately clamp off the AV fistula access, remove all dialysis needles, apply direct pressure to needle sites for at least 30 minutes, and send the patient home with instructions to rest and follow up with her regular physician the next day',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are working in a psychiatric acute care unit caring for a 29-year-old female patient admitted 2 days ago with major depressive disorder and suicidal ideation following a recent divorce and job loss. She has been on 15-minute safety checks and has appeared somewhat more engaged in milieu activities today. During dinner, you notice she is not in the dining room and find her in her room with multiple superficial cuts on her left forearm that are actively bleeding. A piece of broken plastic from a disposable razor is on the floor. She states calmly, "I deserve this pain. Everyone would be better off without me. I should have done it right this time." What is your priority intervention?',
                'choices' => [
                    'Ensure immediate safety by calmly approaching the patient in a non-threatening manner, assess for additional injuries and weapons, provide immediate wound care using standard precautions, maintain continuous visual observation, activate emergency psychiatric protocols, notify physician and charge nurse immediately, conduct thorough room search and remove all potentially dangerous items, reassess suicide risk and implement increased safety precautions (likely one-to-one observation), document incident thoroughly, review circumstances that allowed access to harmful items, and arrange for psychiatric evaluation to consider possible hospitalization level change or additional interventions',
                    'Lecture the patient about the inappropriateness of her behavior, tell her that self-harm will result in immediate discharge from the unit, restrict all privileges including visitors and phone calls as punishment, and document that the patient is manipulative and attention-seeking',
                    'Take the patient to the emergency department immediately for surgical evaluation and repair of lacerations, leave her in the ED until the wounds are treated, then return her to the unit with instructions not to harm herself again or she will be transferred to another facility',
                    'Give the patient bandages and tell her to clean up the wounds herself to take responsibility for her actions, allow her to keep the razor since confiscating it would violate her personal rights, and encourage her to call you if she feels like hurting herself again',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 3-year-old child is brought to the emergency department by parents reporting that the child ingested an unknown quantity of children\'s chewable vitamins with iron approximately 45 minutes ago while the parents were briefly distracted. The child found the bottle in a kitchen cabinet and the parents estimate that 15-20 tablets may be missing from the bottle (each tablet contains 10 mg of elemental iron). The child initially vomited once at home, appears lethargic, has abdominal pain, and parents report seeing "bloody-looking" vomit. Vital signs are: heart rate 130 beats per minute, respiratory rate 28 breaths per minute, blood pressure 85/50 mmHg (low for age), and temperature 37.2°C (99°F). What is your priority nursing intervention?',
                'choices' => [
                    'Recognize this as a potentially life-threatening iron overdose emergency requiring immediate intervention, establish IV access for fluid resuscitation and medication administration, administer whole bowel irrigation with polyethylene glycol solution as ordered to prevent further iron absorption, prepare for possible chelation therapy with deferoxamine if serum iron levels are significantly elevated, monitor for signs of shock and metabolic acidosis, obtain serum iron level, complete blood count, electrolytes, glucose, and liver function tests, maintain continuous cardiorespiratory monitoring, and closely observe for progression through stages of iron toxicity including GI symptoms, apparent recovery phase, and potentially severe systemic toxicity',
                    'Induce vomiting immediately by giving syrup of ipecac or placing fingers in the child\'s throat, then send the child home with parents since the child has already vomited once and most of the iron has likely been expelled from the stomach',
                    'Reassure parents that children\'s vitamins are completely harmless and non-toxic, give the child juice or milk to drink to dilute the vitamins in the stomach, and provide discharge instructions to monitor the child at home and call if any concerning symptoms develop',
                    'Perform immediate gastric lavage using a large-bore orogastric tube, administer activated charcoal orally or via tube even though it is not effective for iron poisoning, keep child NPO, and wait for iron levels to return before providing any further treatment',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are caring for a 58-year-old female patient on postoperative day 1 following a total hip arthroplasty (posterior approach). She has a patient-controlled analgesia pump, sequential compression devices, and is on chemical thromboprophylaxis with subcutaneous enoxaparin. During your morning assessment, the patient calls you urgently to her room stating she suddenly feels short of breath and has sharp chest pain that worsens when she takes a deep breath. Assessment reveals: respiratory rate 32 breaths per minute, oxygen saturation 89% on room air, heart rate 115 beats per minute, blood pressure 100/65 mmHg, she appears anxious and restless, and you note slight asymmetry in calf size (right calf 2 cm larger than left). What is your priority action?',
                'choices' => [
                    'Recognize potential pulmonary embolism (sudden dyspnea, pleuritic chest pain, tachypnea, tachycardia, hypoxemia, possible deep vein thrombosis source), place patient in high Fowler\'s position to optimize respiratory function, immediately apply supplemental oxygen via face mask to maintain oxygen saturation above 90%, establish IV access or ensure patency of existing line, notify physician immediately for urgent evaluation, prepare for diagnostic testing including arterial blood gas, d-dimer, CT pulmonary angiography or ventilation-perfusion scan, ECG, and chest x-ray, initiate continuous pulse oximetry and cardiac monitoring, reassure patient while preparing for possible anticoagulation therapy adjustment, and implement emergency interventions as ordered',
                    'Assist the patient to ambulate in the hallway to promote deep breathing and circulation, encourage her to perform incentive spirometry exercises, administer the next scheduled dose of enoxaparin early, and reassure her that mild shortness of breath is completely normal after any surgical procedure',
                    'Assume the patient is having an anxiety attack due to pain and the stress of surgery, administer an anxiolytic medication, encourage relaxation techniques and slow deep breathing, dim the lights, and allow her to rest quietly without further assessment',
                    'Perform aggressive chest physiotherapy and postural drainage, increase oral fluid intake significantly, apply warm compresses to both calves, and wait to see if symptoms resolve spontaneously over the next 2-3 hours before notifying anyone',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 15-year-old female patient with a known diagnosis of anorexia nervosa is admitted to the medical unit for nutritional rehabilitation with severe malnutrition (BMI 14.5, weight 75% of ideal body weight). Admission laboratory results show: sodium 132 mEq/L, potassium 2.8 mEq/L, phosphorus 1.9 mg/dL, magnesium 1.4 mg/dL, and glucose 65 mg/dL. A refeeding protocol has been initiated starting with 1200 kcal/day with plans to gradually increase. On day 3 of admission, the patient reports feeling weak, has muscle tremors, develops confusion, experiences cardiac rhythm changes (QTc prolongation on telemetry), and complains of difficulty breathing. What is your priority nursing action?',
                'choices' => [
                    'Recognize signs and symptoms of refeeding syndrome, a potentially fatal complication of nutritional rehabilitation in severely malnourished patients characterized by dangerous shifts in electrolytes (particularly phosphorus, potassium, and magnesium) and fluid balance when nutrition is reintroduced, immediately notify physician about change in condition, obtain stat electrolyte panel including phosphorus and magnesium, prepare for aggressive electrolyte replacement therapy, continuous cardiac monitoring for arrhythmias, possible reduction or temporary hold on nutritional advancement, monitor for complications including cardiac dysfunction, respiratory failure, and neurological changes, and ensure careful monitoring during continued refeeding with slower caloric advancement',
                    'Increase the caloric intake immediately to 3000 kcal/day to provide adequate nutrition more quickly, encourage the patient to eat high-fat, high-sugar foods to rapidly restore body weight, discontinue all electrolyte monitoring since it causes patient anxiety, and focus on psychological counseling',
                    'Assume the patient is manipulating staff and exaggerating symptoms to avoid eating, maintain current feeding plan without modifications, restrict all privileges until weight gain goals are met, and document that patient is non-compliant with treatment',
                    'Stop all nutritional support immediately including oral, enteral, and parenteral nutrition, keep patient NPO, begin total parenteral nutrition through a central line at full caloric needs, and consult psychiatry for behavior management',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are working in a busy emergency department when a 38-year-old male patient arrives via ambulance following a workplace accident involving industrial chemicals. Emergency medical services report potential exposure to both hydrofluoric acid and ammonia vapors. The patient has chemical burns on both hands and forearms (approximately 15% total body surface area), is coughing with audible wheezing, has hoarseness and difficulty speaking, and his clothing is wet with chemical residue. Respiratory rate is 30 breaths per minute with increased work of breathing, oxygen saturation is 90% on high-flow oxygen via non-rebreather mask, and he is complaining of severe burning pain rated 10/10 in affected areas. What is your immediate priority intervention?',
                'choices' => [
                    'Ensure your own safety first by wearing appropriate personal protective equipment (gown, gloves, face shield, respirator if indicated), immediately remove all of the patient\'s contaminated clothing and jewelry using trauma shears while preventing spread of contamination, begin immediate and copious irrigation of affected areas with water or saline for at least 20-30 minutes (longer for hydrofluoric acid), assess and support airway given signs of potential inhalation injury including respiratory distress, hoarseness, and wheezing which may rapidly progress to airway obstruction, notify physician immediately, prepare for possible emergency intubation, administer supplemental oxygen, for hydrofluoric acid burns prepare for specialized treatment including calcium gluconate gel topically and possible systemic calcium administration, continuously monitor cardiac rhythm as hydrofluoric acid can cause life-threatening arrhythmias, and implement burn and trauma protocols',
                    'Immediately apply neutralizing agents to all burned areas (baking soda for acid, vinegar for base) before any irrigation, wrap affected areas in sterile dressings to prevent contamination, encourage the patient to drink large quantities of milk to neutralize internal exposure, and allow him to remove his own clothing in the bathroom',
                    'Rush the patient directly to the operating room for immediate surgical debridement of all affected areas without any preliminary treatment, delay all irrigation until surgical intervention is complete, and focus solely on pain management with high-dose opioid administration',
                    'Take photographs of all injuries before beginning treatment, complete full head-to-toe assessment and detailed documentation, obtain a complete history of the accident, wait for the industrial hygienist to arrive to identify specific chemicals involved before initiating any treatment, and have patient sign consent forms for treatment',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 68-year-old male patient with a history of chronic atrial fibrillation, hypertension, and transient ischemic attacks (TIAs) is admitted to your stroke unit with sudden onset of right-sided weakness, facial droop, and slurred speech that began approximately 90 minutes ago according to his wife who witnessed the onset. He is currently alert and oriented but frustrated by his inability to speak clearly. Initial vital signs are: blood pressure 185/105 mmHg, heart rate 110 beats per minute (irregular), respiratory rate 18 breaths per minute, oxygen saturation 96% on room air, and temperature 37.0°C (98.6°F). CT scan of head shows no hemorrhage. His medications at home include warfarin 5 mg daily, and today\'s INR is 2.3. The stroke team has determined he is a candidate for thrombolytic therapy. What is your priority nursing responsibility before and during administration of tissue plasminogen activator (tPA)?',
                'choices' => [
                    'Conduct comprehensive baseline neurological assessment using NIH Stroke Scale and document thoroughly, ensure informed consent is obtained, verify INR is within acceptable range for tPA administration, confirm CT shows no hemorrhage and all inclusion/exclusion criteria are met, establish two large-bore IV lines (one dedicated for tPA), maintain blood pressure below 185/110 mmHg before starting tPA and below 180/105 mmHg during and after infusion using antihypertensive medications as ordered, monitor neurological status every 15 minutes during infusion and for first 6 hours after, observe for signs of bleeding complications including intracranial hemorrhage (severe headache, sudden neurological deterioration, seizures), avoid all invasive procedures, arterial punctures, and IM injections for 24 hours, and be prepared to stop infusion immediately and notify physician if complications occur',
                    'Administer tPA as quickly as possible without completing full assessment or obtaining baseline vital signs since time is critical, give all of the patient\'s regular medications including warfarin and aspirin along with the tPA, allow blood pressure to remain elevated since this is protective for brain perfusion, and perform frequent phlebotomy to monitor coagulation studies',
                    'Wait until INR drops below 1.0 before administering tPA even if this means delaying treatment for several hours, keep blood pressure as low as possible (target systolic <120 mmHg) throughout treatment, start patient on full diet and all oral medications immediately, and plan for early aggressive physical therapy within 2 hours of tPA administration',
                    'Give tPA by intramuscular injection rather than IV infusion for faster absorption, encourage the patient to ambulate immediately after treatment to promote circulation, apply cold compresses to the affected side to reduce swelling, and restrict all visitors during the treatment period',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are caring for a 5-year-old child in the pediatric intensive care unit who was admitted 12 hours ago with severe dehydration and diabetic ketoacidosis (new-onset type 1 diabetes mellitus). Treatment has included aggressive IV fluid resuscitation with normal saline followed by 0.45% NaCl, continuous insulin infusion, and electrolyte replacement. Initial glucose was 625 mg/dL and has now decreased to 185 mg/dL. Initial pH was 7.15 and bicarbonate was 8 mEq/L. The child was initially lethargic but became more alert over the past few hours. Now, approximately 8 hours into treatment, you notice the child has a sudden change in mental status, appears increasingly confused and disoriented, begins complaining of severe headache, and demonstrates decreased level of consciousness. Blood pressure has increased from 95/60 to 125/85 mmHg, heart rate has decreased from 125 to 75 beats per minute. What is your priority concern and action?',
                'choices' => [
                    'Recognize potential cerebral edema, a life-threatening complication of DKA treatment (particularly in children) that can occur during therapy despite appropriate management, classically presenting with headache, altered mental status, declining level of consciousness, and signs of increased intracranial pressure (hypertension, bradycardia), immediately notify physician and activate emergency protocols, elevate head of bed to 30 degrees, prepare to administer hypertonic saline (3% NaCl) or mannitol as ordered to reduce cerebral edema, reduce IV fluid rate, continue insulin infusion, prepare for possible intubation and mechanical ventilation, consider transfer to higher level of care or neurosurgical consultation, perform frequent neurological assessments, and monitor for further deterioration including posturing, pupillary changes, or respiratory abnormalities',
                    'Assume this is normal improvement in the child\'s condition as glucose normalizes, encourage the parents to talk to the child to keep them awake, increase IV fluid rate to ensure continued hydration, advance diet to regular foods since glucose is improving, and plan for discharge to the regular pediatric floor within the next few hours',
                    'Stop all insulin immediately since the blood glucose has decreased adequately, give concentrated dextrose solution rapidly IV to increase blood glucose back above 300 mg/dL, administer a bolus of normal saline at 20 mL/kg over 30 minutes, and lower the head of bed to flat position',
                    'Interpret these findings as hypoglycemia requiring immediate treatment with orange juice or glucagon, discontinue all IV fluids, remove all monitoring devices to allow the child to rest comfortably, and reassure parents that fluctuating mental status is expected during recovery from DKA',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'A 52-year-old male patient is admitted to your medical-surgical unit following an uncomplicated laparoscopic cholecystectomy performed earlier today as an outpatient procedure, but he developed persistent nausea and vomiting in the PACU requiring admission. He has a history of alcohol use disorder (reports drinking 6-8 beers daily for the past 20 years but has been NPO for the surgery). Current medications include ondansetron for nausea and hydromorphone PCA for pain. At 11:00 PM (approximately 12 hours post-op and 36 hours since last alcohol consumption), you respond to his call light and find him agitated, tremulous, diaphoretic, stating "there are bugs crawling all over me" and attempting to climb out of bed. Vital signs show: blood pressure 170/100 mmHg, heart rate 125 beats per minute, temperature 38.2°C (100.7°F), respiratory rate 24 breaths per minute. He is disoriented to time and place. What is your priority nursing intervention?',
                'choices' => [
                    'Recognize signs and symptoms of acute alcohol withdrawal (specifically delirium tremens): altered sensorium with confusion, visual hallucinations, severe agitation, tremors, autonomic hyperactivity (tachycardia, hypertension, diaphoresis, fever), typically occurring 48-96 hours after last drink but can begin earlier, implement immediate safety measures including bed in low position, side rails up and padded if available, constant observation to prevent fall or injury, notify physician immediately as this is a medical emergency requiring urgent intervention, prepare to administer benzodiazepines per CIWA protocol or as ordered (such as lorazepam or diazepam) to prevent seizures and manage symptoms, IV fluids, thiamine, and multivitamins, continuous monitoring of vital signs and mental status, maintain calm quiet environment, reorient frequently, and prepare for possible transfer to ICU if symptoms escalate',
                    'Assume the patient is having a severe allergic reaction to one of his medications, immediately discontinue all medications including the PCA pump, administer diphenhydramine and epinephrine, call for rapid response team, and prepare for possible anaphylaxis protocol',
                    'Interpret this as emergence delirium from anesthesia, reassure the patient that this is normal, encourage him to go back to sleep, turn off the lights, give him warm milk to drink, and check on him again in a few hours when the anesthesia has fully worn off',
                    'Document that the patient is "drug-seeking" and demonstrating manipulative behavior to obtain more pain medication, remove the PCA pump, refuse to administer any further opioids, place the patient on psychiatric hold, and call security to restrain the patient',
                ],
                'correct' => 0,
            ],
            [
                'text' => 'You are working in a community health clinic when a 24-year-old pregnant woman at approximately 32 weeks gestation (gravida 2, para 1) arrives for a routine prenatal visit. During assessment, she appears anxious and avoids eye contact. You notice she is wearing long sleeves and pants despite warm weather. When checking blood pressure, you observe multiple bruises in various stages of healing on her upper arms in a pattern consistent with grab marks. She has a healing laceration above her right eyebrow. When you gently inquire about the injuries, she becomes tearful and states "I\'m so clumsy, I fell down the stairs last week." Her partner, who insisted on accompanying her into the exam room, immediately interjects "yes, she needs to be more careful" and answers most questions directed at the patient. You also note on the chart this is her third visit to the clinic in two months for various minor injuries. What is your most appropriate nursing intervention?',
                'choices' => [
                    'Recognize indicators of potential intimate partner violence including pattern of injuries, implausible explanations, partner\'s controlling behavior and presence during examination, repeated visits for injuries, victim\'s anxiety and avoidance, privately and sensitively create opportunity to interview the patient alone using a legitimate reason to separate from partner (such as needing urine specimen, conducting portion of exam that requires privacy), conduct screening for intimate partner violence using validated tool in private setting, provide emotional support in non-judgmental manner, ensure immediate safety, assess for escalation and imminent danger, provide information about local domestic violence resources and safety planning, document objectively using body map and photographs per facility protocol, follow mandatory reporting requirements for your jurisdiction, offer resources for shelter and counseling, develop follow-up plan, and never confront suspected abuser or place victim at greater risk',
                    'Accept the patient\'s explanation of falling down stairs since you have no proof of abuse, document the injuries as accidental, provide education about fall prevention during pregnancy, and schedule routine follow-up appointment without any further assessment or intervention regarding the injuries',
                    'Confront the partner directly in front of the patient asking "are you abusing her?", call hospital security to have the partner removed from the building, insist the patient immediately leave the relationship and go to a shelter today, and threaten to call child protective services if she refuses',
                    'Assume this is a private family matter that is none of your business, avoid asking any questions about the injuries to prevent embarrassing the patient, focus only on the prenatal assessment and fetal well-being, and avoid documenting the injuries or your concerns in the medical record',
                ],
                'correct' => 0,
            ],
        ];

        $this->createQuestionsFromArray($exam, $questions);
    }

    private function createQuestionsFromArray(\App\Models\Institution\InstitutionAssessment $exam, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            $question = \App\Models\Institution\InstitutionQuestion::create([
                'assessment_id' => $exam->id,
                'question_type' => 'multiple_choice',
                'question_text' => $questionData['text'],
                'points' => 1,
                'order' => $index + 1,
            ]);

            foreach ($questionData['choices'] as $choiceIndex => $choiceText) {
                \App\Models\Institution\InstitutionQuestionChoice::create([
                    'question_id' => $question->id,
                    'choice_text' => $choiceText,
                    'is_correct' => $choiceIndex === $questionData['correct'],
                    'order' => $choiceIndex + 1,
                ]);
            }
        }
    }
}
