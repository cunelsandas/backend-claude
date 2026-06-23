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
            'dateRange'     => ['start' => $startDate, 'end' => $endDate],
            'kpis'          => $this->service->getKpis($startFull, $endFull),
            'section2'      => $this->service->getSection2(),
            'saleRank'      => $this->service->getSaleRank($startFull, $endFull, $user),
            'bestSale'      => $this->service->getBestSale($startFull, $endFull),
            'commissions'   => $this->service->getCommissions($startFull, $endFull),
            'brandSale'     => $this->service->getBrandSale($startFull, $endFull),
            'shipping'      => $this->service->getShipping($startDate, $endDate),
            'customerCount' => $this->service->getCustomerCount(),
            'newCustomers'  => $this->service->getNewCustomers($startDate, $endDate),
            'charts'        => [
                'daily'      => $this->service->getDailyChart($startFull, $endFull),
                'dayOfWeek'  => $this->service->getDayOfWeekChart($startFull, $endFull),
            ],
        ]);
    }
}
