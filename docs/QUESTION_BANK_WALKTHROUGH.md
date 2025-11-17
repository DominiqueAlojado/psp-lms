# Question Bank Filtering Walkthrough

## Overview
The question bank automatically shows different questions based on which organization the user is currently in. This ensures that:
- **National questions** are shown when working with in-service exams
- **Institution questions** are shown when working with institution-specific exams

---

## Step-by-Step Flow

### Step 1: User Switches Organization Context

When a user navigates to a page with `?org=in-service-exams` in the URL:

```
User visits: /question-bank?org=in-service-exams
```

The middleware (`SetOrganizationFromUrl`) detects this and:
1. Finds the organization with slug `in-service-exams`
2. Checks if user belongs to that organization
3. Switches user's `current_organization_id` to that organization
4. The organization has `type = 'national'`

**Result**: User's `currentOrganization` is now the "In-Service Exams" organization (type = 'national')

---

### Step 2: Question Bank Controller Checks Organization Type

When the user visits `/question-bank`, the controller runs:

```php
// Line 24: Get user's current organization
$currentOrganization = $user->currentOrganization;
// Example: Organization { id: 2, name: "In-Service Exams", type: "national" }

// Line 29: Check the organization type
$isNational = $currentOrganization?->type === 'national';
// Result: true (because type === 'national')
```

---

### Step 3: Query is Built Based on Type

#### Scenario A: National Organization (In-Service Exams)

```php
if ($isNational) {
    // Line 36: Query for national questions
    $query->national();
    // This translates to: WHERE owner_type = 'national' AND organization_id IS NULL
}
```

**What gets shown:**
- Questions where `owner_type = 'national'`
- Questions where `organization_id = null`
- These are the 100 questions from `InServiceQuestionBankSeeder`

#### Scenario B: Institution Organization (e.g., Bataan General Hospital)

```php
else {
    // Line 39: Query for institution questions
    $query->institution()->forOrganization($organizationId);
    // This translates to: 
    // WHERE owner_type = 'institution' 
    // AND organization_id = 123 (current org's ID)
}
```

**What gets shown:**
- Questions where `owner_type = 'institution'`
- Questions where `organization_id = 123` (Bataan General Hospital's ID)
- Only questions created for that specific institution

---

## Visual Example

### Database Structure

```
question_bank table:
┌────┬──────────────────┬──────────────┬──────────────┐
│ id │ organization_id  │ owner_type   │ question_text│
├────┼──────────────────┼──────────────┼──────────────┤
│ 1  │ NULL            │ national     │ "What is..." │ ← National question
│ 2  │ NULL            │ national     │ "Which..."  │ ← National question
│ 3  │ 123             │ institution  │ "A patient..."│ ← BGH question
│ 4  │ 123             │ institution  │ "The best..." │ ← BGH question
│ 5  │ 456             │ institution  │ "In this..."  │ ← PGH question
└────┴──────────────────┴──────────────┴──────────────┘
```

### When User is in "In-Service Exams" Organization

```
Current Organization: { id: 2, type: 'national' }
Query: WHERE owner_type = 'national' AND organization_id IS NULL

Results: Questions 1, 2 ✅
```

### When User is in "Bataan General Hospital" Organization

```
Current Organization: { id: 123, type: 'institution' }
Query: WHERE owner_type = 'institution' AND organization_id = 123

Results: Questions 3, 4 ✅
```

---

## Key Points

1. **No Hardcoded Slugs**: We don't check for `org=in-service-exams` anymore. Instead, we check the organization's `type` field.

2. **Automatic Detection**: The system automatically knows which questions to show based on the user's current organization context.

3. **Access Control**: 
   - National questions can only be edited/deleted when in national organization
   - Institution questions can only be edited/deleted by users from that institution

4. **Question Creation**: When creating a question:
   - In national org → creates `owner_type = 'national'`, `organization_id = null`
   - In institution org → creates `owner_type = 'institution'`, `organization_id = current_org_id`

---

## Testing the Flow

1. **Switch to National Organization**:
   ```
   Visit: /question-bank?org=in-service-exams
   → Should see 100 national questions
   ```

2. **Switch to Institution Organization**:
   ```
   Visit: /question-bank?org=bataan-general-hospital
   → Should see only BGH's institution questions
   ```

3. **Create Question in National Context**:
   ```
   In national org → Create question
   → Question saved with owner_type='national', organization_id=null
   ```

4. **Create Question in Institution Context**:
   ```
   In BGH org → Create question
   → Question saved with owner_type='institution', organization_id=123
   ```

