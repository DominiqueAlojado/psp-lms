# Exam System Documentation

## Overview

This LMS features a comprehensive exam/assessment system with two distinct categories:
1. **Institution Exams** - Created and managed by individual institutions for their residents
2. **National In-Service Exams** - System-wide exams for national assessments and rankings

## Features

### Core Functionality
- ✅ Multiple question types: Multiple Choice, Multiple Select, True/False
- ✅ Rich text editor with formatting (Bold, Italic, Superscript, Subscript, Lists, Links)
- ✅ Image support for questions (upload and preview)
- ✅ Topic categorization with searchable combobox
- ✅ Create topics on-the-fly during exam creation
- ✅ Individual question saving (save-as-you-go)
- ✅ Collapsible question UI for managing large exams
- ✅ Permission-based access control
- ✅ Edit and delete functionality
- ✅ Toast notifications for user feedback

### Institution Exams Features
- Organization-specific exams
- Course assignment (optional)
- Configurable exam settings:
  - Duration (minutes)
  - Passing score
  - Randomize questions/choices
  - Show results immediately
  - Allow review
  - Publish status
  - Availability dates
- Track exam creator
- Questions with choices (max 4 per question)
- First choice is always correct answer

### National Exams Features
- System-wide exams
- Exam year and period tracking
- National ranking enabled
- Institution comparison
- Scheduled date and results release date
- Difficulty levels for questions
- Comprehensive analytics support

---

## Database Structure

### Tables Overview

#### **Topics System**
```
topics
├── id
├── name
├── slug
├── description (nullable)
├── organization_id (nullable) - for organization-specific topics
├── is_global - true for system-wide topics
└── timestamps
```

#### **Institution Exams**
```
institution_assessments
├── id
├── organization_id
├── course_id (nullable)
├── title
├── description (nullable)
├── duration_minutes (nullable)
├── total_points
├── passing_score
├── randomize_questions
├── randomize_choices
├── show_results_immediately
├── allow_review
├── is_published
├── available_from (nullable)
├── available_until (nullable)
├── created_by
└── timestamps + soft deletes

institution_questions
├── id
├── assessment_id → institution_assessments
├── topic_id → topics (nullable)
├── question_type (enum: multiple_choice, multiple_select, true_false, essay, fill_blank)
├── question_text
├── points
├── explanation (nullable)
├── image_path (nullable)
├── order
└── timestamps + soft deletes

institution_question_choices
├── id
├── question_id → institution_questions
├── choice_text
├── is_correct
├── order
└── timestamps

institution_attempts
├── id
├── assessment_id → institution_assessments
├── user_id → users
├── organization_id → organizations
├── started_at
├── submitted_at (nullable)
├── score (nullable)
├── total_points
├── status (enum: in_progress, submitted, graded)
└── timestamps

institution_answers
├── id
├── attempt_id → institution_attempts
├── question_id → institution_questions
├── answer_data (json)
├── is_correct (nullable)
├── points_earned (nullable)
├── grader_feedback (nullable)
├── graded_by → users (nullable)
├── graded_at (nullable)
└── timestamps
```

#### **National Exams**
```
national_assessments
├── id
├── title
├── description (nullable)
├── exam_year
├── exam_period
├── duration_minutes (nullable)
├── total_points
├── passing_score
├── randomize_questions
├── randomize_choices
├── show_results_immediately
├── allow_review
├── is_published
├── national_ranking_enabled
├── institution_comparison_enabled
├── scheduled_date (nullable)
├── results_release_date (nullable)
├── created_by
└── timestamps + soft deletes

national_questions (similar to institution_questions, plus:)
├── difficulty_level (nullable)
└── topic (text field)

national_question_choices (similar to institution_question_choices)

national_attempts (similar to institution_attempts, plus:)
├── national_rank (nullable)
├── institution_rank (nullable)
└── percentile (nullable)

national_answers (similar to institution_answers)
```

---

## Routes

### Institution Exams
```php
GET    /institution-exams/active              - List active exams
GET    /institution-exams/create              - Create exam form
GET    /institution-exams/{id}/edit           - Edit exam form
POST   /assessments                           - Store new exam
PATCH  /assessments/{id}                      - Update exam
DELETE /assessments/{id}                      - Delete exam
POST   /assessments/{id}/questions/save-one   - Save individual question
DELETE /assessments/{id}/questions/{qid}      - Delete individual question
```

