# CME/CPD Credits System Documentation

## Overview

The Continuing Medical Education (CME) and Continuing Professional Development (CPD) credits system allows medical residents to earn and track credits through various educational activities. This system is designed to comply with Philippine Medical Association (PMA) and Professional Regulation Commission (PRC) standards for continuing medical education.

## Table of Contents

1. [System Configuration](#system-configuration)
2. [Credit Sources](#credit-sources)
3. [Credit Awarding Rules](#credit-awarding-rules)
4. [Implementation Details](#implementation-details)
5. [Usage Guide](#usage-guide)
6. [Philippine Medical Society Standards](#philippine-medical-society-standards)

---

## System Configuration

### Enable/Disable CME/CPD System

The CME/CPD system can be enabled or disabled system-wide via configuration:

**Environment Variable:**
```env
CME_CPD_ENABLED=true   # Enable CME/CPD tracking
CME_CPD_ENABLED=false  # Disable CME/CPD tracking
```

**Config File:** `config/cme_cpd.php`
```php
'enabled' => env('CME_CPD_ENABLED', true), // Default: enabled
```

**Default Behavior:** If not configured, the system defaults to **enabled**.

### Minimum Attendance Percentage

For event attendance, configure the minimum percentage required to earn credits:

**Environment Variable:**
```env
CME_CPD_MIN_ATTENDANCE=0.5  # 50% (default)
```

**Config File:** `config/cme_cpd.php`
```php
'minimum_attendance_percentage' => env('CME_CPD_MIN_ATTENDANCE', 0.5),
```

---

## Credit Sources

The system tracks credits from three main sources:

### 1. Event Attendance

**Source Type:** `event`

**How Credits are Awarded:**
- Event must have `cme_credits` field set (e.g., 1.5, 2.0)
- User must attend at least 50% of event duration (configurable)
- Attendance is tracked via check-in/check-out (`MeetingAttendance`)
- Credits awarded automatically when attendance is finalized

**Example:**
- Event: "Pathology Conference 2024" with 2.0 CME credits
- User attends for 3 hours of a 4-hour event (75% attendance)
- Result: 2.0 CME credits awarded

**Implementation:**
- `MeetingAttendanceObserver` automatically awards credits
- Uses `CmeCreditService::awardCreditsForEventAttendance()`

---

### 2. Exam Completion

**Source Types:** 
- `exam_institution` - Institution-specific exams
- `exam_national` - National in-service exams

**How Credits are Awarded:**
- Exam must have `cme_credits` field set
- User must complete the exam (status: `completed` or `graded`)
- Credits awarded automatically when exam is submitted
- One credit per exam attempt (no duplicates)

**Example:**
- Exam: "General Nursing Knowledge Assessment" with 1.5 CME credits
- User completes and submits the exam
- Result: 1.5 CME credits awarded

**Implementation:**
- `ResidentExamController::submit()` awards credits after exam completion
- Uses `CmeCreditService::awardCreditsForInstitutionExam()` or `awardCreditsForNationalExam()`

---

### 3. Assignment Submission

**Source Type:** `assignment_submission`

**How Credits are Awarded:**
- Assignment must have `cme_credits` field set
- Assignment must be graded (status: `graded`)
- Submission must pass minimum threshold (60% default)
- One credit per assignment (first passing grade only)
- Credits awarded automatically when assignment is graded

**Example:**
- Assignment: "Case Report - Acute MI" with 1.0 CME credits
- User submits assignment
- Training officer grades: 85/100 (85% - passes)
- Result: 1.0 CME credits awarded

**Implementation:**
- `AssignmentController::saveGrade()` awards credits after grading
- Uses `CmeCreditService::awardCreditsForAssignment()`

**Assignment Types Eligible for Credits:**
- Case Report
- Journal Review
- Research Paper
- Presentation
- Reflection
- Other (configurable)

**Note:** Procedure Logs typically do not earn CME/CPD credits as they are competency tracking, not educational activities.

---

## Credit Awarding Rules

### General Rules

1. **System-Wide Toggle:** Credits are only awarded if `config('cme_cpd.enabled')` is `true`
2. **One Credit Per Source:** Each source (event, exam, assignment) awards credits only once per user
3. **Approval Required:** Credits require approval (automatically approved when awarded by system)
4. **Status Tracking:** Credits have status: `pending`, `approved`, or `revoked`

### Event Attendance Rules

- Minimum attendance: 50% of event duration (configurable)
- Attendance tracked via `MeetingAttendance` model
- Credits awarded when `left_at` is set or `duration_seconds` is calculated
- Duplicate prevention: Checks for existing credits for same event

### Exam Completion Rules

- Exam must be completed (status: `completed` or `graded`)
- Credits awarded immediately upon exam submission
- Duplicate prevention: Checks for existing credits for same attempt

### Assignment Submission Rules

- Assignment must be graded (status: `graded`)
- Submission must pass (60% minimum score)
- Only first passing grade earns credits (resubmissions don't earn additional credits)
- Duplicate prevention: Checks for existing credits for same assignment

---

## Implementation Details

### Database Schema

**Table: `cme_credits`**
```sql
- id (primary key)
- user_id (foreign key to users)
- organization_id (foreign key to organizations)
- credits (decimal 5,2) - Credit amount (e.g., 1.5, 2.0)
- source_type (string) - 'event', 'exam_institution', 'exam_national', 'assignment_submission'
- source_id (integer) - ID of the event, exam attempt, or assignment
- description (string) - Human-readable description
- status (enum) - 'pending', 'approved', 'revoked'
- earned_at (timestamp) - When credits were earned
- approved_by (foreign key to users) - Who approved the credits
- approved_at (timestamp) - When credits were approved
- created_at, updated_at, deleted_at
```

**Table: `assignments`**
- Added `cme_credits` column (decimal 5,2, nullable)

**Tables: `institution_assessments`, `national_assessments`**
- Added `cme_credits` column (decimal 5,2, nullable)

**Table: `events`**
- Already has `cme_credits` column

### Service Classes

**`CmeCreditService`**
- `awardCreditsForEventAttendance()` - Awards credits for event attendance
- `awardCreditsForInstitutionExam()` - Awards credits for institution exam completion
- `awardCreditsForNationalExam()` - Awards credits for national exam completion
- `awardCreditsForAssignment()` - Awards credits for assignment submission (NEW)
- `getTotalCreditsForUser()` - Gets total credits for a user
- `getCreditHistory()` - Gets paginated credit history

### Controllers

**`CmeCreditController`**
- `index()` - Displays CME/CPD dashboard
- `history()` - Displays credit history with pagination
- `statistics()` - API endpoint for credit statistics

**`AssignmentController`**
- `saveGrade()` - Awards CME credits when assignment is graded (NEW)

**`ResidentExamController`**
- `submit()` - Awards CME credits when exam is completed

**`MeetingAttendanceObserver`**
- `updated()` - Awards CME credits when attendance is finalized

### Frontend Components

**Assignment Forms:**
- `assignment-form-fields.tsx` - Added CME credits input field
- `create-assignment-sheet.tsx` - Includes CME credits in form
- `edit-assignment-sheet.tsx` - Includes CME credits in form

**CME Credits Dashboard:**
- `cme-credits/index.tsx` - Displays total credits, breakdown by source, recent credits
- `cme-credits/history.tsx` - Displays paginated credit history
- Updated to show assignment credits with orange badge

---

## Usage Guide

### For Training Officers / Administrators

#### Creating Assignments with CME Credits

1. Navigate to **Assignments** → **Create Assignment**
2. Fill in assignment details (title, description, instructions, etc.)
3. Set **CME/CPD Credits** (optional field):
   - Enter credit amount (e.g., 1.0, 1.5, 2.0)
   - Leave empty if no credits should be awarded
4. Save assignment

**Recommended Credit Amounts:**
- Case Report: 1.0 - 2.0 credits
- Journal Review: 0.5 - 1.0 credits
- Research Paper: 2.0 - 3.0 credits
- Presentation: 1.0 - 2.0 credits
- Reflection: 0.5 credits
- Procedure Log: 0 credits (not typically CME/CPD eligible)

#### Grading Assignments

1. Navigate to **Assignments** → Select assignment → **View Submissions**
2. Click **Grade** on a submission
3. Enter score and feedback
4. Save grade
5. **Credits are automatically awarded** if:
   - Assignment has `cme_credits` set
   - Submission passes (60% minimum)
   - This is the first passing grade for this assignment

#### Creating Exams with CME Credits

**Note:** Currently, CME credits fields exist in the database but are not yet exposed in the exam creation forms. To add credits to exams:

1. Create exam normally
2. Manually set `cme_credits` in database or wait for form implementation

**Future Implementation:**
- CME credits field will be added to institution exam creation/edit forms
- CME credits field will be added to national exam creation/edit forms

#### Creating Events with CME Credits

1. Navigate to **Events** → **Create Event**
2. Fill in event details
3. Set **CME/CPD Credits** field
4. Save event
5. Credits awarded automatically when residents attend (50% minimum attendance)

---

### For Residents

#### Viewing CME/CPD Credits

1. Navigate to **CME/CPD Credits** in sidebar
2. View dashboard showing:
   - Total credits (all-time)
   - Credits this year
   - Breakdown by source (Events, Exams, Assignments)
   - Recent credits

#### Viewing Credit History

1. Navigate to **CME/CPD Credits** → **View History**
2. See paginated list of all credit transactions
3. Filter by:
   - Search term
   - Source type (Event, Exam, Assignment)
   - Status (Approved, Pending, Revoked)

#### Earning Credits from Assignments

1. Submit assignment via **My Assignments**
2. Wait for training officer to grade
3. If graded with passing score (60%+), credits are automatically awarded
4. View credits in **CME/CPD Credits** dashboard

---

## Philippine Medical Society Standards

### Compliance with PMA/PRC Requirements

The system is designed to comply with Philippine Medical Association (PMA) and Professional Regulation Commission (PRC) standards:

#### Recognized CME/CPD Activities

1. **Conferences, Workshops, Seminars** ✅
   - Implemented via Event Attendance
   - Requires minimum attendance (50% default)

2. **Online CME/CPD Courses** ✅
   - Can be implemented via Events or Exams
   - Tracks completion automatically

3. **Self-Directed Learning** ✅
   - Implemented via Assignment Submissions
   - Case reports, journal reviews, research papers
   - **Note:** PMA allows up to 30% of required credits from self-directed learning

4. **Research and Publications** ✅
   - Implemented via Assignment Submissions (Research Paper type)
   - Requires grading and approval

#### Credit Requirements (Varies by Specialty)

- **Trainee Members:** 15 CME units within 3 years
- **Diplomates:** 60 CME credit units annually
- **Fellows:** 90 CME credit units annually

#### Quality Assurance

- Credits require approval (automatically approved when awarded by system)
- Assignments require passing grade (60% minimum)
- Events require minimum attendance (50% default)
- All credits are tracked with audit trail

---

## Technical Notes

### Credit Awarding Flow

**Event Attendance:**
```
User checks in → MeetingAttendance created
User checks out → MeetingAttendance updated (left_at set)
→ MeetingAttendanceObserver::updated() triggered
→ Checks attendance duration (≥50% of event)
→ CmeCreditService::awardCreditsForEventAttendance()
→ CmeCredit created with status 'approved'
```

**Exam Completion:**
```
User submits exam → ResidentExamController::submit()
→ Exam attempt status set to 'completed'
→ CmeCreditService::awardCreditsForInstitutionExam() or awardCreditsForNationalExam()
→ CmeCredit created with status 'approved'
```

**Assignment Grading:**
```
Training officer grades submission → AssignmentController::saveGrade()
→ Submission status set to 'graded'
→ Checks if submission passes (≥60%)
→ CmeCreditService::awardCreditsForAssignment()
→ CmeCredit created with status 'approved'
```

### Duplicate Prevention

All credit awarding methods check for existing credits:
```php
$existingCredit = CmeCredit::where('user_id', $userId)
    ->where('source_type', $sourceType)
    ->where('source_id', $sourceId)
    ->where('status', 'approved')
    ->first();

if ($existingCredit) {
    return null; // Already awarded
}
```

### Passing Score Calculation

For assignments, passing is determined by:
```php
public function isPassed(): bool
{
    // Default passing is 60%
    return $this->percentage >= 60;
}
```

This can be customized per assignment if needed.

---

## Future Enhancements

1. **Exam Forms Integration:**
   - Add CME credits field to institution exam creation/edit forms
   - Add CME credits field to national exam creation/edit forms

2. **Configurable Passing Threshold:**
   - Allow per-assignment passing score configuration
   - Support different thresholds for different assignment types

3. **Credit Categories:**
   - Track credits by category (e.g., "Self-Directed Learning", "Formal Education")
   - Enforce 30% limit for self-directed learning

4. **Reporting:**
   - Generate CME/CPD compliance reports
   - Export credit history for accreditation

5. **Notifications:**
   - Notify residents when credits are awarded
   - Alert when approaching credit requirements

---

## Troubleshooting

### Credits Not Being Awarded

1. **Check System Configuration:**
   ```php
   config('cme_cpd.enabled') // Should be true
   ```

2. **Check Source Has Credits:**
   - Event: `$event->cme_credits > 0`
   - Exam: `$assessment->cme_credits > 0`
   - Assignment: `$assignment->cme_credits > 0`

3. **Check Requirements Met:**
   - Event: Attendance ≥ 50% of duration
   - Exam: Status is 'completed' or 'graded'
   - Assignment: Status is 'graded' AND score ≥ 60%

4. **Check for Duplicates:**
   - System prevents duplicate credits
   - Check `cme_credits` table for existing records

### Credits Not Displaying

1. **Check User Permissions:**
   - User must have `view-cme-credits` permission
   - Route: `/cme-credits`

2. **Check Credit Status:**
   - Only `approved` credits are displayed
   - Check `cme_credits.status` field

3. **Clear Cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Support

For issues or questions regarding the CME/CPD credits system:

1. Check this documentation
2. Review activity logs for credit awarding
3. Check database `cme_credits` table for records
4. Verify system configuration in `config/cme_cpd.php`

---

**Last Updated:** November 23, 2024
**Version:** 1.0.0

