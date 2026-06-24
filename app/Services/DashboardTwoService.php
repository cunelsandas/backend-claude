<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardTwoService
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mergeMonthlyData(array $map, $rows, string $col = 'total'): array
    {
        foreach ($rows as $r) {
            $key = $r->year . '-' . str_pad($r->month, 2, '0', STR_PAD_LEFT);
            $map[$key] = ($map[$key] ?? 0) + (float) $r->$col;
        }
        return $map;
    }

    // ── Monthly Sales Chart (full year, 8 series) ─────────────────────────────
    public function getSalesChart(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth()->toDateTimeString();
            $end   = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay()->toDateTimeString();

            $retail = DB::table('sma_sales')
                ->whereBetween('date', [$start, $end])
                ->where('bill_vip', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('payment_status', 'paid')
                ->where('sell_by_company', 0)
                ->where('payment_method', '!=', 'CREDIT')
                ->sum('grand_total');

            $wholesale = DB::table('sma_sales')
                ->whereBetween('date', [$start, $end])
                ->where('payment_method', '!=', 'CREDIT')
                ->where('online', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
                ->whereNotIn('special_tag', [10])
                ->where('bill_type', 'VIP')
                ->where('shop_id', '!=', 22)
                ->where('payment_status', 'paid')
                ->where('sell_by_company', 0)
                ->sum('grand_total');

            $creditCurrent = DB::table('sma_sales')
                ->where('month', $m)
                ->whereBetween('credit_pay_date', [$start, $end])
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
                ->where('payment_method', 'CREDIT')
                ->where('payment_status', 'paid')
                ->where('sell_by_company', 0)
                ->sum('grand_total');

            $creditPast = DB::table('sma_sales')
                ->where('month', '!=', $m)
                ->whereBetween('credit_pay_date', [$start, $end])
                ->where('payment_method', 'CREDIT')
                ->where('payment_status', 'paid')
                ->where('sell_by_company', 0)
                ->sum('grand_total');

            $shopee = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Shopee')
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->sum('total');

            $lazada = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Lazada')
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->sum('total');

            $tiktok = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Tiktok')
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->sum('total');

            $rows[] = [
                'month'          => Carbon::create($year, $m)->format('M'),
                'retail'         => (float) $retail,
                'wholesale'      => (float) $wholesale,
                'credit_current' => (float) $creditCurrent,
                'credit_past'    => (float) $creditPast,
                'marketplace'    => (float) ($shopee + $lazada + $tiktok),
                'shopee'         => (float) $shopee,
                'lazada'         => (float) $lazada,
                'tiktok'         => (float) $tiktok,
            ];
        }
        return $rows;
    }

    // ── Monthly Orders Chart (full year, 3 series) ────────────────────────────
    public function getOrdersChart(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $retail = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('bill_vip', 0)->count();

            $wholesale = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('bill_vip', 1)->count();

            $marketplace = DB::table('sma_sales')
                ->whereYear('date', $year)->whereMonth('date', $m)
                ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->count();

            $rows[] = [
                'month'       => Carbon::create($year, $m)->format('M'),
                'retail'      => $retail,
                'wholesale'   => $wholesale,
                'marketplace' => $marketplace,
            ];
        }
        return $rows;
    }

    // ── Profit Pie (date range) ───────────────────────────────────────────────
    public function getProfitPie(string $start, string $end): array
    {
        $month = Carbon::parse($start)->month;

        $retail = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('bill_vip', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->where('payment_method', '!=', 'CREDIT')
            ->sum('grand_total');

        $retailCreditNow = (float) DB::table('sma_sales')
            ->where('month', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
            ->where('payment_method', 'CREDIT')->where('bill_vip', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('grand_total');

        $retailCreditPast = (float) DB::table('sma_sales')
            ->where('month', '!=', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->where('payment_method', 'CREDIT')->where('bill_vip', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('grand_total');

        $wholesale = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('payment_method', '!=', 'CREDIT')->where('online', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
            ->whereNotIn('special_tag', [10])->where('bill_type', 'VIP')
            ->where('shop_id', '!=', 22)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        $wholesaleCreditNow = (float) DB::table('sma_sales')
            ->where('month', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
            ->where('payment_method', 'CREDIT')->where('bill_type', 'VIP')
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('grand_total');

        $wholesaleCreditPast = (float) DB::table('sma_sales')
            ->where('month', '!=', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->where('payment_method', 'CREDIT')->where('bill_type', 'VIP')
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('grand_total');

        $marketplace = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('total');

        $online = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('online', 1)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        return [
            ['name' => 'Retail',      'value' => $retail + $retailCreditNow + $retailCreditPast],
            ['name' => 'Wholesale',   'value' => $wholesale + $wholesaleCreditNow + $wholesaleCreditPast],
            ['name' => 'Marketplace', 'value' => $marketplace + $online],
        ];
    }

    // ── Retail Customer Count ─────────────────────────────────────────────────
    public function getRetailCount(string $start, string $end): int
    {
        return DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('bill_vip', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->where('payment_method', '!=', 'CREDIT')
            ->whereNotIn('special_tag', [10])
            ->distinct('customer_id')->count('customer_id');
    }

    // ── Marketplace Platform Breakdown ────────────────────────────────────────
    public function getMarketplace(string $start, string $end): array
    {
        $q = fn (string $method) => (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('shop_method', $method)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->sum('total');

        $shopee = $q('Shopee');
        $lazada = $q('Lazada');

        $tiktokBase = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])->where('shop_method', 'Tiktok')
            ->where('from_live', 0)->where('tiktok_video', 0)
            ->where('affiliate_commission', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tiktokLive = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])->where('shop_method', 'Tiktok')
            ->where('from_live', 1)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tiktokAffiliate = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])->where('shop_method', 'Tiktok')
            ->where('affiliate_commission', '>', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tiktokVideo = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])->where('shop_method', 'Tiktok')
            ->where('tiktok_video', 1)->where('from_live', 0)
            ->where('affiliate_commission', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $online = (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('online', 1)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        $tiktokTotal = $tiktokBase + $tiktokLive + $tiktokAffiliate + $tiktokVideo;

        return [
            'shopee'           => $shopee,
            'lazada'           => $lazada,
            'tiktok'           => $tiktokTotal,
            'tiktok_base'      => $tiktokBase,
            'tiktok_live'      => $tiktokLive,
            'tiktok_affiliate' => $tiktokAffiliate,
            'tiktok_video'     => $tiktokVideo,
            'online'           => $online,
            'total'            => $shopee + $lazada + $tiktokTotal + $online,
        ];
    }

    // ── Monthly Sales Trend (last N months, with MoM & YoY %) ────────────────
    public function getMonthlySalesTrend(int $months = 24): array
    {
        $since = (int) Carbon::now()->subYears(5)->format('Y');
        $data  = [];

        $streams = [
            // [table, date_col, amount_col, extra_filters]
            ['sma_sales', 'date',          'grand_total', fn ($q) => $q->where('bill_vip', 0)->where('payment_status', 'paid')->where('payment_method', '!=', 'CREDIT')->where('sell_by_company', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['sma_sales', 'credit_pay_date','grand_total', fn ($q) => $q->where('bill_vip', 0)->where('payment_status', 'paid')->where('payment_method', 'CREDIT')->where('sell_by_company', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['sma_sales', 'date',          'grand_total', fn ($q) => $q->where('bill_vip', 1)->where('payment_status', 'paid')->where('payment_method', '!=', 'CREDIT')->where('sell_by_company', 0)->where('special_tag', '!=', 10)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['sma_sales', 'credit_pay_date','grand_total', fn ($q) => $q->where('bill_vip', 1)->where('payment_status', 'paid')->where('payment_method', 'CREDIT')->where('sell_by_company', 0)],
            ['sma_sales', 'date',          'total',       fn ($q) => $q->where('payment_status', 'paid')->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])->where('sell_by_company', 0)],
            ['sma_sales', 'date',          'grand_total', fn ($q) => $q->where('payment_status', 'paid')->where('online', 1)->where('sell_by_company', 0)],
        ];

        foreach ($streams as [$table, $dateCol, $amountCol, $filter]) {
            $rows = $filter(DB::table($table)
                ->selectRaw("YEAR($dateCol) as year, MONTH($dateCol) as month, SUM($amountCol) as total")
                ->whereYear($dateCol, '>=', $since))
                ->groupByRaw("YEAR($dateCol), MONTH($dateCol)")
                ->get();
            $data = $this->mergeMonthlyData($data, $rows);
        }

        ksort($data);
        $keys   = array_keys($data);
        $result = [];

        foreach ($keys as $i => $key) {
            $total = $data[$key];

            $prevKey  = $i > 0 ? $keys[$i - 1] : null;
            $momPct   = ($prevKey && $data[$prevKey] > 0)
                ? round((($total - $data[$prevKey]) / $data[$prevKey]) * 100, 1)
                : null;

            [$y, $mo] = explode('-', $key);
            $yoyKey   = ($y - 1) . '-' . $mo;
            $yoyPct   = (isset($data[$yoyKey]) && $data[$yoyKey] > 0)
                ? round((($total - $data[$yoyKey]) / $data[$yoyKey]) * 100, 1)
                : null;

            $result[] = ['month' => $key, 'total' => (float) $total, 'mom_pct' => $momPct, 'yoy_pct' => $yoyPct];
        }

        return array_values(array_slice($result, -$months));
    }

    // ── Retail Customer Table (for API endpoint) ──────────────────────────────
    public function getRetailCustomers(string $start, string $end): array
    {
        $yearStart = Carbon::parse($start)->startOfYear()->toDateString();
        $yearEnd   = Carbon::parse($end)->endOfYear()->toDateString();

        // Non-credit retail in filter range
        $rows = DB::table('sma_sales as s')
            ->join('sma_companies as c', 'c.id', '=', 's.customer_id')
            ->selectRaw('s.customer_id, c.name as customer_name, c.phone as customer_phone,
                SUM(s.grand_total) as filter_total, COUNT(s.id) as filter_orders')
            ->whereBetween('s.date', [$start, $end])
            ->where('s.bill_vip', 0)
            ->whereNotIn('s.shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('s.payment_status', 'paid')->where('s.sell_by_company', 0)
            ->where('s.payment_method', '!=', 'CREDIT')
            ->whereNotIn('s.special_tag', [10])
            ->groupBy('s.customer_id', 'c.name', 'c.phone')
            ->orderByDesc('filter_total')
            ->get();

        // Year totals for same customers
        $ids = $rows->pluck('customer_id')->toArray();
        if (empty($ids)) return [];

        $yearTotals = DB::table('sma_sales')
            ->whereIn('customer_id', $ids)
            ->whereBetween('date', [$yearStart . ' 00:00:00', $yearEnd . ' 23:59:59'])
            ->where('bill_vip', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->whereNotIn('special_tag', [10])
            ->selectRaw('customer_id, SUM(grand_total) as year_total')
            ->groupBy('customer_id')
            ->pluck('year_total', 'customer_id');

        return $rows->filter(fn ($r) => (float) $r->filter_total >= 500)
            ->map(fn ($r) => [
                'customer_id'    => $r->customer_id,
                'customer_name'  => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'filter_total'   => (float) $r->filter_total,
                'filter_orders'  => (int) $r->filter_orders,
                'year_total'     => (float) ($yearTotals[$r->customer_id] ?? 0),
            ])
            ->values()->toArray();
    }

    // ── Wholesale Customer Table (for API endpoint) ───────────────────────────
    public function getWholesaleCustomers(string $start, string $end): array
    {
        $yearStart = Carbon::parse($start)->startOfYear()->toDateString();
        $yearEnd   = Carbon::parse($end)->endOfYear()->toDateString();

        $rows = DB::table('sma_sales as s')
            ->join('sma_companies as c', 'c.id', '=', 's.customer_id')
            ->selectRaw('s.customer_id, c.name as customer_name, c.phone as customer_phone,
                SUM(s.grand_total) as filter_total, COUNT(s.id) as filter_orders')
            ->whereBetween('s.date', [$start, $end])
            ->where('s.bill_vip', 1)
            ->whereNotIn('s.shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
            ->whereNotIn('s.special_tag', [10])
            ->where('s.shop_id', '!=', 22)
            ->where('s.payment_status', 'paid')->where('s.sell_by_company', 0)
            ->where('s.payment_method', '!=', 'CREDIT')
            ->groupBy('s.customer_id', 'c.name', 'c.phone')
            ->orderByDesc('filter_total')
            ->get();

        $ids = $rows->pluck('customer_id')->toArray();
        if (empty($ids)) return [];

        $yearTotals = DB::table('sma_sales')
            ->whereIn('customer_id', $ids)
            ->whereBetween('date', [$yearStart . ' 00:00:00', $yearEnd . ' 23:59:59'])
            ->where('bill_vip', 1)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
            ->whereNotIn('special_tag', [10])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->selectRaw('customer_id, SUM(grand_total) as year_total')
            ->groupBy('customer_id')
            ->pluck('year_total', 'customer_id');

        return $rows->filter(fn ($r) => (float) $r->filter_total >= 10000)
            ->map(fn ($r) => [
                'customer_id'    => $r->customer_id,
                'customer_name'  => $r->customer_name,
                'customer_phone' => $r->customer_phone,
                'filter_total'   => (float) $r->filter_total,
                'filter_orders'  => (int) $r->filter_orders,
                'year_total'     => (float) ($yearTotals[$r->customer_id] ?? 0),
            ])
            ->values()->toArray();
    }
}
