<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Business $business */
        $business = $request->attributes->get('currentBusiness');

        $query = $business->customers();

        $status = $request->query('status');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $search = $request->query('search', '');
        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderByDesc('last_visit')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'total_bookings' => $customer->total_bookings,
                'total_spent' => $customer->total_spent,
                'last_visit' => $customer->last_visit?->toIso8601String(),
                'is_active' => $customer->is_active,
            ]);

        return Inertia::render('dashboard/customers/index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status ?? 'all',
            ],
        ]);
    }
}
