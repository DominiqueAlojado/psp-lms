import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import { toast } from 'sonner';

interface ExportButtonProps {
    /**
     * The export route/URL (e.g., '/residents/export')
     */
    exportUrl: string;

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
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'link' | 'destructive';

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
}

export function ExportButton({
    exportUrl,
    filters = {},
    buttonText = 'Export to Excel',
    variant = 'outline',
    showIcon = true,
    icon: Icon = Download,
    successMessage = 'Exporting data...',
    className,
    onExport,
}: ExportButtonProps) {
    const handleExport = () => {
        // Build query string with provided filters
        const params = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                params.append(key, value.toString());
            }
        });

        // Trigger download
        const queryString = params.toString();
        const url = queryString ? `${exportUrl}?${queryString}` : exportUrl;

        window.location.href = url;
        toast.success(successMessage);

        // Call optional callback
        onExport?.();
    };

    return (
        <Button variant={variant} onClick={handleExport} className={className}>
            {showIcon && <Icon className="mr-2 h-4 w-4" />}
            {buttonText}
        </Button>
    );
}

