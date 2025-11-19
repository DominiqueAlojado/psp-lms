import { useState, useEffect, useImperativeHandle, forwardRef } from 'react';
import { ExternalLink, Maximize2, Minimize2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface GoogleMeetEmbedProps {
    virtualLink: string;
    onPopupClosed?: () => void;
}

export interface GoogleMeetEmbedRef {
    closePopup: () => void;
}

/**
 * Google Meet full-screen overlay component
 * Since Google Meet blocks iframe embedding, this creates a full-screen
 * overlay that opens the meeting in a way that feels embedded
 */
const GoogleMeetEmbed = forwardRef<GoogleMeetEmbedRef, GoogleMeetEmbedProps>(({ virtualLink, onPopupClosed }, ref) => {
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [popupWindow, setPopupWindow] = useState<Window | null>(null);

    useEffect(() => {
        // Open Google Meet in a popup window
        const openMeeting = () => {
            const width = window.screen.width;
            const height = window.screen.height;
            const left = 0;
            const top = 0;

            const popup = window.open(
                virtualLink,
                'GoogleMeet',
                `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes`,
            );

            if (popup) {
                setPopupWindow(popup);
                setIsFullscreen(true);

                // Monitor if popup is closed
                const checkClosed = setInterval(() => {
                    if (popup.closed) {
                        setIsFullscreen(false);
                        setPopupWindow(null);
                        clearInterval(checkClosed);
                        // Notify parent that popup was closed
                        if (onPopupClosed) {
                            onPopupClosed();
                        }
                    }
                }, 500);

                return () => {
                    clearInterval(checkClosed);
                };
            }
        };

        // Auto-open on mount
        openMeeting();

        // Cleanup on unmount
        return () => {
            if (popupWindow && !popupWindow.closed) {
                popupWindow.close();
            }
        };
    }, [virtualLink]);

    const handleOpenInNewTab = () => {
        window.open(virtualLink, '_blank', 'noopener,noreferrer');
    };

    const handleClosePopup = () => {
        if (popupWindow && !popupWindow.closed) {
            popupWindow.close();
        }
        setIsFullscreen(false);
        setPopupWindow(null);
    };

    // Expose closePopup method via ref
    useImperativeHandle(ref, () => ({
        closePopup: handleClosePopup,
    }));

    return (
        <div className="flex h-full flex-col items-center justify-center bg-gray-900 p-8 text-center">
            <div className="max-w-md space-y-6">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10">
                    <ExternalLink className="h-8 w-8 text-green-500" />
                </div>
                <div className="space-y-2">
                    <h3 className="text-lg font-semibold text-white">
                        {isFullscreen ? 'Meeting Opened' : 'Opening Google Meet'}
                    </h3>
                    <p className="text-sm text-gray-400">
                        {isFullscreen
                            ? 'The meeting has been opened in a popup window. If you don\'t see it, check your browser\'s popup blocker settings.'
                            : 'Google Meet will open in a new window. Please allow popups if prompted.'}
                    </p>
                </div>
                <div className="flex flex-col gap-2">
                    {isFullscreen ? (
                        <>
                            <Button
                                onClick={handleClosePopup}
                                variant="outline"
                                size="lg"
                                className="w-full"
                            >
                                <Minimize2 className="mr-2 h-4 w-4" />
                                Close Meeting Window
                            </Button>
                            <Button
                                onClick={handleOpenInNewTab}
                                variant="ghost"
                                size="sm"
                                className="w-full text-gray-400"
                            >
                                <ExternalLink className="mr-2 h-4 w-4" />
                                Open in New Tab Instead
                            </Button>
                        </>
                    ) : (
                        <Button
                            onClick={handleOpenInNewTab}
                            size="lg"
                            className="w-full"
                        >
                            <ExternalLink className="mr-2 h-4 w-4" />
                            Open Meeting in New Tab
                        </Button>
                    )}
                </div>
                {isFullscreen && (
                    <div className="rounded-lg border border-gray-700 bg-gray-800/50 p-4 text-left">
                        <p className="text-xs text-gray-400">
                            <strong>Note:</strong> Google Meet blocks iframe embedding for security
                            reasons. The meeting is running in a separate window that stays on top
                            of this page. Your attendance is still being tracked on this page.
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
});

GoogleMeetEmbed.displayName = 'GoogleMeetEmbed';

export default GoogleMeetEmbed;

