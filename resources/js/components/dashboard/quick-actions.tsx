import {
    CalendarPlus,
    ListChecks,
    UserPlus,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function QuickActions() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                <Button
                    variant="outline"
                    className="w-full justify-start"
                    disabled
                >
                    <CalendarPlus className="mr-2 size-4" />
                    New Booking
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
                    disabled
                >
                    <ListChecks className="mr-2 size-4" />
                    Manage Services
                </Button>
            </CardContent>
        </Card>
    );
}
