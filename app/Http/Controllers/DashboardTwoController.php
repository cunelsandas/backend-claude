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
        $month = (int) $request->get('month', date('n'));

        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end',   $today);

        try { $start = Carbon::parse($start)->toDateString(); } catch (\Throwable) { $start = $today; }
        try { $end   = Carbon::parse($end)->toDateString();   } catch (\Throwable) { $end   = $today; }
        if ($start > $end) [$start, $end] = [$end, $start];

        $startFull = $start . ' 00:00:00';
        $endFull   = $end   . ' 23:59:59';

        // Selector lookups — fetched eagerly (small lists, used by the UI selectors)
        $categories = $this->service->getCategories();
        $catId      = (int) $request->get('category_id', $categories[0]->id ?? 0) ?: null;

        return Inertia::render('DashboardTwo/Index', [
            'dateRange'  => ['start' => $start, 'end' => $end],
            'year'       => $year,
            'month'      => $month,
            'categoryId' => $catId,
            'categories' => $categories,

            // Charts (year-driven)
            'salesChart'         => Inertia::defer(fn () => $this->service->getSalesChart($year)),
            'ordersChart'        => Inertia::defer(fn () => $this->service->getOrdersChart($year)),
            'costRevenue'        => Inertia::defer(fn () => $this->service->getCostRevenueSummary($year)),
            'productByCategory'  => Inertia::defer(fn () => $this->service->getProductSalesByCategory($year, $catId)),

            // Pies / KPI (date-range-driven)
            'profitPie'    => Inertia::defer(fn () => $this->service->getProfitPie($startFull, $endFull)),
            'countrySplit' => Inertia::defer(fn () => $this->service->getCountrySplit($startFull, $endFull)),
            'csProfit'     => Inertia::defer(fn () => $this->service->getCSProfit($startFull, $endFull)),
            'retailCount'  => Inertia::defer(fn () => $this->service->getRetailCount($startFull, $endFull)),
            'marketplace'  => Inertia::defer(fn () => $this->service->getMarketplace($startFull, $endFull)),

            // Category pies (year / year+month)
            'yearByCategory'  => Inertia::defer(fn () => $this->service->getYearSalesByCategory($year)),
            'monthByCategory' => Inertia::defer(fn () => $this->service->getMonthSalesByCategory($year, $month)),

            // Long-term tables (5-year rolling)
            'salesTrend'    => Inertia::defer(fn () => $this->service->getMonthlySalesTrend(24)),
            'monthlyMatrix' => Inertia::defer(fn () => $this->service->getMonthlySalesYearMajor(5)),
            'quarterly'     => Inertia::defer(fn () => $this->service->getQuarterlySales(5)),
            'grossProfit'   => Inertia::defer(fn () => $this->service->getMonthlyGrossProfit(5)),
            'netProfit'     => Inertia::defer(fn () => $this->service->getMonthlyNetProfit(5)),
        ]);
    }

    // ── API: Retail / Wholesale customer tables ───────────────────────────────
    public function retailCustomers(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $start = $request->get('start', Carbon::now()->startOfMonth()->toDateString());
        $end   = $request->get('end',   $today);
        return response()->json(
            $this->service->getRetailCustomers($start . ' 00:00:00', $end . ' 23:59:59')
        );
    }

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
