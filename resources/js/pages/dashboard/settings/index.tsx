import { Head, useForm, usePage } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type BusinessSettings = {
    auto_confirm_bookings: boolean;
    staff_selection_enabled: boolean;
};

type Props = {
    settings: BusinessSettings;
};

export default function SettingsPage({ settings }: Props) {
    const { currentBusiness } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: `/${currentBusiness?.handle}/dashboard`,
        },
        {
            title: 'Settings',
            href: `/${currentBusiness?.handle}/settings`,
        },
    ];

    const form = useForm({
        auto_confirm_bookings: settings.auto_confirm_bookings,
        staff_selection_enabled: settings.staff_selection_enabled,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(`/${currentBusiness?.handle}/settings`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Settings" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold">Settings</h1>

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Bookings</CardTitle>
                            <CardDescription>
                                Configure how bookings are handled for your
                                business.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <Label htmlFor="auto_confirm_bookings">
                                        Auto-confirm bookings
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Automatically confirm new bookings
                                        instead of requiring manual approval.
                                    </p>
                                </div>
                                <Switch
                                    id="auto_confirm_bookings"
                                    checked={form.data.auto_confirm_bookings}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'auto_confirm_bookings',
                                            checked,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <Label htmlFor="staff_selection_enabled">
                                        Staff selection
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        Allow customers to choose a specific
                                        staff member when booking.
                                    </p>
                                </div>
                                <Switch
                                    id="staff_selection_enabled"
                                    checked={form.data.staff_selection_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'staff_selection_enabled',
                                            checked,
                                        )
                                    }
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Saving...' : 'Save Settings'}
                    </Button>

                    {form.recentlySuccessful && (
                        <p className="text-sm text-emerald-600">
                            Settings saved.
                        </p>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
