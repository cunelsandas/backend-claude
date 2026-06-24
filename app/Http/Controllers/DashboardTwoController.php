<?php

namespace App\Http\Controllers;

use App\Services\DashboardTwoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardTwoController extends Controller
{
    public function __construct(private DashboardTwoService $service) {}

    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $year  = (int) $request->get('year', date('Y'));

        // Date range for pie / count / marketplace widgets (defaults to current month)
        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end',   $today);

        try { $start = Carbon::parse($start)->toDateString(); } catch (\Throwable) { $start = $today; }
        try { $end   = Carbon::parse($end)->toDateString();   } catch (\Throwable) { $end   = $today; }
        if ($start > $end) [$start, $end] = [$end, $start];

        $startFull = $start . ' 00:00:00';
        $endFull   = $end   . ' 23:59:59';

        return Inertia::render('DashboardTwo/Index', [
            'dateRange' => ['start' => $start, 'end' => $end],
            'year'      => $year,

            'salesChart'   => Inertia::defer(fn () => $this->service->getSalesChart($year)),
            'ordersChart'  => Inertia::defer(fn () => $this->service->getOrdersChart($year)),
            'profitPie'    => Inertia::defer(fn () => $this->service->getProfitPie($startFull, $endFull)),
            'retailCount'  => Inertia::defer(fn () => $this->service->getRetailCount($startFull, $endFull)),
            'marketplace'  => Inertia::defer(fn () => $this->service->getMarketplace($startFull, $endFull)),
            'salesTrend'   => Inertia::defer(fn () => $this->service->getMonthlySalesTrend(24)),
        ]);
    }

    // ── API: Retail customer table ────────────────────────────────────────────
    public function retailCustomers(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end',   $today);

        return response()->json(
            $this->service->getRetailCustomers($start . ' 00:00:00', $end . ' 23:59:59')
        );
    }

    // ── API: Wholesale customer table ─────────────────────────────────────────
    public function wholesaleCustomers(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end',   $today);

        return response()->json(
            $this->service->getWholesaleCustomers($start . ' 00:00:00', $end . ' 23:59:59')
        );
    }
}
