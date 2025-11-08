/**
 * Utility functions to capture exam metadata (browser info, connection speed, etc.)
 */

interface BrowserMetadata {
    browser: string;
    browserVersion: string;
    os: string;
    osVersion: string;
    device: string;
    screenResolution: string;
    language: string;
    timezone: string;
}

interface ConnectionInfo {
    effectiveType?: string; // 4g, 3g, 2g, slow-2g
    downlink?: number; // Mbps
    rtt?: number; // Round trip time in ms
    saveData?: boolean;
}

interface ExamMetadata {
    userAgent: string;
    browserMetadata: BrowserMetadata;
    connectionType: string;
    connectionSpeed: number | null;
    ipAddress?: string; // Will be captured server-side
}

/**
 * Parse user agent to extract browser information
 */
function parseBrowserInfo(): BrowserMetadata {
    const ua = navigator.userAgent;
    let browser = 'Unknown';
    let browserVersion = 'Unknown';
    let os = 'Unknown';
    let osVersion = 'Unknown';
    let device = 'Desktop';

    // Detect Browser
    if (ua.includes('Firefox/')) {
        browser = 'Firefox';
        browserVersion = ua.match(/Firefox\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Edg/')) {
        browser = 'Edge';
        browserVersion = ua.match(/Edg\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Chrome/') && !ua.includes('Edg/')) {
        browser = 'Chrome';
        browserVersion = ua.match(/Chrome\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Safari/') && !ua.includes('Chrome')) {
        browser = 'Safari';
        browserVersion = ua.match(/Version\/([0-9.]+)/)?.[1] || 'Unknown';
    }

    // Detect OS
    if (ua.includes('Windows NT')) {
        os = 'Windows';
        const version = ua.match(/Windows NT ([0-9.]+)/)?.[1];
        if (version === '10.0') osVersion = '10/11';
        else if (version === '6.3') osVersion = '8.1';
        else if (version === '6.2') osVersion = '8';
        else osVersion = version || 'Unknown';
    } else if (ua.includes('Mac OS X')) {
        os = 'macOS';
        osVersion = ua.match(/Mac OS X ([0-9_]+)/)?.[1]?.replace(/_/g, '.') || 'Unknown';
    } else if (ua.includes('Linux')) {
        os = 'Linux';
    } else if (ua.includes('Android')) {
        os = 'Android';
        osVersion = ua.match(/Android ([0-9.]+)/)?.[1] || 'Unknown';
        device = 'Mobile';
    } else if (ua.includes('iOS') || ua.includes('iPhone') || ua.includes('iPad')) {
        os = 'iOS';
        osVersion = ua.match(/OS ([0-9_]+)/)?.[1]?.replace(/_/g, '.') || 'Unknown';
        device = ua.includes('iPad') ? 'Tablet' : 'Mobile';
    }

    // Detect device type (more precise)
    if (
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
            ua,
        )
    ) {
        device = ua.includes('iPad') || ua.includes('Tablet') ? 'Tablet' : 'Mobile';
    }

    return {
        browser,
        browserVersion,
        os,
        osVersion,
        device,
        screenResolution: `${screen.width}x${screen.height}`,
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    };
}

/**
 * Get connection information from Network Information API
 */
function getConnectionInfo(): ConnectionInfo {
    // @ts-ignore - Network Information API may not be in all TypeScript definitions
    const connection =
        navigator.connection ||
        navigator.mozConnection ||
        navigator.webkitConnection;

    if (!connection) {
        return {};
    }

    return {
        effectiveType: connection.effectiveType, // 4g, 3g, 2g, slow-2g
        downlink: connection.downlink, // Mbps
        rtt: connection.rtt, // Round trip time in ms
        saveData: connection.saveData,
    };
}

/**
 * Perform a simple speed test by downloading a small file
 */
async function measureConnectionSpeed(): Promise<number | null> {
    try {
        // Use a small image or test file from your server
        const testUrl = '/favicon.svg'; // Small file for testing
        const startTime = performance.now();

        const response = await fetch(testUrl + '?cache=' + Date.now(), {
            cache: 'no-store',
        });

        if (!response.ok) {
            return null;
        }

        const blob = await response.blob();
        const endTime = performance.now();

        const durationInSeconds = (endTime - startTime) / 1000;
        const fileSizeInBits = blob.size * 8;
        const speedBps = fileSizeInBits / durationInSeconds;
        const speedMbps = speedBps / (1024 * 1024);

        return Math.round(speedMbps * 100) / 100; // Round to 2 decimals
    } catch (error) {
        console.error('Failed to measure connection speed:', error);
        return null;
    }
}

/**
 * Capture all exam metadata
 */
export async function captureExamMetadata(): Promise<ExamMetadata> {
    const connectionInfo = getConnectionInfo();
    const browserMetadata = parseBrowserInfo();

    // Measure connection speed (optional, can be skipped if slow)
    let connectionSpeed = connectionInfo.downlink || null;

    // If Network Information API not available, do a simple speed test
    if (!connectionSpeed) {
        connectionSpeed = await measureConnectionSpeed();
    }

    return {
        userAgent: navigator.userAgent,
        browserMetadata,
        connectionType:
            connectionInfo.effectiveType ||
            connectionInfo.saveData
                ? 'slow'
                : 'unknown',
        connectionSpeed,
    };
}

/**
 * Quick metadata capture (without speed test) for immediate use
 */
export function captureExamMetadataSync(): Omit<
    ExamMetadata,
    'connectionSpeed'
> {
    const connectionInfo = getConnectionInfo();
    const browserMetadata = parseBrowserInfo();

    return {
        userAgent: navigator.userAgent,
        browserMetadata,
        connectionType:
            connectionInfo.effectiveType ||
            (connectionInfo.saveData ? 'slow' : 'unknown'),
    };
}

