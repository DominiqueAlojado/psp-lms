import { detectMeetingPlatform } from '@/utils/meeting-platform';
import ZoomMeetingEmbed, { type ZoomMeetingEmbedRef } from './zoom-meeting-embed';
import GoogleMeetEmbed, { type GoogleMeetEmbedRef } from './google-meet-embed';
import { forwardRef, useRef, useImperativeHandle } from 'react';

interface MeetingEmbedProps {
    virtualLink: string;
    userName?: string;
    userEmail?: string;
}

export interface MeetingEmbedRef {
    closePopup: () => void;
}

/**
 * Main component that detects the meeting platform and renders the appropriate embed
 */
const MeetingEmbed = forwardRef<MeetingEmbedRef, MeetingEmbedProps>(({
    virtualLink,
    userName,
    userEmail,
}, ref) => {
    const platformInfo = detectMeetingPlatform(virtualLink);
    const zoomRef = useRef<ZoomMeetingEmbedRef>(null);
    const googleMeetRef = useRef<GoogleMeetEmbedRef>(null);

    // Expose closePopup method via ref
    useImperativeHandle(ref, () => ({
        closePopup: () => {
            if (platformInfo.platform === 'zoom' && zoomRef.current) {
                zoomRef.current.closePopup();
            } else if (platformInfo.platform === 'google-meet' && googleMeetRef.current) {
                googleMeetRef.current.closePopup();
            }
        },
    }));

    switch (platformInfo.platform) {
        case 'zoom':
            return (
                <ZoomMeetingEmbed
                    ref={zoomRef}
                    virtualLink={virtualLink}
                    userName={userName}
                    userEmail={userEmail}
                />
            );

        case 'google-meet':
            return <GoogleMeetEmbed ref={googleMeetRef} virtualLink={virtualLink} />;

        case 'microsoft-teams':
            // Teams also blocks iframe embedding, similar to Google Meet
            return (
                <div className="flex h-full flex-col items-center justify-center bg-gray-900 p-8 text-center">
                    <div className="max-w-md space-y-4">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10">
                            <span className="text-2xl">👥</span>
                        </div>
                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold text-white">
                                Microsoft Teams Meeting
                            </h3>
                            <p className="text-sm text-gray-400">
                                Click the button below to join the Teams meeting in a new tab.
                            </p>
                        </div>
                        <a
                            href={virtualLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-block"
                        >
                            <button className="rounded-md bg-blue-600 px-6 py-3 text-white transition-colors hover:bg-blue-700">
                                Join Teams Meeting
                            </button>
                        </a>
                    </div>
                </div>
            );

        default:
            // Generic fallback for other meeting platforms
            return (
                <div className="flex h-full flex-col items-center justify-center bg-gray-900 p-8 text-center">
                    <div className="max-w-md space-y-4">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-500/10">
                            <span className="text-2xl">📹</span>
                        </div>
                        <div className="space-y-2">
                            <h3 className="text-lg font-semibold text-white">
                                Join Meeting
                            </h3>
                            <p className="text-sm text-gray-400">
                                Click the button below to join the meeting in a new tab.
                            </p>
                        </div>
                        <a
                            href={virtualLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-block"
                        >
                            <button className="rounded-md bg-primary px-6 py-3 text-white transition-colors hover:bg-primary/90">
                                Join Meeting
                            </button>
                        </a>
                    </div>
                </div>
            );
    }
});

MeetingEmbed.displayName = 'MeetingEmbed';

export default MeetingEmbed;

