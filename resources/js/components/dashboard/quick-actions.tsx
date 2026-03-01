import { Link, usePage } from '@inertiajs/react';
import {
    CalendarPlus,
    ListChecks,
    UserPlus,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function QuickActions() {
    const { currentBusiness } = usePage().props;
    const handle = currentBusiness?.handle;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                <Button
                    variant="outline"
                    className="w-full justify-start"
                    asChild
                >
                    <Link href={`/${handle}/calendar`}>
                        <CalendarPlus className="mr-2 size-4" />
                        New Booking
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    className="w-full justify-start"
                    disabled
                >
                    <UserPlus className="mr-2 size-4" />
                    Add Customer
                </Button>
                <Button
                    variant="outline"
                    className="w-full justify-start"
                    asChild
                >
                    <Link href={`/${handle}/services`}>
                        <ListChecks className="mr-2 size-4" />
                        Manage Services
                    </Link>
                </Button>
            </CardContent>
        </Card>
    );
}
