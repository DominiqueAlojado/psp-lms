import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Preserves the org query parameter from the current URL or from the current organization.
 * This ensures that navigation links maintain the organization context.
 */
export function preserveOrgParam(
    href: NonNullable<InertiaLinkProps['href']>,
    currentOrgSlug?: string | null,
): NonNullable<InertiaLinkProps['href']> {
    // Get org from URL if not provided
    let orgSlug = currentOrgSlug;
    
    if (!orgSlug && typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        orgSlug = urlParams.get('org');
    }

    // If no org found, return href as-is
    if (!orgSlug) {
        return href;
    }

    // Handle string href
    if (typeof href === 'string') {
        try {
            const url = new URL(href, window.location.origin);
            url.searchParams.set('org', orgSlug);
            return url.pathname + url.search;
        } catch {
            // If URL parsing fails, append query param manually
            const separator = href.includes('?') ? '&' : '?';
            return `${href}${separator}org=${encodeURIComponent(orgSlug)}`;
        }
    }

    // Handle object href with url property
    if (typeof href === 'object' && 'url' in href) {
        try {
            const url = new URL(href.url, window.location.origin);
            url.searchParams.set('org', orgSlug);
            return {
                ...href,
                url: url.pathname + url.search,
            };
        } catch {
            // If URL parsing fails, append query param manually
            const separator = href.url.includes('?') ? '&' : '?';
            return {
                ...href,
                url: `${href.url}${separator}org=${encodeURIComponent(orgSlug)}`,
            };
        }
    }

    return href;
}

export function formatRelativeTime(value: string | null | undefined): string {
    if (!value) {
        return 'N/A';
    }

    try {
        return formatDistanceToNow(parseISO(value), { addSuffix: true });
    } catch {
        return value;
    }
}
