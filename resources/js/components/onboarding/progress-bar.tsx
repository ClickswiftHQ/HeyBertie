import { Link } from '@inertiajs/react';
import { CheckIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { step as stepRoute } from '@/routes/onboarding';

const STEP_LABELS = [
    'Type',
    'Details',
    'Handle',
    'Location',
    'Services',
    'Verify',
    'Plan',
];

interface ProgressBarProps {
    currentStep: number;
    totalSteps: number;
    completedSteps: number[];
}

export default function ProgressBar({
    currentStep,
    totalSteps,
    completedSteps,
}: ProgressBarProps) {
    return (
        <nav aria-label="Onboarding progress" className="w-full">
            <ol className="flex items-center justify-between">
                {STEP_LABELS.map((label, index) => {
                    const stepNumber = index + 1;
                    const isCompleted = completedSteps.includes(stepNumber);
                    const isCurrent = stepNumber === currentStep;
                    const isAccessible = isCompleted || isCurrent;

                    return (
                        <li
                            key={stepNumber}
                            className="flex flex-1 flex-col items-center gap-2"
                        >
                            <div className="flex w-full items-center">
                                {index > 0 && (
                                    <div
                                        className={cn(
                                            'h-0.5 w-full',
                                            isCompleted || isCurrent
                                                ? 'bg-primary'
                                                : 'bg-border',
                                        )}
                                    />
                                )}
                                {isAccessible ? (
                                    <Link
                                        href={stepRoute.url(stepNumber)}
                                        className={cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-medium transition-colors',
                                            isCompleted &&
                                                'bg-primary text-primary-foreground',
                                            isCurrent &&
                                                !isCompleted &&
                                                'border-2 border-primary bg-background text-primary',
                                        )}
                                    >
                                        {isCompleted ? (
                                            <CheckIcon className="size-4" />
                                        ) : (
                                            stepNumber
                                        )}
                                    </Link>
                                ) : (
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-background text-xs font-medium text-muted-foreground">
                                        {stepNumber}
                                    </div>
                                )}
                                {index < totalSteps - 1 && (
                                    <div
                                        className={cn(
                                            'h-0.5 w-full',
                                            isCompleted
                                                ? 'bg-primary'
                                                : 'bg-border',
                                        )}
                                    />
                                )}
                            </div>
                            <span
                                className={cn(
                                    'text-xs',
                                    isCurrent
                                        ? 'font-medium text-foreground'
                                        : 'text-muted-foreground',
                                )}
                            >
                                <span className="hidden sm:inline">
                                    {label}
                                </span>
                            </span>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
