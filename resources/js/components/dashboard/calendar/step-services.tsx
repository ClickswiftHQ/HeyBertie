import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ManualBookingLocation, ManualBookingService } from '@/types';

type StepServicesProps = {
    locations: ManualBookingLocation[];
    services: ManualBookingService[];
    selectedLocationId: number | null;
    selectedServiceIds: number[];
    onSelectLocation: (locationId: number) => void;
    onToggleService: (serviceId: number) => void;
    errors: Record<string, string>;
};

export function StepServices({
    locations,
    services,
    selectedLocationId,
    selectedServiceIds,
    onSelectLocation,
    onToggleService,
    errors,
}: StepServicesProps) {
    const filteredServices = selectedLocationId
        ? services.filter(
              (s) =>
                  s.location_id === null ||
                  s.location_id === selectedLocationId,
          )
        : services;

    const selectedServices = filteredServices.filter((s) =>
        selectedServiceIds.includes(s.id),
    );
    const totalDuration = selectedServices.reduce(
        (sum, s) => sum + s.duration_minutes,
        0,
    );
    const totalPrice = selectedServices.reduce(
        (sum, s) => sum + (parseFloat(s.price ?? '0') || 0),
        0,
    );

    return (
        <div className="space-y-4">
            {locations.length > 1 && (
                <div className="grid gap-1.5">
                    <Label>Location</Label>
                    <Select
                        value={selectedLocationId?.toString() ?? ''}
                        onValueChange={(val) => onSelectLocation(parseInt(val))}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select location" />
                        </SelectTrigger>
                        <SelectContent>
                            {locations.map((loc) => (
                                <SelectItem
                                    key={loc.id}
                                    value={loc.id.toString()}
                                >
                                    {loc.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.location_id && (
                        <p className="text-destructive text-xs">
                            {errors.location_id}
                        </p>
                    )}
                </div>
            )}

            <div className="grid gap-1.5">
                <Label>Services</Label>
                {errors.service_ids && (
                    <p className="text-destructive text-xs">
                        {errors.service_ids}
                    </p>
                )}

                <div className="max-h-56 space-y-2 overflow-y-auto">
                    {filteredServices.length === 0 && (
                        <p className="text-muted-foreground py-2 text-center text-sm">
                            {selectedLocationId
                                ? 'No active services for this location.'
                                : 'Select a location first.'}
                        </p>
                    )}

                    {filteredServices.map((service) => (
                        <label
                            key={service.id}
                            className={`hover:bg-accent flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors ${
                                selectedServiceIds.includes(service.id)
                                    ? 'border-primary bg-primary/5'
                                    : ''
                            }`}
                        >
                            <Checkbox
                                checked={selectedServiceIds.includes(
                                    service.id,
                                )}
                                onCheckedChange={() =>
                                    onToggleService(service.id)
                                }
                                className="mt-0.5"
                            />
                            <div className="flex-1">
                                <div className="text-sm font-medium">
                                    {service.name}
                                </div>
                                <div className="text-muted-foreground text-xs">
                                    {service.duration_minutes} min ·{' '}
                                    {service.formatted_price}
                                </div>
                            </div>
                        </label>
                    ))}
                </div>
            </div>

            {selectedServices.length > 0 && (
                <div className="bg-muted rounded-md p-3">
                    <div className="flex justify-between text-sm">
                        <span className="text-muted-foreground">
                            {selectedServices.length} service
                            {selectedServices.length !== 1 ? 's' : ''} ·{' '}
                            {totalDuration} min
                        </span>
                        <span className="font-medium">
                            £{totalPrice.toFixed(2)}
                        </span>
                    </div>
                </div>
            )}
        </div>
    );
}
