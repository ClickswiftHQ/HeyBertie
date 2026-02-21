<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessPageView;
use App\Models\Location;
use Illuminate\Http\Request;

class PageViewService
{
    /**
     * Track a page view, deduplicating same IP + business within 30 minutes.
     */
    public function trackView(Business $business, ?Location $location, Request $request): void
    {
        $ipAddress = $request->ip();

        if ($this->isDuplicate($business, $ipAddress)) {
            return;
        }

        BusinessPageView::query()->create([
            'business_id' => $business->id,
            'location_id' => $location?->id,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'source' => $this->detectSource($request->header('referer')),
            'viewed_at' => now(),
        ]);
    }

    /**
     * Detect the traffic source from the referrer header.
     */
    public function detectSource(?string $referrer): string
    {
        if (empty($referrer)) {
            return 'direct';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! $host) {
            return 'direct';
        }

        $host = strtolower($host);

        $searchEngines = ['google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex'];
        foreach ($searchEngines as $engine) {
            if (str_contains($host, $engine)) {
                return 'search';
            }
        }

        $socialNetworks = ['facebook', 'instagram', 'twitter', 'x.com', 'linkedin', 'tiktok', 'pinterest'];
        foreach ($socialNetworks as $network) {
            if (str_contains($host, $network)) {
                return 'social';
            }
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && str_contains($host, $appHost)) {
            return 'internal';
        }

        return 'referral';
    }

    private function isDuplicate(Business $business, ?string $ipAddress): bool
    {
        if (! $ipAddress) {
            return false;
        }

        return BusinessPageView::query()
            ->where('business_id', $business->id)
            ->where('ip_address', $ipAddress)
            ->where('viewed_at', '>=', now()->subMinutes(30))
            ->exists();
    }
}
