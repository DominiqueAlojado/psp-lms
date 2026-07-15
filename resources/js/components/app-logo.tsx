import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 items-center justify-center rounded-2xl bg-[image:var(--gradient-brand)] text-sidebar-primary-foreground shadow-[0_16px_30px_-18px_rgb(96_44_193_/_0.45)]">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold tracking-[0.01em] text-sidebar-foreground">
                    PSP LMS
                </span>
                <span className="truncate text-[0.72rem] font-medium text-sidebar-foreground/55">
                    Learning workspace
                </span>
            </div>
        </>
    );
}
