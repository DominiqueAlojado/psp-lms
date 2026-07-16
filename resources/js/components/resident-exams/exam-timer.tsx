import { cn } from '@/lib/utils';
import { Clock } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ExamTimerProps {
    durationMinutes: number;
    startedAt: string;
    onExpire: () => void;
    className?: string;
}

function formatTime(seconds: number) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes
        .toString()
        .padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

export function ExamTimer({
    durationMinutes,
    startedAt,
    onExpire,
    className,
}: ExamTimerProps) {
    const [timeRemaining, setTimeRemaining] = useState<number | null>(null);
    const onExpireRef = useRef(onExpire);

    useEffect(() => {
        onExpireRef.current = onExpire;
    }, [onExpire]);

    useEffect(() => {
        const startTime = new Date(startedAt).getTime();
        const endTime = startTime + durationMinutes * 60 * 1000;
        let expired = false;

        const tick = () => {
            const now = Date.now();
            const remaining = Math.max(0, endTime - now);
            const remainingSeconds = Math.floor(remaining / 1000);

            setTimeRemaining(remainingSeconds);

            if (remaining === 0 && !expired) {
                expired = true;
                onExpireRef.current();
            }
        };

        tick();
        const interval = setInterval(tick, 1000);

        return () => clearInterval(interval);
    }, [durationMinutes, startedAt]);

    if (timeRemaining === null) {
        return null;
    }

    return (
        <div
            className={cn(
                'flex items-center gap-2 rounded-lg border bg-background px-4 py-2',
                className,
            )}
        >
            <Clock className="h-4 w-4" />
            <span
                className={cn(
                    'font-mono text-lg font-bold',
                    timeRemaining < 300 && 'text-destructive',
                )}
            >
                {formatTime(timeRemaining)}
            </span>
        </div>
    );
}
