import { captureExamMetadata } from '@/utils/exam-metadata';
import axios from 'axios';
import { useEffect, useRef } from 'react';

interface UseCaptureExamMetadataProps {
    examType: 'institution' | 'inservice';
    attemptId: number;
}

/**
 * Hook to capture and send exam metadata on page load
 */
export function useCaptureExamMetadata({
    examType,
    attemptId,
}: UseCaptureExamMetadataProps) {
    const captured = useRef(false);

    useEffect(() => {
        if (captured.current) return;

        const capture = async () => {
            try {
                const metadata = await captureExamMetadata();

                await axios.post(
                    `/exams/${examType}/${attemptId}/update-metadata`,
                    {
                        browser_metadata: metadata.browserMetadata,
                        connection_type: metadata.connectionType,
                        connection_speed: metadata.connectionSpeed,
                    },
                );
                captured.current = true;
            } catch {
                return;
            }
        };

        // Capture after a short delay to ensure page is fully loaded
        const timeout = setTimeout(capture, 1000);

        return () => clearTimeout(timeout);
    }, [examType, attemptId]);
}
