import { z } from 'zod';

export const institutionSchema = z.object({
    name: z.string().min(1, 'Institution name is required').max(255),
    description: z.string().optional().or(z.literal('')),
    type: z.enum(['chapter', 'institution', 'main', 'national'], {
        errorMap: () => ({ message: 'Please select a valid institution type' }),
    }),
    is_active: z.boolean().default(true),
});

export type InstitutionFormData = z.infer<typeof institutionSchema>;

