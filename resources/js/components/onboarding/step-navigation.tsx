import { Link } from '@inertiajs/react';
import { ArrowLeftIcon, ArrowRightIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { step as stepRoute } from '@/routes/onboarding';

interface StepNavigationProps {
    currentStep: number;
    totalSteps: number;
    processing?: boolean;
    nextLabel?: string;
    nextDisabled?: boolean;
}

export default function StepNavigation({
    currentStep,
    totalSteps,
    processing = false,
    nextLabel,
    nextDisabled = false,
}: StepNavigationProps) {
    const label =
        nextLabel ?? (currentStep >= totalSteps ? 'Review' : 'Continue');

    return (
        <div className="flex items-center justify-between pt-6">
            {currentStep > 1 ? (
                <Button variant="outline" asChild>
                    <Link href={stepRoute.url(currentStep - 1)}>
                        <ArrowLeftIcon className="size-4" />
                        Back
                    </Link>
                </Button>
            ) : (
                <div />
            )}
            <Button
                type="submit"
                disabled={nextDisabled || processing}
            >
                {processing ? (
                    <Spinner />
                ) : (
                    <>
                        {label}
                        <ArrowRightIcon className="size-4" />
                    </>
                )}
            </Button>
        </div>
    );
}
