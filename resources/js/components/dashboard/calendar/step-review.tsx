import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type {
    ManualBookingCustomer,
    ManualBookingLocation,
    ManualBookingService,
} from '@/types';

type StepReviewProps = {
    customer: ManualBookingCustomer | null;
    newCustomerData: { name: string; email: string; phone: string };
    isNewCustomer: boolean;
    location: ManualBookingLocation | null;
    selectedServices: ManualBookingService[];
    selectedDate: string;
    selectedTime: string;
    petName: string;
    petBreed: string;
    petSize: string;
    notes: string;
    onUpdatePetName: (name: string) => void;
    onUpdatePetBreed: (breed: string) => void;
    onUpdatePetSize: (size: string) => void;
    onUpdateNotes: (notes: string) => void;
    errors: Record<string, string>;
};

export function StepReview({
    customer,
    newCustomerData,
    isNewCustomer,
    location,
    selectedServices,
    selectedDate,
    selectedTime,
    petName,
    petBreed,
    petSize,
    notes,
    onUpdatePetName,
    onUpdatePetBreed,
    onUpdatePetSize,
    onUpdateNotes,
    errors,
}: StepReviewProps) {
    const customerName = isNewCustomer
        ? newCustomerData.name
        : customer?.name ?? 'Unknown';

    const totalDuration = selectedServices.reduce(
        (sum, s) => sum + s.duration_minutes,
        0,
    );
    const totalPrice = selectedServices.reduce(
        (sum, s) => sum + (parseFloat(s.price ?? '0') || 0),
        0,
    );

    const formattedDate = selectedDate
        ? new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-GB', {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : '';

    return (
        <div className="space-y-4">
            <div className="bg-muted space-y-2 rounded-md p-3 text-sm">
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Customer</span>
                    <span className="font-medium">{customerName}</span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Location</span>
                    <span className="font-medium">
                        {location?.name ?? '—'}
                    </span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Services</span>
                    <span className="text-right font-medium">
                        {selectedServices.map((s) => s.name).join(', ')}
                    </span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Date & Time</span>
                    <span className="font-medium">
                        {formattedDate} at {selectedTime}
                    </span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Duration</span>
                    <span className="font-medium">{totalDuration} min</span>
                </div>
                <div className="flex justify-between">
                    <span className="text-muted-foreground">Total</span>
                    <span className="font-medium">
                        £{totalPrice.toFixed(2)}
                    </span>
                </div>
            </div>

            <div className="grid gap-3">
                <div className="grid gap-1.5">
                    <Label htmlFor="pet-name">Pet Name</Label>
                    <Input
                        id="pet-name"
                        value={petName}
                        onChange={(e) => onUpdatePetName(e.target.value)}
                        placeholder="e.g. Buddy"
                        aria-invalid={!!errors.pet_name}
                    />
                    {errors.pet_name && (
                        <p className="text-destructive text-xs">
                            {errors.pet_name}
                        </p>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div className="grid gap-1.5">
                        <Label htmlFor="pet-breed">Breed (optional)</Label>
                        <Input
                            id="pet-breed"
                            value={petBreed}
                            onChange={(e) => onUpdatePetBreed(e.target.value)}
                            placeholder="e.g. Labrador"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="pet-size">Size (optional)</Label>
                        <Select value={petSize} onValueChange={onUpdatePetSize}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select size" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="small">Small</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="large">Large</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="notes">Notes (optional)</Label>
                    <Textarea
                        id="notes"
                        value={notes}
                        onChange={(e) => onUpdateNotes(e.target.value)}
                        placeholder="Any special instructions..."
                        rows={2}
                    />
                </div>
            </div>
        </div>
    );
}
