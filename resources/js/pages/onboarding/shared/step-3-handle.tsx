import { Form, Head } from '@inertiajs/react';
import { CheckCircleIcon, XCircleIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { checkHandle, store } from '@/routes/onboarding';

interface Step3Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    handle: string | null;
    suggestedHandles: string[];
}

export default function Step3Handle({
    step,
    totalSteps,
    completedSteps,
    handle: initialHandle,
    suggestedHandles,
}: Step3Props) {
    const [handle, setHandle] = useState(initialHandle ?? '');
    const [available, setAvailable] = useState<boolean | null>(null);
    const [suggestions, setSuggestions] = useState<string[]>(suggestedHandles);
    const [checking, setChecking] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>();

    const check = useCallback((value: string) => {
        if (value.length < 3) {
            setAvailable(null);
            return;
        }

        setChecking(true);
        fetch(checkHandle.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ handle: value }),
        })
            .then((res) => res.json())
            .then((data) => {
                setAvailable(data.available);
                if (!data.available && data.suggestions?.length) {
                    setSuggestions(data.suggestions);
                }
            })
            .finally(() => setChecking(false));
    }, []);

    const handleChange = (value: string) => {
        const normalized = value
            .toLowerCase()
            .replace(/[^a-z0-9-]/g, '')
            .replace(/--+/g, '-')
            .slice(0, 30);
        setHandle(normalized);
        setAvailable(null);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => check(normalized), 300);
    };

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="Choose your handle"
            description="This will be your unique URL on heyBertie."
        >
            <Head title="Handle - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="handle" value={handle} />

                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="handle-input">Handle</Label>
                                <div className="flex items-center gap-2">
                                    <span className="text-lg text-muted-foreground">
                                        @
                                    </span>
                                    <div className="relative flex-1">
                                        <Input
                                            id="handle-input"
                                            type="text"
                                            value={handle}
                                            onChange={(e) =>
                                                handleChange(e.target.value)
                                            }
                                            placeholder="your-business-name"
                                            autoFocus
                                        />
                                        {handle.length >= 3 && (
                                            <div className="absolute top-1/2 right-3 -translate-y-1/2">
                                                {checking && (
                                                    <Spinner className="size-4" />
                                                )}
                                                {!checking &&
                                                    available === true && (
                                                        <CheckCircleIcon className="size-4 text-green-500" />
                                                    )}
                                                {!checking &&
                                                    available === false && (
                                                        <XCircleIcon className="size-4 text-red-500" />
                                                    )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                                <InputError message={errors.handle} />
                                <p className="text-xs text-muted-foreground">
                                    3-30 characters. Lowercase letters, numbers,
                                    and hyphens only.
                                </p>
                            </div>

                            {handle && (
                                <p className="text-sm text-muted-foreground">
                                    Your URL:{' '}
                                    <span className="font-medium text-foreground">
                                        bertie.co.uk/@{handle}
                                    </span>
                                </p>
                            )}

                            {available === false &&
                                suggestions.length > 0 && (
                                    <div className="rounded-lg border p-4">
                                        <p className="mb-2 text-sm font-medium">
                                            That handle is taken. Try one of
                                            these:
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {suggestions.map((suggestion) => (
                                                <button
                                                    key={suggestion}
                                                    type="button"
                                                    onClick={() => {
                                                        setHandle(suggestion);
                                                        check(suggestion);
                                                    }}
                                                    className="rounded-full border px-3 py-1 text-sm hover:border-primary hover:text-primary"
                                                >
                                                    @{suggestion}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                )}
                        </div>

                        <StepNavigation
                            currentStep={step}
                            totalSteps={totalSteps}
                            processing={processing}
                            nextDisabled={!handle || available === false}
                        />
                    </>
                )}
            </Form>
        </OnboardingLayout>
    );
}
