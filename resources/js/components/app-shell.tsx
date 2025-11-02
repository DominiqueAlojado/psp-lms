import { SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const page = usePage<SharedData>();
    const isOpen = page.props.sidebarOpen;

    // Handle flash messages from Laravel
    useEffect(() => {
        const flash = page.props.flash as any;
        
        if (flash?.success) {
            toast.success(flash.success);
        }
        
        if (flash?.error) {
            toast.error(flash.error);
        }
        
        if (flash?.info) {
            toast.info(flash.info);
        }
        
        if (flash?.warning) {
            toast.warning(flash.warning);
        }
    }, [page.props.flash]);

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">
                {children}
                <Toaster />
            </div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen}>
            {children}
            <Toaster />
        </SidebarProvider>
    );
}