### National Exams
```php
GET    /inservice-exams/active                - List active national exams
GET    /in-service/create                     - Create national exam
POST   /in-service                            - Store national exam
PATCH  /in-service/{id}                       - Update national exam
DELETE /in-service/{id}                       - Delete national exam
```

### Topics
```php
GET    /topics                                - Fetch all available topics
POST   /topics                                - Create new topic
```

---

## Permissions

### Required Permissions for Institution Exams
- `view-assessments` - View exam lists
- `create-assessments` - Create new exams
- `edit-assessments` - Edit existing exams and add/edit questions
- `delete-assessments` - Delete exams

### Required Roles for National Exams
- `System Admin` or `BOP` - Full CRUD access

---

## Components

### Reusable Components

#### `<TopicSelector />`
Searchable combobox for topic selection with on-the-fly topic creation.

**Props:**
```typescript
interface TopicSelectorProps {
    value?: number | null;           // Current topic ID
    onChange: (topicId: number | null) => void;  // Callback
    label?: string;                  // Default: "Topic (Optional)"
    placeholder?: string;            // Default: "Select topic..."
    className?: string;              // Additional CSS classes
}
```

**Usage:**
```typescript
<TopicSelector
    value={topicId}
    onChange={(newTopicId) => setTopicId(newTopicId)}
/>
```

**Features:**
- Searchable dropdown with all available topics
- Shows global topics and organization-specific topics
- "+" button to create new topics inline
- Auto-selects newly created topic
- Checkmark indicates selected topic

#### `<RichTextEditor />`
Rich text editor with medical/scientific formatting support.

**Props:**
```typescript
interface RichTextEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}
```

**Features:**
- Bold, Italic formatting
- Superscript (x²) for exponents
- Subscript (x₂) for chemical formulas
- Bullet and ordered lists
- Links
- Undo/Redo

---

## Usage Guide

### Creating an Institution Exam

#### Step 1: Create Exam Metadata
1. Navigate to **Institution Exams** → **Create Exam**
2. Fill in exam details:
   - Title (required)
   - Passing Score (required)
   - Duration in minutes (optional)
3. Click **"Create Exam"**
4. You'll be redirected to Step 2 (Question Builder)

#### Step 2: Add Questions
1. Click one of the question type buttons:
   - **Add Multiple Choice** - 4 choices, first is correct
   - **Add Multiple Select** - 4 choices, multiple can be correct
   - **Add True/False** - Two options
2. Expand the question by clicking on it
3. Fill in question details:
   - **Topic** - Select from dropdown or create new
   - **Question Text** - Use rich text editor
   - **Image** (Optional) - Upload histology/medical images
   - **Points** - Question score value
   - **Choices** - Enter 1-4 choices (first is correct by default)
4. Click the **💾 Save icon** to save the question
5. **"✓ Saved"** badge appears when saved
6. Repeat for all questions
7. Use **"Expand All"** / **"Collapse All"** for managing many questions

### Editing an Exam

1. Navigate to **Institution Exams** → **Active**
2. Click the **pencil icon** on an exam
3. Update exam metadata in the top section
4. Click **"Update Exam Details"**
5. Add/edit questions below
6. Save each question individually with the 💾 icon
7. Delete questions with the 🗑️ icon

### Creating Topics

#### Method 1: During Exam Creation
1. When adding a question, click the **"+"** button next to the topic dropdown
2. Enter topic name
3. Press **"Add"** or hit **Enter**
4. Topic is created and auto-selected

#### Method 2: Global Topics (Admin)
- 37 medical topics are pre-seeded:
  - Basic Sciences: Anatomy, Physiology, Biochemistry, Pathology, Histology, etc.
  - Body Systems: Cardiovascular, Respiratory, Gastrointestinal, etc.
  - Clinical Specialties: Internal Medicine, Surgery, Pediatrics, etc.

---

## Image Handling

### Uploading Images
1. When creating/editing a question, use the **"Question Image (Optional)"** section
2. Click the file input and select an image
3. Preview appears with a remove button (X)
4. Images are converted to base64 and sent to server
5. Server stores in `storage/app/public/question-images/`
6. Images display at max height of 384px with aspect ratio maintained

### Supported Formats
- JPEG/JPG
- PNG
- GIF
- WebP

