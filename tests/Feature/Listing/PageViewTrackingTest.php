<?php

use App\Models\Business;
use App\Models\BusinessPageView;
use App\Models\Location;

test('page view is recorded on listing visit', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/'.$location->slug);

    expect(BusinessPageView::where('business_id', $business->id)->count())->toBe(1);
});

test('duplicate views are deduplicated within 30 minutes', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->get('/'.$business->handle.'/'.$location->slug);
    $this->get('/'.$business->handle.'/'.$location->slug);

    expect(BusinessPageView::where('business_id', $business->id)->count())->toBe(1);
});

test('different IPs create separate views', function () {
    $business = Business::factory()->completed()->create();
    $location = Location::factory()->create(['business_id' => $business->id]);

    $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
        ->get('/'.$business->handle.'/'.$location->slug);

    $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.8'])
        ->get('/'.$business->handle.'/'.$location->slug);

    expect(BusinessPageView::where('business_id', $business->id)->count())->toBe(2);
});
