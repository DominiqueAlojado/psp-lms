import { z } from 'zod';

export const staffSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    email: z.string().email('Invalid email address').min(1, 'Email is required'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    roles: z.array(z.number()).min(1, 'At least one role must be selected'),
    organizations: z.array(z.number()).optional(),
    current_organization_id: z.number().nullable().optional(),
});

export const staffEditSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    email: z.string().email('Invalid email address').min(1, 'Email is required'),
    password: z.string().min(8, 'Password must be at least 8 characters').optional().or(z.literal('')),
    roles: z.array(z.number()).min(1, 'At least one role must be selected'),
    organizations: z.array(z.number()).optional(),
    current_organization_id: z.number().nullable().optional(),
});

export type StaffFormData = z.infer<typeof staffSchema>;
export type StaffEditFormData = z.infer<typeof staffEditSchema>;

