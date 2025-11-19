/**
 * Detects the meeting platform from a virtual link URL
 */
export type MeetingPlatform = 'zoom' | 'google-meet' | 'microsoft-teams' | 'other';

export interface MeetingPlatformInfo {
    platform: MeetingPlatform;
    meetingId?: string;
    password?: string;
    isEmbeddable: boolean;
}

/**
 * Detects the meeting platform and extracts relevant information
 */
export function detectMeetingPlatform(virtualLink: string): MeetingPlatformInfo {
    if (!virtualLink) {
        return {
            platform: 'other',
            isEmbeddable: false,
        };
    }

    const url = new URL(virtualLink);

    // Zoom detection
    if (url.hostname.includes('zoom.us') || url.hostname.includes('zoom.com')) {
        const meetingId = url.pathname.split('/').pop() || url.searchParams.get('pwd')?.split('/')[0] || url.searchParams.get('id');
        const password = url.searchParams.get('pwd') || url.searchParams.get('password');

        return {
            platform: 'zoom',
            meetingId: meetingId || undefined,
            password: password || undefined,
            isEmbeddable: true,
        };
    }

    // Google Meet detection
    if (url.hostname.includes('meet.google.com')) {
        const meetingId = url.pathname.split('/').pop();

        return {
            platform: 'google-meet',
            meetingId: meetingId || undefined,
            isEmbeddable: false, // Google Meet blocks iframe embedding
        };
    }

    // Microsoft Teams detection
    if (url.hostname.includes('teams.microsoft.com') || url.hostname.includes('teams.live.com')) {
        return {
            platform: 'microsoft-teams',
            isEmbeddable: false,
        };
    }

    return {
        platform: 'other',
        isEmbeddable: false,
    };
}

/**
 * Extracts Zoom meeting ID and password from various Zoom URL formats
 */
export function extractZoomMeetingInfo(virtualLink: string): { meetingId?: string; password?: string } {
    try {
        const url = new URL(virtualLink);
        
        // Format: https://zoom.us/j/MEETING_ID?pwd=PASSWORD
        // Format: https://zoom.us/j/MEETING_ID/PASSWORD
        // Format: https://zoom.us/s/MEETING_ID?pwd=PASSWORD
        
        let meetingId = url.pathname.split('/').filter(Boolean).pop();
        let password = url.searchParams.get('pwd') || url.searchParams.get('password');

        // If password is in the path (format: /j/MEETING_ID/PASSWORD)
        if (!password && meetingId) {
            const parts = url.pathname.split('/').filter(Boolean);
            if (parts.length > 2) {
                meetingId = parts[1];
                password = parts[2];
            }
        }

        return {
            meetingId: meetingId || undefined,
            password: password || undefined,
        };
    } catch {
        return {};
    }
}

