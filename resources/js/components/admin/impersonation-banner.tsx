import { router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function ImpersonationBanner({
    fromName,
}: {
    fromName: string;
}) {
    function handleLeave() {
        router.post('/admin/impersonate/leave');
    }

    return (
        <div className="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
            <span>
                You are impersonating as another user.{' '}
                <span className="font-semibold">Logged in by {fromName}.</span>
            </span>
            <Button
                variant="ghost"
                size="sm"
                className="ml-3 h-7 bg-white/20 text-white hover:bg-white/30 hover:text-white"
                onClick={handleLeave}
            >
                <LogOut className="mr-1 size-3.5" />
                Leave
            </Button>
        </div>
    );
}
