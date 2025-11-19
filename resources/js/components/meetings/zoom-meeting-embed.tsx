import { useState, useEffect, useImperativeHandle, forwardRef } from 'react';
import { ExternalLink, Video } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface ZoomMeetingEmbedProps {
    virtualLink: string;
    userName?: string;
    userEmail?: string;
    onPopupClosed?: () => void;
}

export interface ZoomMeetingEmbedRef {
    closePopup: () => void;
}

/**
 * Zoom meeting component - opens in popup window (no API required)
 * Since Zoom requires SDK/API for true embedding, we open it in a full-screen popup
 * that feels embedded within the app
 */
const ZoomMeetingEmbed = forwardRef<ZoomMeetingEmbedRef, ZoomMeetingEmbedProps>(({
    virtualLink,
    userName = 'Guest',
    userEmail = '',
    onPopupClosed,
}, ref) => {
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [popupWindow, setPopupWindow] = useState<Window | null>(null);

    useEffect(() => {
        // Open Zoom meeting in a popup window
        const openMeeting = () => {
            const width = window.screen.width;
            const height = window.screen.height;
            const left = 0;
            const top = 0;

            const popup = window.open(
                virtualLink,
                'ZoomMeeting',
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
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10">
                    <Video className="h-8 w-8 text-blue-500" />
                </div>
                <div className="space-y-2">
                    <h3 className="text-lg font-semibold text-white">
                        {isFullscreen ? 'Meeting Opened' : 'Opening Zoom Meeting'}
                    </h3>
                    <p className="text-sm text-gray-400">
                        {isFullscreen
                            ? 'The meeting has been opened in a popup window. If you don\'t see it, check your browser\'s popup blocker settings.'
                            : 'Zoom meeting will open in a new window. Please allow popups if prompted.'}
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
                            <strong>Note:</strong> The meeting is running in a separate window that stays on top
                            of this page. Your attendance is still being tracked on this page.
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
});

ZoomMeetingEmbed.displayName = 'ZoomMeetingEmbed';

export default ZoomMeetingEmbed;

