# Item Analysis Report Documentation

## Overview

The Item Analysis Report is a comprehensive analytical tool that evaluates the quality and effectiveness of individual test questions (items) in exams. This feature helps educators and administrators identify questions that need revision, understand question difficulty, and assess how well questions discriminate between high and low performers.

**Location**: Analytics → Item Analysis

---

## Features

### Core Functionality
- ✅ **Difficulty Index (p-value)** - Measures how easy or difficult each question is
- ✅ **Discrimination Index (D)** - Evaluates how well questions distinguish between high and low performers
- ✅ **Point Biserial Correlation** - Measures the relationship between item performance and total test score
- ✅ **Distractor Analysis** - Analyzes the effectiveness of incorrect answer choices
- ✅ **Item Quality Assessment** - Overall quality rating for each question
- ✅ **Support for Institution and National Exams** - Works with both exam types
- ✅ **Filtering Options** - Filter by exam, organization, and date range
- ✅ **Expandable Item Details** - View full question text and distractor breakdown

---

## Understanding Item Analysis Metrics

### 1. Difficulty Index (p-value)

**What it measures**: The proportion of students who answered the question correctly.

**Range**: 0.000 to 1.000
- **1.000** = All students answered correctly (very easy)
- **0.000** = No students answered correctly (very difficult)

**Interpretation**:
- **0.80 - 1.00** = Very Easy (may be too easy, consider removing)
- **0.60 - 0.79** = Easy (acceptable for basic knowledge checks)
- **0.40 - 0.59** = Moderate (ideal range for most questions)
- **0.20 - 0.39** = Difficult (may need revision)
- **0.00 - 0.19** = Very Difficult (likely needs revision or clarification)

**Best Practice**: Aim for questions in the 0.30-0.70 range for optimal discrimination.

---

### 2. Discrimination Index (D)

**What it measures**: How well a question differentiates between students who scored high on the entire test versus those who scored low.

**Calculation**: Uses the 27% rule
- **High Group**: Top 27% of performers
- **Low Group**: Bottom 27% of performers
- **Formula**: (Proportion correct in high group) - (Proportion correct in low group)

