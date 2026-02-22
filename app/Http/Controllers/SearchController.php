<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\GeocodingService;
use App\Services\SchemaMarkupService;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private GeocodingService $geocodingService,
        private SchemaMarkupService $schemaMarkupService,
    ) {}

    public function index(SearchRequest $request): View|RedirectResponse
    {
        $locationInput = $request->validated('location');
        $service = $request->validated('service', 'dog-grooming');
        $serviceName = $this->searchService->serviceNames()[$service] ?? 'Dog Grooming';

        // Redirect to SEO landing page if the location matches a known slug
        $locationSlug = str($locationInput)->slug()->value();
        $resolved = $this->searchService->resolveLocation($locationSlug);

        if ($resolved && isset($this->searchService->serviceNames()[$service])) {
            $landingSlug = $service.'-in-'.$locationSlug;
            $filters = array_filter($request->only(['sort', 'rating', 'distance', 'type']));

            return redirect()->to('/'.$landingSlug.($filters ? '?'.http_build_query($filters) : ''));
        }

        $coords = $this->geocodingService->geocode($locationInput);

        if (! $coords) {
            return view('search.results', [
                'results' => null,
                'location' => $locationInput,
                'service' => $service,
                'serviceName' => $serviceName,
                'coordinates' => null,
                'filters' => $request->only(['sort', 'rating', 'distance', 'type']),
                'sort' => $request->validated('sort', 'distance'),
                'totalResults' => 0,
                'isLandingPage' => false,
                'geocodingFailed' => true,
                'schemaMarkup' => null,
                'canonicalUrl' => null,
                'metaTitle' => $serviceName.' near '.$locationInput.' | heyBertie',
                'metaDescription' => null,
            ]);
        }

        $filters = array_filter($request->only(['sort', 'rating', 'distance', 'type']));
        $results = $this->searchService->search($coords['latitude'], $coords['longitude'], $filters);

        return view('search.results', [
            'results' => $results,
            'location' => $locationInput,
            'service' => $service,
            'serviceName' => $serviceName,
            'coordinates' => $coords,
            'filters' => $request->only(['sort', 'rating', 'distance', 'type']),
            'sort' => $request->validated('sort', 'distance'),
            'totalResults' => $results->total(),
            'isLandingPage' => false,
            'geocodingFailed' => false,
            'schemaMarkup' => null,
            'canonicalUrl' => null,
            'metaTitle' => $serviceName.' near '.$locationInput.' | heyBertie',
            'metaDescription' => null,
        ]);
    }

    public function landing(string $slug): View
    {
        $lastInPos = strrpos($slug, '-in-');

        if ($lastInPos === false) {
            abort(404);
        }

        $serviceSlug = substr($slug, 0, $lastInPos);
        $locationSlug = substr($slug, $lastInPos + 4);

        $services = $this->searchService->serviceNames();
        $serviceName = $services[$serviceSlug] ?? null;

        if (! $serviceName) {
            abort(404);
        }

        $location = $this->searchService->resolveLocation($locationSlug);

        if (! $location) {
            abort(404);
        }

        $filters = array_filter(request()->only(['sort', 'rating', 'distance', 'type']));
        $results = $this->searchService->search($location['latitude'], $location['longitude'], $filters);

        $canonicalUrl = url('/'.$slug);

        $schemaData = $this->schemaMarkupService->generateForSearchResults(
            $serviceName.' in '.$location['name'],
            $canonicalUrl,
            $results,
        );
        $schemaMarkup = $this->schemaMarkupService->toJsonLd($schemaData);

        return view('search.results', [
            'results' => $results,
            'location' => $location['name'],
            'service' => $serviceSlug,
            'serviceName' => $serviceName,
            'coordinates' => ['latitude' => $location['latitude'], 'longitude' => $location['longitude']],
            'filters' => request()->only(['sort', 'rating', 'distance', 'type']),
            'sort' => request()->get('sort', 'distance'),
            'totalResults' => $results->total(),
            'isLandingPage' => true,
            'geocodingFailed' => false,
            'schemaMarkup' => $schemaMarkup,
            'canonicalUrl' => $canonicalUrl,
            'metaTitle' => $serviceName.' in '.$location['name'].' - Find & Book | heyBertie',
            'metaDescription' => 'Compare '.$results->total().' trusted '.strtolower($serviceName).'s in '.$location['name'].'. Read verified reviews, check prices, and book online.',
        ]);
    }
}
