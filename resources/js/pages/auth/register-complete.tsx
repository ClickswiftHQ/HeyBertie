import { Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import AuthLayout from '@/layouts/auth-layout';

export default function RegisterComplete() {
    return (
        <AuthLayout
            title="Check your email"
            description="We've sent a verification link to your email address. Please click the link to verify your account."
        >
            <Head title="Check your email" />

            <div className="text-center">
                <TextLink href="/login" className="text-sm">
                    Back to login
                </TextLink>
            </div>
        </AuthLayout>
    );
}
