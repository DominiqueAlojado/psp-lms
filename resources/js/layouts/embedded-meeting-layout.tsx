import { type ReactNode } from 'react';

interface EmbeddedMeetingLayoutProps {
    children: ReactNode;
    title?: string;
    onClose?: () => void;
}

/**
 * Full-screen layout for embedded meetings
 * Provides a clean, distraction-free environment for video meetings
 */
export default function EmbeddedMeetingLayout({
    children,
    title,
    onClose,
}: EmbeddedMeetingLayoutProps) {
    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-black">
            {/* Header Bar */}
            {title && (
                <div className="flex items-center justify-between border-b border-gray-800 bg-gray-900 px-4 py-3">
                    <h2 className="text-sm font-medium text-white">{title}</h2>
                    {onClose && (
                        <button
                            onClick={onClose}
                            className="rounded-md px-3 py-1.5 text-sm text-gray-300 transition-colors hover:bg-gray-800 hover:text-white"
                        >
                            Exit Meeting View
                        </button>
                    )}
                </div>
            )}

            {/* Meeting Content */}
            <div className="flex-1 overflow-hidden">{children}</div>
        </div>
    );
}

