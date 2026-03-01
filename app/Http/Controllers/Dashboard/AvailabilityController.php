<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreAvailabilityBlockRequest;
use App\Http\Requests\Dashboard\UpdateAvailabilityBlockRequest;
use App\Models\AvailabilityBlock;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $blocks = $business->availabilityBlocks()
            ->orderBy('start_time')
            ->get();

        $weeklyBlocks = $blocks->whereNull('specific_date')
            ->groupBy('day_of_week')
            ->map(fn ($dayBlocks) => $dayBlocks->map(fn (AvailabilityBlock $block) => $this->formatBlock($block))->values())
            ->toArray();

        $specificBlocks = $blocks->whereNotNull('specific_date')
            ->where('specific_date', '>=', now()->startOfDay())
            ->sortBy('specific_date')
            ->map(fn (AvailabilityBlock $block) => $this->formatBlock($block))
            ->values()
            ->toArray();

        return Inertia::render('dashboard/availability/index', [
            'weeklyBlocks' => $weeklyBlocks,
            'specificBlocks' => $specificBlocks,
        ]);
    }

    public function store(StoreAvailabilityBlockRequest $request): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $data = $request->validated();

        if (isset($data['day_of_week'])) {
            $data['repeat_weekly'] = true;
            $data['specific_date'] = null;
        } else {
            $data['repeat_weekly'] = false;
            $data['day_of_week'] = null;
        }

        $business->availabilityBlocks()->create($data);

        return back()->with('success', 'Availability block added.');
    }

    public function update(UpdateAvailabilityBlockRequest $request, string $handle, int $availabilityBlock): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $block = $business->availabilityBlocks()->findOrFail($availabilityBlock);

        $data = $request->validated();

        if (isset($data['day_of_week'])) {
            $data['repeat_weekly'] = true;
            $data['specific_date'] = null;
        } else {
            $data['repeat_weekly'] = false;
            $data['day_of_week'] = null;
        }

        $block->update($data);

        return back()->with('success', 'Availability block updated.');
    }

    public function destroy(Request $request, string $handle, int $availabilityBlock): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $block = $business->availabilityBlocks()->findOrFail($availabilityBlock);
        $block->delete();

        return back()->with('success', 'Availability block deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBlock(AvailabilityBlock $block): array
    {
        return [
            'id' => $block->id,
            'day_of_week' => $block->day_of_week,
            'start_time' => $block->start_time,
            'end_time' => $block->end_time,
            'specific_date' => $block->specific_date?->toDateString(),
            'block_type' => $block->block_type,
            'repeat_weekly' => $block->repeat_weekly,
            'notes' => $block->notes,
        ];
    }
}
