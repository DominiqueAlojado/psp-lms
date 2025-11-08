import { captureExamMetadataSync } from '@/utils/exam-metadata';
import axios from 'axios';
import { useEffect, useRef } from 'react';

interface SessionData {
    userAgent: string;
    browserMetadata: any;
}

interface UseExamSessionMonitorProps {
    examType: 'institution' | 'inservice';
    attemptId: number;
    isActive: boolean; // Only monitor when exam is active
}

const IDLE_THRESHOLD = 120; // 2 minutes of no activity = idle
const ACTIVITY_CHECK_INTERVAL = 30000; // Check every 30 seconds

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
    const lastActivityTime = useRef<number>(Date.now());
    const activityIntervalRef = useRef<NodeJS.Timeout | null>(null);

    // Track user activity
    useEffect(() => {
        if (!isActive) {
            return;
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
                } catch (error) {
                    console.error('Failed to log idle time:', error);
                }
            } else {
                // Normal activity heartbeat (no idle duration)
                try {
                    await axios.post(
                        `/exams/${examType}/${attemptId}/log-activity`,
                        {},
                    );
                } catch (error) {
                    console.error('Failed to log activity:', error);
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

        // Capture initial session data
        if (!initialSession.current) {
            const metadata = captureExamMetadataSync();
            initialSession.current = {
                userAgent: metadata.userAgent,
                browserMetadata: metadata.browserMetadata,
            };
        }

        // Check for changes periodically
        const checkForChanges = async () => {
            const currentMetadata = captureExamMetadataSync();

            if (!initialSession.current) {
                return;
            }

            const userAgentChanged =
                currentMetadata.userAgent !== initialSession.current.userAgent;

            if (userAgentChanged) {
                console.warn('⚠️ Browser/Device change detected during exam!');

                // Log the change
                try {
                    await axios.post(
                        `/exams/${examType}/${attemptId}/log-session-change`,
                        {
                            change_type: 'browser',
                            previous_user_agent:
                                initialSession.current.userAgent,
                            new_user_agent: currentMetadata.userAgent,
                            browser_info: currentMetadata.browserMetadata,
                        },
                    );

                    // Update reference
                    initialSession.current = {
                        userAgent: currentMetadata.userAgent,
                        browserMetadata: currentMetadata.browserMetadata,
                    };
                } catch (error) {
                    console.error('Failed to log session change:', error);
                }
            }
        };

        // Check every 30 seconds
        checkIntervalRef.current = setInterval(checkForChanges, 30000);

        // Initial check after 5 seconds
        const initialCheckTimeout = setTimeout(checkForChanges, 5000);

        // Cleanup
        return () => {
            if (checkIntervalRef.current) {
                clearInterval(checkIntervalRef.current);
            }
            clearTimeout(initialCheckTimeout);
        };
    }, [examType, attemptId, isActive]);

    // Also check when page visibility changes (tab switching)
    useEffect(() => {
        if (!isActive) {
            return;
        }

        const handleVisibilityChange = async () => {
            if (document.visibilityState === 'visible') {
                // User came back to the tab, check for changes
                const currentMetadata = captureExamMetadataSync();

                if (
                    initialSession.current &&
                    currentMetadata.userAgent !==
                        initialSession.current.userAgent
                ) {
                    console.warn(
                        '⚠️ Browser change detected after tab became visible!',
                    );

                    try {
                        await axios.post(
                            `/exams/${examType}/${attemptId}/log-session-change`,
                            {
                                change_type: 'browser',
                                previous_user_agent:
                                    initialSession.current.userAgent,
                                new_user_agent: currentMetadata.userAgent,
                                browser_info: currentMetadata.browserMetadata,
                            },
                        );

                        initialSession.current = {
                            userAgent: currentMetadata.userAgent,
                            browserMetadata: currentMetadata.browserMetadata,
                        };
                    } catch (error) {
                        console.error('Failed to log session change:', error);
                    }
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

