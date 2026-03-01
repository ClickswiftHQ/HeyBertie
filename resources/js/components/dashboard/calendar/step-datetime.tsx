import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import BookingController from '@/actions/App/Http/Controllers/BookingController';

type StepDatetimeProps = {
    locationId: number;
    totalDuration: number;
    selectedDate: string;
    selectedTime: string;
    onSelectDate: (date: string) => void;
    onSelectTime: (time: string) => void;
    errors: Record<string, string>;
};

type AvailableDate = {
    date: string;
    available: boolean;
};

type TimeSlot = {
    time: string;
    duration: number;
    period: string;
};

function parseDateParts(dateStr: string) {
    const d = new Date(dateStr + 'T00:00:00');
    return {
        dayName: d.toLocaleDateString('en-GB', { weekday: 'short' }),
        dayNumber: d.getDate(),
        monthName: d.toLocaleDateString('en-GB', { month: 'short' }),
    };
}

function formatTime(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const h = hours % 12 || 12;
    const ampm = hours < 12 ? 'am' : 'pm';
    return minutes === 0 ? `${h}${ampm}` : `${h}:${minutes.toString().padStart(2, '0')}${ampm}`;
}

export function StepDatetime({
    locationId,
    totalDuration,
    selectedDate,
    selectedTime,
    onSelectDate,
    onSelectTime,
    errors,
}: StepDatetimeProps) {
    const [availableDates, setAvailableDates] = useState<AvailableDate[]>([]);
    const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
    const [loadingDates, setLoadingDates] = useState(true);
    const [loadingSlots, setLoadingSlots] = useState(false);

    const fetchAvailableDates = useCallback(async () => {
        setLoadingDates(true);
        try {
            const url = BookingController.availableDates.url(locationId, {
                query: { duration: totalDuration },
            });
            const response = await fetch(url);
            const data = await response.json();
            const dates: AvailableDate[] = data.dates ?? [];
            setAvailableDates(dates.filter((d) => d.available));
        } finally {
            setLoadingDates(false);
        }
    }, [locationId, totalDuration]);

    const fetchTimeSlots = useCallback(
        async (date: string) => {
            setLoadingSlots(true);
            try {
                const url = BookingController.timeSlots.url(locationId, {
                    query: { date, duration: totalDuration },
                });
                const response = await fetch(url);
                const data = await response.json();
                setTimeSlots(data.slots ?? []);
            } finally {
                setLoadingSlots(false);
            }
        },
        [locationId, totalDuration],
    );

    useEffect(() => {
        fetchAvailableDates();
    }, [fetchAvailableDates]);

    useEffect(() => {
        if (selectedDate) {
            fetchTimeSlots(selectedDate);
        }
    }, [selectedDate, fetchTimeSlots]);

    return (
        <div className="space-y-4">
            <div className="grid gap-1.5">
                <Label>Date</Label>
                {errors.appointment_datetime && (
                    <p className="text-destructive text-xs">
                        {errors.appointment_datetime}
                    </p>
                )}

                {loadingDates ? (
                    <div className="flex gap-2">
                        {Array.from({ length: 5 }).map((_, i) => (
                            <div
                                key={i}
                                className="bg-muted h-16 w-16 animate-pulse rounded-md"
                            />
                        ))}
                    </div>
                ) : availableDates.length === 0 ? (
                    <p className="text-muted-foreground py-4 text-center text-sm">
                        No available dates found.
                    </p>
                ) : (
                    <div className="flex gap-2 overflow-x-auto pb-2">
                        {availableDates.map((d) => {
                            const { dayName, dayNumber, monthName } =
                                parseDateParts(d.date);
                            return (
                                <Button
                                    key={d.date}
                                    type="button"
                                    variant={
                                        selectedDate === d.date
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="flex h-auto min-w-16 flex-col px-3 py-2"
                                    onClick={() => {
                                        onSelectDate(d.date);
                                        onSelectTime('');
                                    }}
                                >
                                    <span className="text-xs">{dayName}</span>
                                    <span className="text-lg font-bold">
                                        {dayNumber}
                                    </span>
                                    <span className="text-xs">{monthName}</span>
                                </Button>
                            );
                        })}
                    </div>
                )}
            </div>

            {selectedDate && (
                <div className="grid gap-1.5">
                    <Label>Time</Label>
                    {loadingSlots ? (
                        <div className="grid grid-cols-4 gap-2">
                            {Array.from({ length: 8 }).map((_, i) => (
                                <div
                                    key={i}
                                    className="bg-muted h-9 animate-pulse rounded-md"
                                />
                            ))}
                        </div>
                    ) : timeSlots.length === 0 ? (
                        <p className="text-muted-foreground py-4 text-center text-sm">
                            No available time slots for this date.
                        </p>
                    ) : (
                        <div className="grid grid-cols-4 gap-2">
                            {timeSlots.map((slot) => (
                                <Button
                                    key={slot.time}
                                    type="button"
                                    variant={
                                        selectedTime === slot.time
                                            ? 'default'
                                            : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => onSelectTime(slot.time)}
                                >
                                    {formatTime(slot.time)}
                                </Button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