### Image Display
- Responsive flexbox layout
- Max width: 2xl (672px)
- Max height: 96 (384px)
- Object-fit: contain (maintains aspect ratio)
- Remove button overlaid in top-right corner

---

## Question Types

### Multiple Choice
- 4 choices maximum
- First choice is the correct answer
- Single selection by user
- Auto-gradable

### Multiple Select
- 4 choices maximum
- Multiple choices can be correct
- Checkbox selection by user
- Auto-gradable

### True/False
- Two choices: True and False
- Radio button selection
- Auto-gradable

### Future Question Types (Database Ready)
- Essay/Short Answer - Manual grading required
- Fill in the Blank - Auto-gradable with exact match
- Matching - Pair items together
- Ordering - Arrange items in sequence
- Image-based - Click regions on images

---

## Technical Implementation

### Frontend Stack
- **React 19** - UI library
- **Inertia.js v2** - Server-driven SPA
- **Tailwind CSS v4** - Styling
- **Shadcn UI** - Component library
- **Tiptap** - Rich text editor
- **Sonner** - Toast notifications
- **Lucide React** - Icons

### Backend Stack
- **Laravel 12** - PHP framework
- **PostgreSQL** - Database
- **Laravel Fortify** - Authentication
- **Spatie Permissions** - Role-based access control
- **Laravel Storage** - File handling

### Key Features Implementation

#### Collapsible Questions
```typescript
<Collapsible open={open} onOpenChange={setOpen}>
    <CollapsibleTrigger>
        Question #1 (multiple choice) ✓ Saved
    </CollapsibleTrigger>
    <CollapsibleContent>
        {/* Question form fields */}
    </CollapsibleContent>
</Collapsible>
```

#### Individual Question Saving
- Each question has its own save button
- POST to `/assessments/{id}/questions/save-one`
- Returns saved question with ID
- Updates local state with saved ID
- Shows "✓ Saved" badge

#### Topic Selector Component
- Fetches topics on mount
- Searches/filters in real-time
- Creates new topics via POST `/topics`
- Auto-selects newly created topics
- Fully self-contained state management

---

## Permissions & Security

