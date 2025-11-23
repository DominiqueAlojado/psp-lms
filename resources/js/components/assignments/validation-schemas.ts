import { z } from 'zod';

export const assignmentSchema = z
    .object({
        title: z.string().min(1, 'Title is required'),
        description: z.string().min(1, 'Description is required'),
        instructions: z.string().min(1, 'Instructions are required'),
        assignment_type: z.string().min(1, 'Assignment type is required'),
        target_year_levels: z
            .array(z.string())
            .min(1, 'At least one year level must be selected'),
        max_score: z
            .number({ invalid_type_error: 'Max score must be a number' })
            .min(1, 'Max score must be at least 1'),
        cme_credits: z
            .number({ invalid_type_error: 'CME credits must be a number' })
            .min(0, 'CME credits cannot be negative')
            .max(100, 'CME credits cannot exceed 100')
            .nullable()
            .optional(),
        credit_type: z
            .enum(['cme', 'cpd'])
            .nullable()
            .optional(),
        due_date: z.string().min(1, 'Due date is required'),
        allow_late_submission: z.boolean(),
        late_submission_until: z.string(),
        late_penalty_percent: z
            .number({ invalid_type_error: 'Penalty must be a number' })
            .min(0, 'Penalty cannot be negative')
            .max(100, 'Penalty cannot exceed 100%'),
        allow_resubmission: z.boolean(),
        max_submissions: z
            .number({ invalid_type_error: 'Max submissions must be a number' })
            .min(1, 'Must allow at least 1 submission')
            .max(10, 'Cannot exceed 10 submissions'),
        allowed_file_types: z
            .array(z.string())
            .min(1, 'At least one file type must be selected'),
        max_file_size_mb: z
            .number({ invalid_type_error: 'File size must be a number' })
            .min(1, 'File size must be at least 1 MB')
            .max(100, 'File size cannot exceed 100 MB'),
        max_files: z
            .number({ invalid_type_error: 'Max files must be a number' })
            .min(1, 'Must allow at least 1 file')
            .max(20, 'Cannot exceed 20 files'),
        is_published: z.boolean(),
    })
    .refine(
        (data) => {
            if (data.allow_late_submission) {
                return data.late_submission_until.length > 0;
            }
            return true;
        },
        {
            message: 'Late submission deadline is required when allowing late submissions',
            path: ['late_submission_until'],
        },
    );

export type AssignmentFormData = z.infer<typeof assignmentSchema>;

export const ASSIGNMENT_TYPES = [
    { value: 'case_report', label: 'Case Report' },
    { value: 'procedure_log', label: 'Procedure Log' },
    { value: 'journal_review', label: 'Journal Review' },
    { value: 'presentation', label: 'Presentation' },
    { value: 'research_paper', label: 'Research Paper' },
    { value: 'reflection', label: 'Reflection' },
    { value: 'other', label: 'Other' },
];

export const YEAR_LEVELS = [
    'Pre-Resident',
    'First Year',
    'Second Year',
    'Third Year',
    'Fourth Year',
    'Graduate',
];

export const FILE_TYPES = [
    { value: 'pdf', label: 'PDF (.pdf)' },
    { value: 'doc', label: 'Word (.doc)' },
    { value: 'docx', label: 'Word (.docx)' },
    { value: 'ppt', label: 'PowerPoint (.ppt)' },
    { value: 'pptx', label: 'PowerPoint (.pptx)' },
    { value: 'jpg', label: 'Image (.jpg)' },
    { value: 'jpeg', label: 'Image (.jpeg)' },
    { value: 'png', label: 'Image (.png)' },
];

