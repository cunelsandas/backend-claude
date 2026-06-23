<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $start = $request->get('start', $today);
        $end   = $request->get('end',   $today);

        // Clamp to valid dates
        try {
            $startDate = Carbon::parse($start)->toDateString();
            $endDate   = Carbon::parse($end)->toDateString();
        } catch (\Throwable) {
            $startDate = $endDate = $today;
        }

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $startFull = $startDate . ' 00:00:00';
        $endFull   = $endDate   . ' 23:59:59';

        $user = Auth::user();

        return Inertia::render('Dashboard/Index', [
            // Returned immediately — page shell renders without waiting for DB
            'dateRange' => ['start' => $startDate, 'end' => $endDate],

            // Deferred — fetched in a follow-up background request after the page loads
            'kpis'          => Inertia::defer(fn () => $this->service->getKpis($startFull, $endFull)),
            'section2'      => Inertia::defer(fn () => $this->service->getSection2()),
            'saleRank'      => Inertia::defer(fn () => $this->service->getSaleRank($startFull, $endFull, $user)),
            'bestSale'      => Inertia::defer(fn () => $this->service->getBestSale($startFull, $endFull)),
            'commissions'   => Inertia::defer(fn () => $this->service->getCommissions($startFull, $endFull)),
            'brandSale'     => Inertia::defer(fn () => $this->service->getBrandSale($startFull, $endFull)),
            'shipping'      => Inertia::defer(fn () => $this->service->getShipping($startDate, $endDate)),
            'customerCount' => Inertia::defer(fn () => $this->service->getCustomerCount()),
            'newCustomers'  => Inertia::defer(fn () => $this->service->getNewCustomers($startDate, $endDate)),
            'charts'        => Inertia::defer(fn () => [
                'daily'     => $this->service->getDailyChart($startFull, $endFull),
                'dayOfWeek' => $this->service->getDayOfWeekChart($startFull, $endFull),
            ]),
        ]);
    }
}
