import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import BookingManagementController from '@/actions/App/Http/Controllers/Dashboard/BookingManagementController';
import type {
    ManualBookingCustomer,
    ManualBookingLocation,
    ManualBookingService,
} from '@/types';
import { StepCustomer } from './step-customer';
import { StepDatetime } from './step-datetime';
import { StepReview } from './step-review';
import { StepServices } from './step-services';

type NewBookingDialogProps = {
    handle: string;
    locations: ManualBookingLocation[];
    services: ManualBookingService[];
    recentCustomers: ManualBookingCustomer[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const STEPS = ['Customer', 'Services', 'Date & Time', 'Review'] as const;

export function NewBookingDialog({
    handle,
    locations,
    services,
    recentCustomers,
    open,
    onOpenChange,
}: NewBookingDialogProps) {
    const [currentStep, setCurrentStep] = useState(0);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Customer state
    const [selectedCustomer, setSelectedCustomer] =
        useState<ManualBookingCustomer | null>(null);
    const [isNewCustomer, setIsNewCustomer] = useState(false);
    const [newCustomerData, setNewCustomerData] = useState({
        name: '',
        email: '',
        phone: '',
    });

    // Service state
    const [selectedLocationId, setSelectedLocationId] = useState<number | null>(
        locations.length === 1 ? locations[0].id : null,
    );
    const [selectedServiceIds, setSelectedServiceIds] = useState<number[]>([]);

    // Datetime state
    const [selectedDate, setSelectedDate] = useState('');
    const [selectedTime, setSelectedTime] = useState('');

    // Pet state
    const [petName, setPetName] = useState('');
    const [petBreed, setPetBreed] = useState('');
    const [petSize, setPetSize] = useState('');
    const [notes, setNotes] = useState('');

    function reset() {
        setCurrentStep(0);
        setProcessing(false);
        setErrors({});
        setSelectedCustomer(null);
        setIsNewCustomer(false);
        setNewCustomerData({ name: '', email: '', phone: '' });
        setSelectedLocationId(
            locations.length === 1 ? locations[0].id : null,
        );
        setSelectedServiceIds([]);
        setSelectedDate('');
        setSelectedTime('');
        setPetName('');
        setPetBreed('');
        setPetSize('');
        setNotes('');
    }

    function handleOpenChange(isOpen: boolean) {
        if (!isOpen) {
            reset();
        }
        onOpenChange(isOpen);
    }

    function validateStep(): boolean {
        const stepErrors: Record<string, string> = {};

        if (currentStep === 0) {
            if (!isNewCustomer && !selectedCustomer) {
                stepErrors.customer_id = 'Please select a customer.';
            }
            if (isNewCustomer) {
                if (!newCustomerData.name.trim())
                    stepErrors.name = 'Name is required.';
                if (!newCustomerData.email.trim())
                    stepErrors.email = 'Email is required.';
                if (!newCustomerData.phone.trim())
                    stepErrors.phone = 'Phone is required.';
            }
        }

        if (currentStep === 1) {
            if (!selectedLocationId)
                stepErrors.location_id = 'Please select a location.';
            if (selectedServiceIds.length === 0)
                stepErrors.service_ids = 'Please select at least one service.';
        }

        if (currentStep === 2) {
            if (!selectedDate || !selectedTime)
                stepErrors.appointment_datetime =
                    'Please select a date and time.';
        }

        if (currentStep === 3) {
            if (!petName.trim()) stepErrors.pet_name = 'Pet name is required.';
        }

        setErrors(stepErrors);
        return Object.keys(stepErrors).length === 0;
    }

    function handleNext() {
        if (!validateStep()) return;
        setCurrentStep((prev) => Math.min(prev + 1, STEPS.length - 1));
    }

    function handleBack() {
        setCurrentStep((prev) => Math.max(prev - 1, 0));
    }

    function handleToggleService(serviceId: number) {
        setSelectedServiceIds((prev) =>
            prev.includes(serviceId)
                ? prev.filter((id) => id !== serviceId)
                : [...prev, serviceId],
        );
    }

    function handleSelectLocation(locationId: number) {
        setSelectedLocationId(locationId);
        setSelectedServiceIds([]);
    }

    function handleSubmit() {
        if (!validateStep()) return;

        setProcessing(true);

        const data: Record<string, unknown> = {
            location_id: selectedLocationId,
            service_ids: selectedServiceIds,
            appointment_datetime: `${selectedDate} ${selectedTime}`,
            pet_name: petName,
            pet_breed: petBreed || null,
            pet_size: petSize || null,
            notes: notes || null,
        };

        if (isNewCustomer) {
            data.name = newCustomerData.name;
            data.email = newCustomerData.email;
            data.phone = newCustomerData.phone;
        } else {
            data.customer_id = selectedCustomer!.id;
        }

        router.post(
            BookingManagementController.storeManual.url(handle),
            data,
            {
                onSuccess: () => handleOpenChange(false),
                onError: (responseErrors) => {
                    setErrors(responseErrors);
                    setProcessing(false);

                    // Navigate to the step with errors
                    if (
                        responseErrors.name ||
                        responseErrors.email ||
                        responseErrors.phone ||
                        responseErrors.customer_id
                    ) {
                        setCurrentStep(0);
                    } else if (
                        responseErrors.location_id ||
                        responseErrors.service_ids
                    ) {
                        setCurrentStep(1);
                    } else if (responseErrors.appointment_datetime) {
                        setCurrentStep(2);
                    } else {
                        setCurrentStep(3);
                    }
                },
            },
        );
    }

    const selectedLocation =
        locations.find((l) => l.id === selectedLocationId) ?? null;
    const allSelectedServices = services.filter((s) =>
        selectedServiceIds.includes(s.id),
    );
    const totalDuration = allSelectedServices.reduce(
        (sum, s) => sum + s.duration_minutes,
        0,
    );

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>New Booking</DialogTitle>
                    <DialogDescription>
                        Step {currentStep + 1} of {STEPS.length}:{' '}
                        {STEPS[currentStep]}
                    </DialogDescription>
                </DialogHeader>

                <div className="py-2">
                    {currentStep === 0 && (
                        <StepCustomer
                            handle={handle}
                            recentCustomers={recentCustomers}
                            selectedCustomer={selectedCustomer}
                            isNewCustomer={isNewCustomer}
                            newCustomerData={newCustomerData}
                            onSelectCustomer={setSelectedCustomer}
                            onToggleNewCustomer={setIsNewCustomer}
                            onUpdateNewCustomer={setNewCustomerData}
                            errors={errors}
                        />
                    )}

                    {currentStep === 1 && (
                        <StepServices
                            locations={locations}
                            services={services}
                            selectedLocationId={selectedLocationId}
                            selectedServiceIds={selectedServiceIds}
                            onSelectLocation={handleSelectLocation}
                            onToggleService={handleToggleService}
                            errors={errors}
                        />
                    )}

                    {currentStep === 2 && selectedLocationId && (
                        <StepDatetime
                            locationId={selectedLocationId}
                            totalDuration={totalDuration}
                            selectedDate={selectedDate}
                            selectedTime={selectedTime}
                            onSelectDate={setSelectedDate}
                            onSelectTime={setSelectedTime}
                            errors={errors}
                        />
                    )}

                    {currentStep === 3 && (
                        <StepReview
                            customer={selectedCustomer}
                            newCustomerData={newCustomerData}
                            isNewCustomer={isNewCustomer}
                            location={selectedLocation}
                            selectedServices={allSelectedServices}
                            selectedDate={selectedDate}
                            selectedTime={selectedTime}
                            petName={petName}
                            petBreed={petBreed}
                            petSize={petSize}
                            notes={notes}
                            onUpdatePetName={setPetName}
                            onUpdatePetBreed={setPetBreed}
                            onUpdatePetSize={setPetSize}
                            onUpdateNotes={setNotes}
                            errors={errors}
                        />
                    )}
                </div>

                <DialogFooter>
                    {currentStep > 0 && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleBack}
                            disabled={processing}
                        >
                            Back
                        </Button>
                    )}

                    {currentStep < STEPS.length - 1 ? (
                        <Button type="button" onClick={handleNext}>
                            Next
                        </Button>
                    ) : (
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            disabled={processing}
                        >
                            {processing ? 'Creating...' : 'Create Booking'}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
