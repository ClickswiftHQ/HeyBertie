<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MarketingController extends Controller
{
    public function forDogGroomers(): View
    {
        return view('marketing.for-dog-groomers', [
            'metaTitle' => 'Grow Your Dog Grooming Business | heyBertie',
            'metaDescription' => 'Join heyBertie to get discovered by local pet owners, manage bookings, and grow your dog grooming business with powerful online tools.',
            'canonicalUrl' => route('marketing.for-dog-groomers'),
        ]);
    }
}
