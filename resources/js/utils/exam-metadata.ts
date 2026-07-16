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

    // Detect Browser (order matters - check most specific first)
    if (
        ua.includes('Edg/') ||
        ua.includes('Edge/') ||
        ua.includes('EdgA/') ||
        ua.includes('EdgiOS/')
    ) {
        browser = 'Edge';
        browserVersion =
            ua.match(/Edg\/([0-9.]+)/)?.[1] ||
            ua.match(/Edge\/([0-9.]+)/)?.[1] ||
            ua.match(/EdgA\/([0-9.]+)/)?.[1] ||
            ua.match(/EdgiOS\/([0-9.]+)/)?.[1] ||
            'Unknown';
    } else if (ua.includes('OPR/') || ua.includes('Opera/')) {
        browser = 'Opera';
        browserVersion =
            ua.match(/OPR\/([0-9.]+)/)?.[1] ||
            ua.match(/Opera\/([0-9.]+)/)?.[1] ||
            'Unknown';
    } else if (
        ua.includes('Brave/') ||
        'brave' in navigator ||
        (navigator as unknown as { brave?: unknown }).brave
    ) {
        browser = 'Brave';
        browserVersion = ua.match(/Brave\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Vivaldi/')) {
        browser = 'Vivaldi';
        browserVersion = ua.match(/Vivaldi\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Arc/')) {
        browser = 'Arc';
        browserVersion = ua.match(/Arc\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('SamsungBrowser/')) {
        browser = 'Samsung Internet';
        browserVersion =
            ua.match(/SamsungBrowser\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('UCBrowser/')) {
        browser = 'UC Browser';
        browserVersion = ua.match(/UCBrowser\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('DuckDuckGo/')) {
        browser = 'DuckDuckGo';
        browserVersion = ua.match(/DuckDuckGo\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('YaBrowser/')) {
        browser = 'Yandex';
        browserVersion = ua.match(/YaBrowser\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Firefox/')) {
        browser = 'Firefox';
        browserVersion = ua.match(/Firefox\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Chrome/')) {
        browser = 'Chrome';
        browserVersion = ua.match(/Chrome\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Safari/') && !ua.includes('Chrome')) {
        browser = 'Safari';
        browserVersion = ua.match(/Version\/([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('MSIE') || ua.includes('Trident/')) {
        browser = 'Internet Explorer';
        browserVersion =
            ua.match(/MSIE ([0-9.]+)/)?.[1] ||
            ua.match(/rv:([0-9.]+)/)?.[1] ||
            'Unknown';
    } else if (ua.includes('Chromium/')) {
        browser = 'Chromium';
        browserVersion = ua.match(/Chromium\/([0-9.]+)/)?.[1] || 'Unknown';
    }

    // Detect OS (order matters - check most specific first)
    if (ua.includes('Android')) {
        os = 'Android';
        osVersion = ua.match(/Android ([0-9.]+)/)?.[1] || 'Unknown';
        device = 'Mobile';
    } else if (
        ua.includes('iOS') ||
        ua.includes('iPhone') ||
        ua.includes('iPad')
    ) {
        os = 'iOS';
        osVersion =
            ua.match(/OS ([0-9_]+)/)?.[1]?.replace(/_/g, '.') || 'Unknown';
        device = ua.includes('iPad') ? 'Tablet' : 'Mobile';
    } else if (ua.includes('CrOS')) {
        os = 'ChromeOS';
        osVersion = ua.match(/CrOS [^ ]+ ([0-9.]+)/)?.[1] || 'Unknown';
    } else if (ua.includes('Windows NT')) {
        os = 'Windows';
        const version = ua.match(/Windows NT ([0-9.]+)/)?.[1];
        if (version === '10.0') osVersion = '10/11';
        else if (version === '6.3') osVersion = '8.1';
        else if (version === '6.2') osVersion = '8';
        else if (version === '6.1') osVersion = '7';
        else osVersion = version || 'Unknown';
    } else if (ua.includes('Mac OS X')) {
        os = 'macOS';
        osVersion =
            ua.match(/Mac OS X ([0-9_]+)/)?.[1]?.replace(/_/g, '.') ||
            'Unknown';
    } else if (ua.includes('Linux')) {
        os = 'Linux';
        // Try to detect specific Linux distributions
        if (ua.includes('Ubuntu')) {
            os = 'Ubuntu';
        } else if (ua.includes('Fedora')) {
            os = 'Fedora';
        } else if (ua.includes('Debian')) {
            os = 'Debian';
        }
    } else if (ua.includes('FreeBSD')) {
        os = 'FreeBSD';
    } else if (ua.includes('Unix')) {
        os = 'Unix';
    }

    // Detect device type (more precise)
    if (
        /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
            ua,
        )
    ) {
        device =
            ua.includes('iPad') || ua.includes('Tablet') ? 'Tablet' : 'Mobile';
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
    // Network Information API may not be in all TypeScript definitions
    const nav = navigator as Navigator & {
        connection?: {
            effectiveType?: string;
            downlink?: number;
            rtt?: number;
            saveData?: boolean;
        };
        mozConnection?: {
            effectiveType?: string;
            downlink?: number;
            rtt?: number;
            saveData?: boolean;
        };
        webkitConnection?: {
            effectiveType?: string;
            downlink?: number;
            rtt?: number;
            saveData?: boolean;
        };
    };

    const connection =
        nav.connection || nav.mozConnection || nav.webkitConnection;

    if (!connection) {
        // Network Information API not available - return empty object
        // This is normal for Firefox, Safari, and some privacy-focused browsers
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
    } catch {
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

    // Only fall back to a small speed test when the browser exposes no useful network hints.
    if (
        !connectionSpeed &&
        !connectionInfo.effectiveType &&
        !connectionInfo.saveData
    ) {
        connectionSpeed = await measureConnectionSpeed();
    }

    // Determine connection type with better fallback
    let connectionType = 'broadband'; // Default assumption for modern browsers
    if (connectionInfo.effectiveType) {
        connectionType = connectionInfo.effectiveType;
    } else if (connectionInfo.saveData) {
        connectionType = 'slow';
    } else if (connectionSpeed) {
        // Classify based on measured speed
        if (connectionSpeed >= 10) connectionType = '4g';
        else if (connectionSpeed >= 5) connectionType = '3g';
        else if (connectionSpeed >= 1) connectionType = '2g';
        else connectionType = 'slow-2g';
    }

    return {
        userAgent: navigator.userAgent,
        browserMetadata,
        connectionType,
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

    // Determine connection type with better fallback
    let connectionType = 'broadband'; // Default assumption
    if (connectionInfo.effectiveType) {
        connectionType = connectionInfo.effectiveType;
    } else if (connectionInfo.saveData) {
        connectionType = 'slow';
    }

    return {
        userAgent: navigator.userAgent,
        browserMetadata,
        connectionType,
    };
}
