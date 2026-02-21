import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import ProgressBar from '@/components/onboarding/progress-bar';
import { dashboard } from '@/routes';

interface OnboardingLayoutProps {
    children: ReactNode;
    step: number;
    totalSteps: number;
    completedSteps: number[];
    title: string;
    description?: string;
}

export default function OnboardingLayout({
    children,
    step,
    totalSteps,
    completedSteps,
    title,
    description,
}: OnboardingLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                    <a
                        href="/"
                        className="flex items-center gap-2"
                    >
                        <AppLogoIcon className="size-7 fill-current text-foreground" />
                        <span className="sr-only">heyBertie</span>
                    </a>
                    <Link
                        href={dashboard()}
                        className="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Save & exit
                    </Link>
                </div>
            </header>

            <div className="mx-auto w-full max-w-3xl px-6 py-8">
                <ProgressBar
                    currentStep={step}
                    totalSteps={totalSteps}
                    completedSteps={completedSteps}
                />

                <div className="mt-8 space-y-2">
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    {description && (
                        <p className="text-muted-foreground">{description}</p>
                    )}
                </div>

                <div className="mt-8">{children}</div>
            </div>
        </div>
    );
}