**Range**: -1.000 to +1.000
- **Positive values** = High performers answered correctly more often (good)
- **Negative values** = Low performers answered correctly more often (problematic)
- **Zero** = No discrimination (question doesn't differentiate)

**Interpretation**:
- **≥ 0.40** = Excellent discrimination
- **0.30 - 0.39** = Good discrimination
- **0.20 - 0.29** = Fair discrimination
- **0.10 - 0.19** = Poor discrimination (needs review)
- **< 0.10** = Very poor discrimination (likely needs revision or removal)

**Best Practice**: Questions with discrimination index ≥ 0.20 are generally acceptable. Values ≥ 0.30 are preferred.

---

### 3. Point Biserial Correlation

**What it measures**: The correlation between getting a question correct and the total test score.

**Range**: -1.000 to +1.000
- **Positive values** = Students who got this question right tended to score higher overall (good)
- **Negative values** = Students who got this question right tended to score lower overall (problematic)
- **Near zero** = No relationship (question may not measure what the test measures)

**Interpretation**:
- **≥ 0.30** = Strong positive correlation (excellent)
- **0.20 - 0.29** = Moderate correlation (good)
- **0.10 - 0.19** = Weak correlation (acceptable)
- **< 0.10** = Very weak or negative correlation (needs review)

**Best Practice**: Higher values indicate the question is measuring the same construct as the overall test.

---

### 4. Distractor Analysis

**What it measures**: How many students selected each answer choice (both correct and incorrect options).

**Key Insights**:
- **Correct Answer**: Should be selected by a reasonable percentage of students
- **Effective Distractors**: Incorrect choices that attract some students (especially low performers)
- **Ineffective Distractors**: Choices that no one or very few students select (may need revision)

**Best Practices**:
- Each distractor should be selected by at least 5% of students
- Distractors should be more attractive to low performers than high performers
- If a distractor is never selected, consider revising it to be more plausible

---

### 5. Item Quality Assessment

**What it measures**: Overall quality rating based on difficulty and discrimination metrics.

**Quality Levels**:

1. **Good**
   - Moderate difficulty (0.30 - 0.70)
   - Good discrimination (≥ 0.20)
   - Item is functioning well, no action needed

2. **Acceptable**
   - Either moderate difficulty OR good discrimination
   - Item is acceptable but could be improved

3. **Marginal**
   - Difficulty or discrimination is borderline
   - Consider reviewing for potential improvements

4. **Needs Review**
   - Poor discrimination (< 0.10)
   - Very easy or very difficult
   - Should be revised or removed

---

## How to Use Item Analysis

### Step 1: Access Item Analysis

1. Navigate to **Analytics** in the sidebar
2. Click on **Item Analysis** from the analytics menu
3. You'll see the filter panel

### Step 2: Select an Exam

1. Choose an exam from the **"Select Exam"** dropdown
   - Both institution and national exams are available
   - Exams must be published to appear in the list
2. Optionally filter by:
   - **Organization** (if you have system-wide permissions)
   - **Date From** - Start date for attempts to analyze
   - **Date To** - End date for attempts to analyze
3. Click **"Analyze"** button

### Step 3: Review Results

The report displays:

#### Summary Card
- **Total Attempts**: Number of completed exam attempts analyzed
- **Total Items**: Number of questions in the exam
- **Items Needing Review**: Count of questions flagged for review

#### Item Analysis Table

Each row shows:
- **Item #**: Question order number
- **Question**: Truncated question text (click to expand for full text)
- **Topic**: Topic category (if assigned)
- **Difficulty**: Index value and label
- **Discrimination**: Index value and label
- **Point Biserial**: Correlation coefficient
- **Quality**: Overall quality assessment
- **Correct / Total**: Number of correct answers vs total responses

#### Expandable Details

Click the chevron icon to expand and view:
- **Full Question Text**: Complete question content
- **Distractor Analysis**: Breakdown showing:
  - Each answer choice
  - Number of students who selected it
  - Percentage of total responses
  - Visual indicator for correct answer

---

## Interpreting Results

### Good Questions

**Characteristics**:
- Difficulty Index: 0.30 - 0.70 (Moderate)
- Discrimination Index: ≥ 0.20 (Fair to Excellent)
- Point Biserial: ≥ 0.20 (Positive correlation)
- Quality: "Good" or "Acceptable"

**Action**: No changes needed. These questions are functioning well.

---

### Questions Needing Review

**Red Flags**:
- **Very Easy Questions** (Difficulty > 0.80)
  - May not assess meaningful knowledge
  - Consider removing or making more challenging

- **Very Difficult Questions** (Difficulty < 0.20)
  - May be too complex or poorly worded
  - Consider simplifying or clarifying

- **Poor Discrimination** (Discrimination < 0.10)
  - Question doesn't differentiate between high and low performers
  - May be measuring something different from the test
  - Likely needs revision or removal

- **Negative Discrimination** (Discrimination < 0)
  - Low performers are answering correctly more often
  - Indicates a serious problem (miskeyed answer, confusing wording)
  - Should be reviewed immediately

- **Ineffective Distractors** (Selected by < 5% of students)
  - Distractors are not plausible
  - Consider revising to be more attractive

---

## Best Practices

### When Creating Questions

1. **Aim for Moderate Difficulty**
   - Target difficulty index of 0.40 - 0.60 for most questions
   - Mix in some easier (0.60 - 0.70) and harder (0.30 - 0.40) questions

2. **Write Effective Distractors**
   - Make incorrect choices plausible
   - Base distractors on common misconceptions
   - Avoid obviously wrong answers

3. **Ensure Clear Wording**
   - Ambiguous questions lead to poor discrimination
   - Test questions with colleagues before use

### When Reviewing Results

1. **Prioritize Items Needing Review**
   - Start with questions marked "Needs Review"
   - Check discrimination index first (most important metric)

2. **Look for Patterns**
   - Multiple questions on the same topic with poor discrimination may indicate topic coverage issues
   - Questions with similar difficulty but different discrimination may reveal wording problems

3. **Consider Context**
   - Very easy questions may be appropriate for basic knowledge checks
   - Very difficult questions may be appropriate for advanced assessments
   - Use judgment based on learning objectives

4. **Revise or Remove**
   - Questions with negative discrimination should be removed or completely revised
   - Questions with poor discrimination can often be improved with better wording
   - Ineffective distractors should be replaced with more plausible options

---

## Technical Details

### Calculation Methods

#### Difficulty Index
```
p = (Number of correct responses) / (Total number of responses)
```

#### Discrimination Index (27% Rule)
```
D = (Proportion correct in high group) - (Proportion correct in low group)

Where:
- High group = Top 27% of performers
- Low group = Bottom 27% of performers
```

#### Point Biserial Correlation
```
r_pb = (Covariance of item scores and total scores) / (SD_item × SD_total)

Where:
- SD = Standard Deviation
```

### Data Requirements

- **Minimum Attempts**: For reliable analysis, at least 30 completed attempts are recommended
- **Completed Attempts Only**: Only attempts with status "completed" are included
- **Answer Data**: Requires answers to be saved with `is_correct` flag set

### Supported Question Types

- ✅ Multiple Choice
- ✅ Multiple Select
- ✅ True/False
- ⚠️ Fill in the Blank (basic analysis only)
- ⚠️ Essay Questions (not included in distractor analysis)

---

## Permissions

**Required Permission**: `view-analytics`

Users with this permission can:
- Access the Item Analysis report
- View analysis for exams in their organization
- Filter by organization (if they have `view-all-assessment-reports` permission)

---

## Limitations

1. **Minimum Sample Size**: Analysis is most reliable with 30+ attempts
2. **Question Types**: Distractor analysis works best with multiple choice questions
3. **Time Period**: Only analyzes attempts within the selected date range
4. **Completed Attempts**: Only includes attempts with status "completed"

---

## Troubleshooting

### No Results Displayed

**Possible Causes**:
- No exam selected
- Selected exam has no completed attempts
- Date range filters exclude all attempts
- Organization filter excludes all attempts

**Solution**: Check filters and ensure the exam has completed attempts in the selected date range.

### All Questions Show "Needs Review"

**Possible Causes**:
- Very small sample size (< 10 attempts)
- All students performed similarly (low variance)
- Questions are poorly written

**Solution**: 
- Wait for more attempts to accumulate
- Review question quality and wording
- Check if exam is appropriate for the student level

### Discrimination Index is Zero or Negative

**Possible Causes**:
- Question is miskeyed (correct answer marked as incorrect)
- Question wording is confusing
- Question measures something different from the test

**Solution**: 
- Verify correct answer is properly marked
- Review question wording for clarity
- Consider if question aligns with learning objectives

---

## Related Features

- **Exam Analytics**: Overall exam statistics and performance metrics
- **Topic Performance**: Performance analysis by topic area
- **Assessment Reports**: Individual attempt reports and resident performance

---

## Support

For questions or issues with Item Analysis:
1. Check this documentation
2. Review the exam's question setup
3. Verify attempt data is complete
4. Contact system administrator if problems persist

---

**Last Updated**: 2025-01-XX
**Version**: 1.0

