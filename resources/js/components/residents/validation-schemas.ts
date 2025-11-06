import { z } from 'zod';

// Shared regex for Philippine phone numbers
export const philippinePhoneRegex = /^(\+63|0)?9\d{9}$/;

// Step 1: Personal Information Schema
export const step1Schema = z.object({
    organization_id: z.string().min(1, 'Organization is required'),
    first_name: z.string().min(1, 'First name is required').max(255),
    middle_name: z.string().max(255).optional().or(z.literal('')),
    last_name: z.string().min(1, 'Last name is required').max(255),
    email: z.string().email('Please enter a valid email address'),
    contact_number: z
        .string()
        .min(1, 'Contact number is required')
        .regex(
            philippinePhoneRegex,
            'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789)',
        ),
    course: z.string().min(1, 'Course is required').max(255),
    year_level: z.string().min(1, 'Year level is required'),
    status: z.enum(['active', 'inactive']),
});

// Step 2: Account Schema
export const step2Schema = z
    .object({
        password: z.string().min(8, 'Password must be at least 8 characters'),
        password_confirmation: z
            .string()
            .min(1, 'Please confirm your password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: "Passwords don't match",
        path: ['password_confirmation'],
    });

// Edit: Personal Information Schema (without organization_id)
export const editPersonalSchema = z.object({
    first_name: z.string().min(1, 'First name is required').max(255),
    middle_name: z.string().max(255).optional().or(z.literal('')),
    last_name: z.string().min(1, 'Last name is required').max(255),
    email: z.string().email('Please enter a valid email address'),
    contact_number: z
        .string()
        .min(1, 'Contact number is required')
        .regex(
            philippinePhoneRegex,
            'Contact number must be a valid Philippine mobile number (e.g., 09123456789 or +639123456789)',
        ),
    course: z.string().min(1, 'Course is required').max(255),
    year_level: z.string().min(1, 'Year level is required'),
    status: z.enum(['active', 'inactive']),
});

// Edit: Account Schema (optional password)
export const editAccountSchema = z
    .object({
        password: z.string().optional().or(z.literal('')),
        password_confirmation: z.string().optional().or(z.literal('')),
    })
    .refine(
        (data) => {
            // If password is provided, it must be at least 8 characters
            if (data.password && data.password.length > 0) {
                return data.password.length >= 8;
            }
            return true;
        },
        {
            message: 'Password must be at least 8 characters',
            path: ['password'],
        },
    )
    .refine(
        (data) => {
            // If password is provided, confirmation must match
            if (data.password && data.password.length > 0) {
                return data.password === data.password_confirmation;
            }
            return true;
        },
        {
            message: "Passwords don't match",
            path: ['password_confirmation'],
        },
    );