### Authorization
- All exam routes protected with Spatie permissions
- Organization-level isolation (users only see their org's exams)
- Middleware validates organization access
- Role-based access for national exams

### Middleware Skip List
The following routes skip organization switching middleware:
- `/assessments*` - All methods
- `/institution-exams*` - All methods  
- `/in-service*` - All methods
- `/topics*` - POST/PATCH/PUT/DELETE

### CSRF Protection
- All POST/PATCH/DELETE requests use Inertia router
- CSRF tokens handled automatically
- No manual token management needed

---

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── InstitutionExamController.php
│   │   ├── NationalAssessmentController.php
│   │   └── TopicController.php
│   └── Middleware/
│       ├── SetOrganizationFromUrl.php
│       └── HandleInertiaRequests.php
├── Models/
│   ├── Topic.php
│   ├── Institution/
│   │   ├── InstitutionAssessment.php
│   │   ├── InstitutionQuestion.php
│   │   ├── InstitutionQuestionChoice.php
│   │   ├── InstitutionAttempt.php
│   │   └── InstitutionAnswer.php
│   └── National/
│       ├── NationalAssessment.php
│       ├── NationalQuestion.php
│       ├── NationalQuestionChoice.php
│       ├── NationalAttempt.php
│       └── NationalAnswer.php

database/
├── migrations/
│   ├── 2025_11_05_023706_create_institution_assessments_table.php
│   ├── 2025_11_05_023713_create_institution_questions_table.php
│   ├── 2025_11_05_023714_create_institution_question_choices_table.php
│   ├── 2025_11_05_023714_create_institution_attempts_table.php
│   ├── 2025_11_05_023715_create_institution_answers_table.php
│   ├── 2025_11_05_023720_create_national_assessments_table.php
│   ├── 2025_11_05_023722_create_national_questions_table.php
│   ├── 2025_11_05_023723_create_national_attempts_table.php
│   ├── 2025_11_05_023724_create_national_answers_table.php
│   ├── 2025_11_05_023725_create_national_question_choices_table.php
│   ├── 2025_11_05_230218_create_topics_table.php
│   └── 2025_11_05_230241_add_topic_id_to_questions_tables.php
└── seeders/
    └── TopicSeeder.php (37 medical topics)

resources/
├── js/
│   ├── components/
│   │   ├── topic-selector.tsx (Reusable topic selector)
│   │   ├── rich-text-editor.tsx
│   │   └── ui/
│   │       ├── command.tsx (Search component)
│   │       └── popover.tsx
│   ├── pages/
│   │   ├── institution-exams/
│   │   │   ├── active.tsx
│   │   │   ├── create.tsx
│   │   │   └── edit.tsx
│   │   └── inservice-exams/
│   │       ├── active.tsx
│   │       └── create.tsx
│   ├── layouts/
│   │   └── exams/
│   │       ├── institution-layout.tsx
│   │       └── inservice-layout.tsx
│   └── hooks/
│       └── use-permissions.ts
```

---

## API Endpoints

### Institution Exams

#### List Exams
```http
GET /institution-exams/active
```
**Response:**
```json
{
  "exams": {
    "data": [
      {
        "id": 1,
        "title": "Pathology Midterm",
        "description": "Mid-year assessment",
        "questions_count": 50,
        "total_points": 100,
        "passing_score": 70,
        "duration_minutes": 120,
        "is_published": true,
        "is_available": true,
        "created_by": "Dr. Smith",
        "updated_at": "2 hours ago"
      }
    ],
    "total": 1
  }
}
```

#### Create Exam
```http
POST /assessments
Content-Type: application/json

{
  "title": "Pathology Midterm",
  "description": "Mid-year assessment",
  "passing_score": 70,
  "duration_minutes": 120,
  "randomize_questions": false,
  "randomize_choices": false,
  "show_results_immediately": true,
  "allow_review": true,
  "available_from": "2025-01-01 08:00",
  "available_until": "2025-01-15 17:00"
}
```

#### Save Individual Question
```http
POST /assessments/{id}/questions/save-one
Content-Type: application/json

{
  "id": 1,                           // Optional - for updates
  "topic_id": 5,                     // Optional
  "question_type": "multiple_choice",
  "question_text": "<p>What is...</p>",
  "points": 2,
  "order": 0,
  "image": "data:image/png;base64,...",  // Optional
  "choices": [
    { "choice_text": "Correct answer", "is_correct": true },
    { "choice_text": "Wrong answer 1", "is_correct": false },
    { "choice_text": "Wrong answer 2", "is_correct": false },
    { "choice_text": "Wrong answer 3", "is_correct": false }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Question saved successfully",
  "question": {
    "id": 123,
    "topic_id": 5,
    "question_type": "multiple_choice",
    "question_text": "<p>What is...</p>",
    "points": 2,
    "order": 0,
    "image_path": "question-images/question_123.png",
    "image_url": "http://localhost/storage/question-images/question_123.png"
  }
}
```

#### Delete Question
```http
DELETE /assessments/{id}/questions/{question_id}
```

### Topics

#### Get All Topics
```http
GET /topics
```
**Response:**
```json
[
  {
    "id": 1,
    "name": "Pathology",
    "slug": "pathology",
    "is_global": true
  },
  {
    "id": 2,
    "name": "Anatomy",
    "slug": "anatomy",
    "is_global": true
  }
]
```

#### Create Topic
```http
POST /topics
Content-Type: application/json

{
  "name": "Cellular Adaptation",
  "description": "Study of cellular changes"
}
```

---

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Topics (Optional)
```bash
php artisan db:seed --class=TopicSeeder
```

This creates 37 global medical topics:
- Basic Sciences (8 topics)
- Body Systems (8 topics)
- Clinical Specialties (14 topics)
- Additional Topics (7 topics)

### 3. Set Permissions
Ensure your roles have the appropriate permissions:
```php
// For institutional users
$role->givePermissionTo([
    'view-assessments',
    'create-assessments',
    'edit-assessments',
    'delete-assessments',
]);

// For national exam admins
$user->assignRole('System Admin'); // or 'BOP'
```

### 4. Storage Link
Ensure storage is linked for image access:
```bash
php artisan storage:link
```

### 5. Install NPM Dependencies
```bash
npm install
npm run build
# or for development
npm run dev
```

---

## User Workflow Examples

### Example 1: Creating a Pathology Exam with Images

1. **Create Exam**
   - Title: "Cellular Pathology Exam"
   - Passing Score: 70
   - Duration: 60 minutes

2. **Add Question 1**
   - Type: Multiple Choice
   - Topic: Select "Pathology" from dropdown
   - Question: "What type of cellular adaptation is shown in this image..."
   - Upload histological image (normal vs. metaplasia)
   - Points: 2
   - Choices:
     - Choice 1 (Correct): "Squamous metaplasia"
     - Choice 2: "Hyperplasia"
     - Choice 3: "Dysplasia"
     - Choice 4: "Atrophy"
   - Click 💾 Save

3. **Add More Questions**
   - Click "Add Multiple Choice" again
   - Repeat process
   - Each question saves individually

4. **Navigate Away Safely**
   - All saved questions (with ✓ badge) are in the database
   - Can return later to add more

### Example 2: Creating a Custom Topic

1. While adding a question, click **"+"** next to topic dropdown
2. Type: "Epithelial Tissues"
3. Press **Enter** or click **"Add"**
4. Toast notification: "Topic 'Epithelial Tissues' created and selected!"
5. Topic is now selected for that question
6. Available in dropdown for future questions

---

## Best Practices

### Exam Creation
- ✅ Use descriptive exam titles
- ✅ Set appropriate passing scores
- ✅ Add topics to questions for better organization
- ✅ Save questions frequently (individual save)
- ✅ Use images for visual/clinical questions
- ✅ Review all questions before publishing

### Question Writing
- ✅ Make first choice the correct answer
- ✅ Write clear, unambiguous questions
- ✅ Use rich text formatting for clarity (super/subscript for formulas)
- ✅ Add relevant medical images
- ✅ Assign appropriate topics
- ✅ Set point values based on difficulty

### Topic Management
- ✅ Use global topics when available
- ✅ Create organization-specific topics as needed
- ✅ Use consistent naming conventions
- ✅ Search topics instead of scrolling

---

## Troubleshooting

### Issue: 419 Page Expired Error
**Solution:** All API calls now use Inertia's `router.post()` which handles CSRF automatically.

### Issue: Topics Not Appearing in Dropdown
**Solution:** 
1. Check if TopicSeeder was run: `php artisan db:seed --class=TopicSeeder`
2. Verify topics exist: Check database `topics` table
3. Check browser console for fetch errors

### Issue: Images Not Displaying
**Solution:**
1. Run `php artisan storage:link`
2. Check `storage/app/public/question-images/` directory exists
3. Verify file permissions

### Issue: Can't Select Topics
**Solution:** Topics now use regular divs with onClick handlers instead of CommandItem. If still not working, check browser console for JavaScript errors.

### Issue: Questions Duplicating
**Solution:** Fixed - questions now update instead of insert when they have an ID.

### Issue: Can't See Topics When Searching
**Solution:** Search filtering is now manual with `.filter()` - should work reliably.

---

## Future Enhancements

### Planned Features
- [ ] Question bank / question library
- [ ] Import/export questions
- [ ] Question templates
- [ ] Bulk question upload (CSV/Excel)
- [ ] Analytics dashboard
- [ ] Exam attempts and grading interface
- [ ] Results and reporting
- [ ] National exam rankings
- [ ] Institution comparisons
- [ ] Question difficulty analysis
- [ ] Topic-based performance tracking

### Additional Question Types
- [ ] Essay/Short Answer with manual grading
- [ ] Matching questions
- [ ] Ordering/Sequencing questions
- [ ] Hotspot (image click) questions
- [ ] Drag and drop questions

---

## Support

For issues or questions about the exam system:
1. Check this documentation first
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check browser console for frontend errors
4. Verify permissions are set correctly
5. Ensure migrations have run successfully

---

## Changelog

### Version 1.0 - Initial Release
- ✅ Institution and National exam separation
- ✅ Topics system with global and org-specific topics
- ✅ Rich text editor with medical formatting
- ✅ Image upload for questions
- ✅ Individual question saving
- ✅ Collapsible UI for large exams
- ✅ Searchable topic combobox
- ✅ Permission-based access control
- ✅ Toast notifications
- ✅ Edit and delete functionality
- ✅ Reusable TopicSelector component
- ✅ Auto-save question IDs
- ✅ Total points auto-calculation

---

## Credits

Built with Laravel 12, Inertia.js v2, React 19, and Tailwind CSS v4.

