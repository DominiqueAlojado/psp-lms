import { z } from 'zod';

const trainingOfficerSchema = z.object({
    name: z.string().optional().or(z.literal('')),
    email: z.string().email('Invalid email address').optional().or(z.literal('')),
});

export const institutionSchema = z.object({
    name: z.string().min(1, 'Institution name is required').max(255),
    description: z.string().optional().or(z.literal('')),
    type: z.enum(['chapter', 'institution', 'national'], {
        errorMap: () => ({ message: 'Please select a valid institution type' }),
    }),
    is_active: z.boolean().default(true),
    training_officers: z.array(trainingOfficerSchema).optional(),
});

export type InstitutionFormData = z.infer<typeof institutionSchema>;
