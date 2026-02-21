import { Form, Head } from '@inertiajs/react';
import {
    FileTextIcon,
    ShieldCheckIcon,
    UploadIcon,
} from 'lucide-react';
import InputError from '@/components/input-error';
import StepNavigation from '@/components/onboarding/step-navigation';
import { Label } from '@/components/ui/label';
import OnboardingLayout from '@/layouts/onboarding-layout';
import { store } from '@/routes/onboarding';

interface Document {
    id: number;
    document_type: string;
    original_filename: string;
    status: string;
}

interface Step6Props {
    step: number;
    totalSteps: number;
    completedSteps: number[];
    documents: Document[];
    documentTypes: string[];
}

const DOCUMENT_LABELS: Record<string, { label: string; description: string }> =
    {
        photo_id: {
            label: 'Photo ID',
            description:
                'Government-issued photo ID (passport, driving licence)',
        },
        qualification: {
            label: 'Grooming Qualification',
            description: 'City & Guilds, ICMG, or equivalent certificate',
        },
        insurance: {
            label: 'Insurance Certificate',
            description: 'Public liability insurance document',
        },
    };

export default function Step6Verification({
    step,
    totalSteps,
    completedSteps,
    documents,
    documentTypes,
}: Step6Props) {
    const hasDocument = (type: string) =>
        documents.some((d) => d.document_type === type);

    return (
        <OnboardingLayout
            step={step}
            totalSteps={totalSteps}
            completedSteps={completedSteps}
            title="Verify your identity"
            description="Upload documents to build trust with customers. You can skip this step and upload them later."
        >
            <Head title="Verification - Onboarding" />
            <Form
                action={store.url(step)}
                method="post"
                encType="multipart/form-data"
                className="space-y-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                            <div className="flex gap-3">
                                <ShieldCheckIcon className="size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                                <div className="text-sm">
                                    <p className="font-medium text-blue-800 dark:text-blue-200">
                                        Why verify?
                                    </p>
                                    <p className="mt-1 text-blue-700 dark:text-blue-300">
                                        Verified businesses get a badge on their
                                        listing and rank higher in search
                                        results. You can always upload these
                                        later from your settings.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-4">
                            {documentTypes.map((type) => {
                                const info = DOCUMENT_LABELS[type];
                                const uploaded = hasDocument(type);

                                return (
                                    <div
                                        key={type}
                                        className="rounded-lg border p-4"
                                    >
                                        <div className="flex items-start gap-4">
                                            <div className="rounded-lg bg-muted p-2">
                                                {uploaded ? (
                                                    <FileTextIcon className="size-5 text-green-600" />
                                                ) : (
                                                    <UploadIcon className="size-5 text-muted-foreground" />
                                                )}
                                            </div>
                                            <div className="flex-1">
                                                <Label htmlFor={type}>
                                                    {info?.label ?? type}
                                                    <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                        Optional
                                                    </span>
                                                </Label>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {info?.description}
                                                </p>
                                                {uploaded && (
                                                    <p className="mt-1 text-sm text-green-600">
                                                        Previously uploaded
                                                        (upload again to
                                                        replace)
                                                    </p>
                                                )}
                                                <input
                                                    id={type}
                                                    name={type}
                                                    type="file"
                                                    accept=".jpg,.jpeg,.png,.pdf"
                                                    className="mt-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                                                />
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    JPG, PNG, or PDF. Max
                                                    5MB.
                                                </p>
                                                <InputError
                                                    message={errors[type]}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <StepNavigation
                            currentStep={step}
                            totalSteps={totalSteps}
                            processing={processing}
                        />
                    </>
                )}
            </Form>
        </OnboardingLayout>
    );
}
