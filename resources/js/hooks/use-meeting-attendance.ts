import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { captureExamMetadata } from '@/utils/exam-metadata';

interface UseMeetingAttendanceOptions {
    eventId: number;
    isEventLive: boolean;
    hasRegistration: boolean;
    virtualLink: string | null;
    eventType: 'in-person' | 'virtual' | 'hybrid';
}

interface AttendanceStatus {
    isTracking: boolean;
    joinedAt: Date | null;
    status: 'idle' | 'joined' | 'active' | 'left' | 'timeout';
    error: string | null;
}

export function useMeetingAttendance({
    eventId,
    isEventLive,
    hasRegistration,
    virtualLink,
    eventType,
}: UseMeetingAttendanceOptions) {
    const [attendance, setAttendance] = useState<AttendanceStatus>({
        isTracking: false,
        joinedAt: null,
        status: 'idle',
        error: null,
    });

    const heartbeatIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const lastHeartbeatRef = useRef<Date | null>(null);
    const isPageVisibleRef = useRef<boolean>(true);
    const hasJoinedRef = useRef<boolean>(false);

    // Check if event is virtual/hybrid and has a link
    const shouldTrack =
        isEventLive &&
        hasRegistration &&
        (eventType === 'virtual' || eventType === 'hybrid') &&
        !!virtualLink;

    // Join meeting function (can be called manually or automatically)
    const joinMeeting = async () => {
        if (hasJoinedRef.current) {
            console.log('⚠️ Already tracking attendance');
            return;
        }

        console.log('📥 Attempting to join meeting...', {
            eventId,
            url: `/events/${eventId}/meeting/join`,
        });

        try {
            // Capture comprehensive metadata for hybrid and virtual events
            let metadata = null;
            if (eventType === 'virtual' || eventType === 'hybrid') {
                console.log('📊 Capturing attendee metadata...');
                try {
                    const capturedMetadata = await captureExamMetadata();
                    metadata = {
                        browser_metadata: capturedMetadata.browserMetadata,
                        connection_type: capturedMetadata.connectionType,
                        connection_speed: capturedMetadata.connectionSpeed,
                        user_agent: capturedMetadata.userAgent,
                        // For hybrid events, determine if joining virtually
                        attendance_type: eventType === 'hybrid' ? 'virtual' : 'virtual',
                    };
                    console.log('✅ Metadata captured:', {
                        browser: metadata.browser_metadata.browser,
                        device: metadata.browser_metadata.device,
                        connectionType: metadata.connection_type,
                        connectionSpeed: metadata.connection_speed,
                        attendanceType: metadata.attendance_type,
                    });
                } catch (metadataError) {
                    console.warn('⚠️ Failed to capture metadata, continuing without it:', metadataError);
                }
            }

            const response = await axios.post(`/events/${eventId}/meeting/join`, {
                metadata,
            });

            console.log('📥 Join meeting response:', response.data);

            if (response.data.success) {
                console.log('✅ Successfully joined meeting. Attendance ID:', response.data.attendance_id);
                setAttendance({
                    isTracking: true,
                    joinedAt: new Date(),
                    status: 'joined',
                    error: null,
                });
                hasJoinedRef.current = true;
                lastHeartbeatRef.current = new Date();
            } else {
                console.error('❌ Failed to join meeting:', response.data.error);
                setAttendance((prev) => ({
                    ...prev,
                    error: response.data.error || 'Failed to join meeting',
                }));
            }
        } catch (error) {
            console.error('❌ Error joining meeting:', error);
            const axiosError = error as { response?: { data?: { error?: string }; status?: number }; message?: string };
            const errorMessage = axiosError.response?.data?.error || axiosError.message || 'Failed to connect to server';
            console.error('Error details:', {
                status: axiosError.response?.status,
                data: axiosError.response?.data,
                message: errorMessage,
            });
            setAttendance((prev) => ({
                ...prev,
                error: errorMessage,
            }));
        }
    };

    // Debug logging
    useEffect(() => {
        console.log('🔍 Meeting Attendance Tracking Debug:', {
            eventId,
            isEventLive,
            hasRegistration,
            eventType,
            hasVirtualLink: !!virtualLink,
            shouldTrack,
        });
    }, [eventId, isEventLive, hasRegistration, eventType, virtualLink, shouldTrack]);

    // Send heartbeat to update active status
    const sendHeartbeat = async () => {
        if (!hasJoinedRef.current || !isPageVisibleRef.current) {
            return;
        }

        try {
            const response = await axios.post(`/events/${eventId}/meeting/heartbeat`);

            if (response.data.success) {
                setAttendance((prev) => ({
                    ...prev,
                    status: response.data.status || 'active',
                }));
                lastHeartbeatRef.current = new Date();
            } else if (response.data.status === 'timeout') {
                setAttendance((prev) => ({
                    ...prev,
                    status: 'timeout',
                    isTracking: false,
                }));
                hasJoinedRef.current = false;
                clearHeartbeatInterval();
            }
        } catch (error) {
            // Silently fail - network issues shouldn't break the experience
            const axiosError = error as { response?: { status?: number } };
            if (axiosError.response?.status === 404) {
                // Attendance record not found - might have been deleted
                hasJoinedRef.current = false;
                clearHeartbeatInterval();
            }
        }
    };

    // Join meeting automatically when page loads (if conditions are met)
    useEffect(() => {
        if (!shouldTrack || hasJoinedRef.current) {
            if (!shouldTrack) {
                console.log('⚠️ Tracking not active. Reasons:', {
                    isEventLive,
                    hasRegistration,
                    eventType: eventType === 'virtual' || eventType === 'hybrid',
                    hasVirtualLink: !!virtualLink,
                });
            }
            return;
        }

        joinMeeting();
    }, [shouldTrack, eventId, isEventLive, hasRegistration, eventType, virtualLink]);

    // Page Visibility API - track when page is visible/hidden
    useEffect(() => {
        const handleVisibilityChange = () => {
            isPageVisibleRef.current = !document.hidden;

            if (!document.hidden && hasJoinedRef.current) {
                // Page became visible - send heartbeat immediately
                sendHeartbeat();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
        };
    }, []);

    // Window focus/blur events
    useEffect(() => {
        const handleFocus = () => {
            isPageVisibleRef.current = true;
            if (hasJoinedRef.current) {
                sendHeartbeat();
            }
        };

        const handleBlur = () => {
            isPageVisibleRef.current = false;
        };

        window.addEventListener('focus', handleFocus);
        window.addEventListener('blur', handleBlur);

        return () => {
            window.removeEventListener('focus', handleFocus);
            window.removeEventListener('blur', handleBlur);
        };
    }, []);

    // Set up heartbeat interval (every 30 seconds)
    useEffect(() => {
        if (!hasJoinedRef.current || !shouldTrack) {
            return;
        }

        heartbeatIntervalRef.current = setInterval(() => {
            if (isPageVisibleRef.current) {
                sendHeartbeat();
            }
        }, 30000); // 30 seconds

        return () => {
            clearHeartbeatInterval();
        };
    }, [shouldTrack, hasJoinedRef.current]);

    const clearHeartbeatInterval = () => {
        if (heartbeatIntervalRef.current) {
            clearInterval(heartbeatIntervalRef.current);
            heartbeatIntervalRef.current = null;
        }
    };

    // Leave meeting when component unmounts or event ends
    useEffect(() => {
        const leaveMeeting = async () => {
            if (!hasJoinedRef.current) {
                return;
            }

            try {
                await axios.post(`/events/${eventId}/meeting/leave`);

                setAttendance((prev) => ({
                    ...prev,
                    status: 'left',
                    isTracking: false,
                }));
                hasJoinedRef.current = false;
            } catch (error) {
                // Silently fail - component is unmounting anyway
                console.log('Leave meeting request completed (may have failed silently)');
            }
        };

        // Leave when component unmounts
        return () => {
            clearHeartbeatInterval();
            if (hasJoinedRef.current) {
                // Use sendBeacon for reliable tracking on page unload
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(
                        `/events/${eventId}/meeting/leave`,
                        new Blob(
                            [
                                JSON.stringify({
                                    _token: document
                                        .querySelector(
                                            'meta[name="csrf-token"]',
                                        )
                                        ?.getAttribute('content') || '',
                                }),
                            ],
                            { type: 'application/json' },
                        ),
                    );
                } else {
                    leaveMeeting();
                }
            }
        };
    }, [eventId]);

    // Handle page unload (beforeunload event)
    useEffect(() => {
        const handleBeforeUnload = () => {
            if (hasJoinedRef.current && navigator.sendBeacon) {
                navigator.sendBeacon(
                    `/events/${eventId}/meeting/leave`,
                    new Blob(
                        [
                            JSON.stringify({
                                _token: document
                                    .querySelector('meta[name="csrf-token"]')
                                    ?.getAttribute('content') || '',
                            }),
                        ],
                        { type: 'application/json' },
                    ),
                );
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [eventId]);

    // Manual start tracking function (exported for manual trigger)
    const startTracking = async () => {
        console.log('🚀 Manual tracking start requested');
        await joinMeeting();
    };

    return {
        isTracking: attendance.isTracking,
        status: attendance.status,
        joinedAt: attendance.joinedAt,
        error: attendance.error,
        shouldTrack,
        startTracking,
    };
}

