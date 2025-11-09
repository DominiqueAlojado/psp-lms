import { captureExamMetadataSync } from '@/utils/exam-metadata';
import axios from 'axios';
import { useEffect, useRef } from 'react';

interface SessionData {
    userAgent: string;
    browserMetadata: any;
    changeLogged?: boolean; // Track if we already logged this change
}

interface UseExamSessionMonitorProps {
    examType: 'institution' | 'inservice';
    attemptId: number;
    isActive: boolean; // Only monitor when exam is active
}

const IDLE_THRESHOLD = 30; // 2 minutes of no activity = idle
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

        // Fetch initial session data from server and check for changes
        const initializeAndCheck = async () => {
            try {
                // Get the attempt data from server (includes initial user_agent)
                const response = await axios.get(
                    `/exams/${examType}/${attemptId}/session-info`,
                );

                const serverUserAgent = response.data.user_agent;
                const currentMetadata = captureExamMetadataSync();

                // Store initial session from this page load
                if (!initialSession.current) {
                    initialSession.current = {
                        userAgent: currentMetadata.userAgent,
                        browserMetadata: currentMetadata.browserMetadata,
                        changeLogged: false,
                    };
                }

                // Compare current browser with what was used to start the exam
                const userAgentChanged =
                    serverUserAgent &&
                    currentMetadata.userAgent !== serverUserAgent;

                // Only log if changed AND not already logged in this session
                if (userAgentChanged && !initialSession.current.changeLogged) {
                    console.warn(
                        '⚠️ Browser/Device change detected during exam!',
                    );
                    console.log('Original:', serverUserAgent);
                    console.log('Current:', currentMetadata.userAgent);

                    // Log the change
                    try {
                        await axios.post(
                            `/exams/${examType}/${attemptId}/log-session-change`,
                            {
                                change_type: 'browser',
                                previous_user_agent: serverUserAgent,
                                new_user_agent: currentMetadata.userAgent,
                                browser_info: currentMetadata.browserMetadata,
                            },
                        );

                        // Mark as logged to prevent duplicate logs
                        initialSession.current.changeLogged = true;
                        console.log('✅ Browser change logged successfully');
                    } catch (error) {
                        console.error('Failed to log session change:', error);
                    }
                }
            } catch (error) {
                console.error('Failed to fetch session info:', error);
            }
        };

        // Check immediately and periodically
        initializeAndCheck();
        checkIntervalRef.current = setInterval(initializeAndCheck, 30000);

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
                // User came back to the tab, check for changes against server data
                try {
                    const response = await axios.get(
                        `/exams/${examType}/${attemptId}/session-info`,
                    );

                    const serverUserAgent = response.data.user_agent;
                    const currentMetadata = captureExamMetadataSync();

                    if (
                        serverUserAgent &&
                        currentMetadata.userAgent !== serverUserAgent
                    ) {
                        console.warn(
                            '⚠️ Browser change detected after tab became visible!',
                        );

                        await axios.post(
                            `/exams/${examType}/${attemptId}/log-session-change`,
                            {
                                change_type: 'browser',
                                previous_user_agent: serverUserAgent,
                                new_user_agent: currentMetadata.userAgent,
                                browser_info: currentMetadata.browserMetadata,
                            },
                        );
                    }
                } catch (error) {
                    console.error(
                        'Failed to check session on visibility change:',
                        error,
                    );
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
