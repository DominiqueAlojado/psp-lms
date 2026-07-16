import { captureExamMetadataSync } from '@/utils/exam-metadata';
import axios from 'axios';
import { useEffect, useRef } from 'react';

interface SessionData {
    userAgent: string;
    ipAddress: string | null;
    browserMetadata: unknown;
    browserChangeLogged?: boolean; // Track if we already logged browser change
    ipChangeLogged?: boolean; // Track if we already logged IP change
}

interface UseExamSessionMonitorProps {
    examType: 'institution' | 'inservice';
    attemptId: number;
    isActive: boolean; // Only monitor when exam is active
}

const IDLE_THRESHOLD = 120; // 2 minutes of no activity = idle
const ACTIVITY_CHECK_INTERVAL = 15000; // Heartbeat every 15 seconds
const SESSION_CHECK_INTERVAL = 30000; // Session/IP check every 30 seconds
const VISIBILITY_CHECK_COOLDOWN = 10000; // Avoid duplicate checks on quick refocus

/**
 * Hook to monitor and log browser/IP changes and idle time during an exam
 */
export function useExamSessionMonitor({
    examType,
    attemptId,
    isActive,
}: UseExamSessionMonitorProps) {
    const initialSession = useRef<SessionData | null>(null);
    const checkIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastActivityTime = useRef<number>(0);
    const activityIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastSessionCheckAt = useRef<number>(0);

    // Track user activity
    useEffect(() => {
        if (!isActive) {
            return;
        }

        // Initialize activity time on mount
        if (lastActivityTime.current === 0) {
            lastActivityTime.current = Date.now();
        }

        const updateActivity = () => {
            lastActivityTime.current = Date.now();
        };

        // Listen to user interactions
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        events.forEach((event) => {
            document.addEventListener(event, updateActivity);
        });

        return () => {
            events.forEach((event) => {
                document.removeEventListener(event, updateActivity);
            });
        };
    }, [isActive]);

    // Monitor idle time and send heartbeat
    useEffect(() => {
        if (!isActive) {
            return;
        }

        const checkActivity = async () => {
            const now = Date.now();
            const timeSinceLastActivity = Math.floor(
                (now - lastActivityTime.current) / 1000,
            );

            // If idle for more than threshold, log it
            if (timeSinceLastActivity >= IDLE_THRESHOLD) {
                try {
                    await axios.post(
                        `/exams/${examType}/${attemptId}/log-activity`,
                        {
                            idle_duration: timeSinceLastActivity,
                        },
                    );

                    // Reset timer after logging
                    lastActivityTime.current = now;
                } catch {
                    return;
                }
            } else {
                // Normal activity heartbeat (no idle duration)
                try {
                    await axios.post(
                        `/exams/${examType}/${attemptId}/log-activity`,
                        {},
                    );
                } catch {
                    return;
                }
            }
        };

        // Check activity periodically
        activityIntervalRef.current = setInterval(
            checkActivity,
            ACTIVITY_CHECK_INTERVAL,
        );

        return () => {
            if (activityIntervalRef.current) {
                clearInterval(activityIntervalRef.current);
            }
        };
    }, [examType, attemptId, isActive]);

    useEffect(() => {
        if (!isActive) {
            return;
        }

        // Fetch initial session data from server and check for changes
        const initializeAndCheck = async () => {
            try {
                lastSessionCheckAt.current = Date.now();

                // Get the attempt data from server (includes initial user_agent and IP)
                const response = await axios.get(
                    `/exams/${examType}/${attemptId}/session-info`,
                );

                const serverUserAgent = response.data.user_agent;
                const serverIpAddress = response.data.ip_address;
                const currentMetadata = captureExamMetadataSync();

                // Get current IP from server (can't get it from JavaScript directly)
                const currentIpResponse = await axios.get(
                    `/exams/${examType}/${attemptId}/current-ip`,
                );
                const currentIpAddress = currentIpResponse.data.ip_address;

                // Store initial session from this page load
                if (!initialSession.current) {
                    initialSession.current = {
                        userAgent: currentMetadata.userAgent,
                        ipAddress: currentIpAddress,
                        browserMetadata: currentMetadata.browserMetadata,
                        browserChangeLogged: false,
                        ipChangeLogged: false,
                    };
                }

                // Check for browser changes
                const userAgentChanged =
                    serverUserAgent &&
                    currentMetadata.userAgent !== serverUserAgent;

                // Check for IP address changes
                const ipAddressChanged =
                    serverIpAddress &&
                    currentIpAddress &&
                    currentIpAddress !== serverIpAddress;

                // Determine change type
                let changeType: 'browser' | 'ip_address' | 'both' | null = null;
                if (userAgentChanged && ipAddressChanged) {
                    changeType = 'both';
                } else if (userAgentChanged) {
                    changeType = 'browser';
                } else if (ipAddressChanged) {
                    changeType = 'ip_address';
                }

                // Only proceed if we have initialized session
                if (!initialSession.current) {
                    return;
                }

                // Log browser change if detected and not already logged
                // Log the change(s) if detected
                if (changeType) {
                    const shouldLogBrowser =
                        (changeType === 'browser' || changeType === 'both') &&
                        !initialSession.current.browserChangeLogged;
                    const shouldLogIp =
                        (changeType === 'ip_address' ||
                            changeType === 'both') &&
                        !initialSession.current.ipChangeLogged;

                    if (shouldLogBrowser || shouldLogIp) {
                        try {
                            await axios.post(
                                `/exams/${examType}/${attemptId}/log-session-change`,
                                {
                                    change_type: changeType,
                                    previous_ip: serverIpAddress,
                                    new_ip: currentIpAddress,
                                    previous_user_agent: serverUserAgent,
                                    new_user_agent: currentMetadata.userAgent,
                                    browser_info:
                                        currentMetadata.browserMetadata,
                                },
                            );

                            // Mark as logged to prevent duplicate logs
                            if (shouldLogBrowser) {
                                initialSession.current.browserChangeLogged = true;
                            }
                            if (shouldLogIp) {
                                initialSession.current.ipChangeLogged = true;
                            }
                        } catch {
                            return;
                        }
                    }
                }
            } catch {
                return;
            }
        };

        // Check immediately and periodically
        initializeAndCheck();
        checkIntervalRef.current = setInterval(
            initializeAndCheck,
            SESSION_CHECK_INTERVAL,
        );

        // Cleanup
        return () => {
            if (checkIntervalRef.current) {
                clearInterval(checkIntervalRef.current);
            }
        };
    }, [examType, attemptId, isActive]);

    // Also check when page visibility changes (tab switching)
    useEffect(() => {
        if (!isActive) {
            return;
        }

        const handleVisibilityChange = async () => {
            if (document.visibilityState === 'visible') {
                if (
                    Date.now() - lastSessionCheckAt.current <
                    VISIBILITY_CHECK_COOLDOWN
                ) {
                    return;
                }

                // User came back to the tab, check for changes against server data
                try {
                    lastSessionCheckAt.current = Date.now();

                    const response = await axios.get(
                        `/exams/${examType}/${attemptId}/session-info`,
                    );

                    const serverUserAgent = response.data.user_agent;
                    const serverIpAddress = response.data.ip_address;
                    const currentMetadata = captureExamMetadataSync();

                    // Get current IP from server
                    const currentIpResponse = await axios.get(
                        `/exams/${examType}/${attemptId}/current-ip`,
                    );
                    const currentIpAddress = currentIpResponse.data.ip_address;

                    // Check for changes
                    const userAgentChanged =
                        serverUserAgent &&
                        currentMetadata.userAgent !== serverUserAgent;

                    const ipAddressChanged =
                        serverIpAddress &&
                        currentIpAddress &&
                        currentIpAddress !== serverIpAddress;

                    // Determine change type
                    let changeType: 'browser' | 'ip_address' | 'both' | null =
                        null;
                    if (userAgentChanged && ipAddressChanged) {
                        changeType = 'both';
                    } else if (userAgentChanged) {
                        changeType = 'browser';
                    } else if (ipAddressChanged) {
                        changeType = 'ip_address';
                    }

                    const shouldLogBrowser =
                        changeType &&
                        initialSession.current &&
                        (changeType === 'browser' || changeType === 'both') &&
                        !initialSession.current.browserChangeLogged;

                    const shouldLogIp =
                        changeType &&
                        initialSession.current &&
                        (changeType === 'ip_address' ||
                            changeType === 'both') &&
                        !initialSession.current.ipChangeLogged;

                    if (shouldLogBrowser || shouldLogIp) {
                        await axios.post(
                            `/exams/${examType}/${attemptId}/log-session-change`,
                            {
                                change_type: changeType,
                                previous_ip: serverIpAddress,
                                new_ip: currentIpAddress,
                                previous_user_agent: serverUserAgent,
                                new_user_agent: currentMetadata.userAgent,
                                browser_info: currentMetadata.browserMetadata,
                            },
                        );

                        // Mark as logged to prevent duplicate logs
                        if (initialSession.current) {
                            if (
                                changeType === 'browser' ||
                                changeType === 'both'
                            ) {
                                initialSession.current.browserChangeLogged = true;
                            }
                            if (
                                changeType === 'ip_address' ||
                                changeType === 'both'
                            ) {
                                initialSession.current.ipChangeLogged = true;
                            }
                        }
                    }
                } catch {
                    return;
                }
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
        };
    }, [examType, attemptId, isActive]);
}
