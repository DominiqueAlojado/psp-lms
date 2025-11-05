import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Download } from 'lucide-react';
import { toast } from 'sonner';

interface ExportButtonProps {
    /**
     * The export route/URL (e.g., '/residents/export')
     * @alias href
     */
    exportUrl?: string;

    /**
     * The export route/URL (e.g., '/residents/export')
     * @alias exportUrl
     */
    href?: string;

    /**
     * Filters to apply to the export
     */
    filters?: Record<string, string | number | boolean | undefined | null>;

    /**
     * Button text
     * @default 'Export to Excel'
     */
    buttonText?: string;

    /**
     * Button variant
     * @default 'outline'
     */
    variant?:
        | 'default'
        | 'outline'
        | 'secondary'
        | 'ghost'
        | 'link'
        | 'destructive';

    /**
     * Show icon
     * @default true
     */
    showIcon?: boolean;

    /**
     * Custom icon component
     */
    icon?: React.ComponentType<{ className?: string }>;

    /**
     * Success toast message
     */
    successMessage?: string;

    /**
     * Additional CSS classes
     */
    className?: string;

    /**
     * Callback after export is triggered
     */
    onExport?: () => void;

    /**
     * Disable the export button
     * @default false
     */
    disabled?: boolean;
}

export function ExportButton({
    exportUrl,
    href,
    filters = {},
    buttonText = 'Export to Excel',
    variant = 'outline',
    showIcon = true,
    icon: Icon = Download,
    successMessage = 'Exporting data...',
    className,
    onExport,
    disabled = false,
}: ExportButtonProps) {
    // Support both exportUrl and href for backward compatibility
    const url = href || exportUrl;

    const handleExport = () => {
        if (!url) return;

        // Build query string with provided filters
        const params = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, value.toString());
            }
        });

        // Trigger download
        const queryString = params.toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;

        window.location.href = fullUrl;
        toast.success(successMessage);

        // Call optional callback
        onExport?.();
    };

    if (disabled) {
        return (
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span className="inline-block">
                            <Button
                                variant={variant}
                                onClick={handleExport}
                                className={className}
                                disabled={disabled}
                            >
                                {showIcon && <Icon className="mr-2 h-4 w-4" />}
                                {buttonText}
                            </Button>
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>You don't have permission to perform this action</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        );
    }

    return (
        <Button
            variant={variant}
            onClick={handleExport}
            className={className}
            disabled={disabled}
        >
            {showIcon && <Icon className="mr-2 h-4 w-4" />}
            {buttonText}
        </Button>
    );
}
