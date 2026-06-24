<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardTwoService
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function mergeMonthlyData(array $map, $rows, string $col = 'total'): array
    {
        foreach ($rows as $r) {
            $key = $r->year . '-' . str_pad($r->month, 2, '0', STR_PAD_LEFT);
            $map[$key] = ($map[$key] ?? 0) + (float) $r->$col;
        }
        return $map;
    }

    /**
     * Build a {YYYY-MM => totalRevenue} map by merging all 6 revenue streams
     * (retail, retail-credit, wholesale, wholesale-credit, marketplace, online).
     * Used by Trend / Year-Major / Quarterly views to avoid duplicate queries.
     */
    private function buildRevenueMap(int $sinceYear): array
    {
        $data    = [];
        $streams = [
            ['date',           'grand_total', fn ($q) => $q->where('bill_vip', 0)->where('payment_status', 'paid')->where('payment_method', '!=', 'CREDIT')->where('sell_by_company', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['credit_pay_date','grand_total', fn ($q) => $q->where('bill_vip', 0)->where('payment_status', 'paid')->where('payment_method', 'CREDIT')->where('sell_by_company', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['date',           'grand_total', fn ($q) => $q->where('bill_vip', 1)->where('payment_status', 'paid')->where('payment_method', '!=', 'CREDIT')->where('sell_by_company', 0)->where('special_tag', '!=', 10)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])],
            ['credit_pay_date','grand_total', fn ($q) => $q->where('bill_vip', 1)->where('payment_status', 'paid')->where('payment_method', 'CREDIT')->where('sell_by_company', 0)],
            ['date',           'total',       fn ($q) => $q->where('payment_status', 'paid')->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])->where('sell_by_company', 0)],
            ['date',           'grand_total', fn ($q) => $q->where('payment_status', 'paid')->where('online', 1)->where('sell_by_company', 0)],
        ];

        foreach ($streams as [$dateCol, $amountCol, $filter]) {
            $rows = $filter(DB::table('sma_sales')
                ->selectRaw("YEAR($dateCol) as year, MONTH($dateCol) as month, SUM($amountCol) as total")
                ->whereYear($dateCol, '>=', $sinceYear))
                ->groupByRaw("YEAR($dateCol), MONTH($dateCol)")
                ->get();
            $data = $this->mergeMonthlyData($data, $rows);
        }
        ksort($data);
        return $data;
    }

    private function pctChange($current, $previous): ?float
    {
        $cur = (float) ($current ?? 0);
        $prv = (float) ($previous ?? 0);
        if ($prv == 0.0) return $cur > 0 ? 100.0 : null;
        return round((($cur - $prv) / abs($prv)) * 100, 1);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Monthly Sales Chart  (ยอดขายรายเดือนแยกตามปี — line, 8 series)
    |--------------------------------------------------------------------------
    */
    public function getSalesChart(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth()->toDateTimeString();
            $end   = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay()->toDateTimeString();

            $retail = DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('bill_vip', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('payment_method', '!=', 'CREDIT')
                ->sum('grand_total');

            $wholesale = DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('payment_method', '!=', 'CREDIT')->where('online', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
                ->whereNotIn('special_tag', [10])->where('bill_type', 'VIP')
                ->where('shop_id', '!=', 22)->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');

            $creditCurrent = DB::table('sma_sales')->where('month', $m)
                ->whereBetween('credit_pay_date', [$start, $end])
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
                ->where('payment_method', 'CREDIT')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');

            $creditPast = DB::table('sma_sales')->where('month', '!=', $m)
                ->whereBetween('credit_pay_date', [$start, $end])
                ->where('payment_method', 'CREDIT')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');

            $shopee = DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Shopee')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('total');

            $lazada = DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Lazada')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('total');

            $tiktok = DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                ->where('shop_method', 'Tiktok')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('total');

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

    /*
    |--------------------------------------------------------------------------
    | 2. Monthly Orders Chart  (จำนวนออเดอร์แยกตามปี — bar, 3 series)
    |--------------------------------------------------------------------------
    */
    public function getOrdersChart(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $rows[] = [
                'month'       => Carbon::create($year, $m)->format('M'),
                'retail'      => DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                    ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                    ->where('bill_vip', 0)->count(),
                'wholesale'   => DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                    ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                    ->where('bill_vip', 1)->count(),
                'marketplace' => DB::table('sma_sales')->whereYear('date', $year)->whereMonth('date', $m)
                    ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])->count(),
            ];
        }
        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Profit Pie  (แสดงสัดส่วนรายได้และมูลค่าจากยอดขาย)
    |--------------------------------------------------------------------------
    */
    public function getProfitPie(string $start, string $end): array
    {
        $month = Carbon::parse($start)->month;

        $retail = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('bill_vip', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->where('payment_method', '!=', 'CREDIT')->sum('grand_total');

        $retailCreditNow = (float) DB::table('sma_sales')
            ->where('month', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
            ->where('payment_method', 'CREDIT')->where('bill_vip', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('grand_total');

        $retailCreditPast = (float) DB::table('sma_sales')
            ->where('month', '!=', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->where('payment_method', 'CREDIT')->where('bill_vip', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('grand_total');

        $wholesale = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('payment_method', '!=', 'CREDIT')->where('online', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
            ->whereNotIn('special_tag', [10])->where('bill_type', 'VIP')
            ->where('shop_id', '!=', 22)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        $wholesaleCreditNow = (float) DB::table('sma_sales')
            ->where('month', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Website', 'Other'])
            ->where('payment_method', 'CREDIT')->where('bill_type', 'VIP')
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('grand_total');

        $wholesaleCreditPast = (float) DB::table('sma_sales')
            ->where('month', '!=', $month)->whereBetween('credit_pay_date', [$start, $end])
            ->where('payment_method', 'CREDIT')->where('bill_type', 'VIP')
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('grand_total');

        $marketplace = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $online = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('online', 1)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        return [
            ['name' => 'Retail',      'value' => $retail + $retailCreditNow + $retailCreditPast],
            ['name' => 'Wholesale',   'value' => $wholesale + $wholesaleCreditNow + $wholesaleCreditPast],
            ['name' => 'Marketplace', 'value' => $marketplace + $online],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Retail Customer Count + Marketplace breakdown
    |--------------------------------------------------------------------------
    */
    public function getRetailCount(string $start, string $end): int
    {
        return DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('bill_vip', 0)
            ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
            ->where('payment_status', 'paid')->where('sell_by_company', 0)
            ->where('payment_method', '!=', 'CREDIT')
            ->whereNotIn('special_tag', [10])
            ->distinct('customer_id')->count('customer_id');
    }

    public function getMarketplace(string $start, string $end): array
    {
        $q = fn ($method) => (float) DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])->where('shop_method', $method)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $shopee = $q('Shopee'); $lazada = $q('Lazada');

        $tBase = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('shop_method', 'Tiktok')->where('from_live', 0)->where('tiktok_video', 0)
            ->where('affiliate_commission', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tLive = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('shop_method', 'Tiktok')->where('from_live', 1)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tAff = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('shop_method', 'Tiktok')->where('affiliate_commission', '>', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $tVid = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('shop_method', 'Tiktok')->where('tiktok_video', 1)
            ->where('from_live', 0)->where('affiliate_commission', 0)
            ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');

        $online = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
            ->where('online', 1)->where('payment_status', 'paid')
            ->where('sell_by_company', 0)->sum('grand_total');

        $tTotal = $tBase + $tLive + $tAff + $tVid;
        return [
            'shopee' => $shopee, 'lazada' => $lazada, 'tiktok' => $tTotal,
            'tiktok_base' => $tBase, 'tiktok_live' => $tLive,
            'tiktok_affiliate' => $tAff, 'tiktok_video' => $tVid,
            'online' => $online, 'total' => $shopee + $lazada + $tTotal + $online,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Monthly Sales Trend (24-mo rolling, with MoM/YoY)
    |--------------------------------------------------------------------------
    */
    public function getMonthlySalesTrend(int $months = 24): array
    {
        $since  = (int) Carbon::now()->subYears(5)->format('Y');
        $map    = $this->buildRevenueMap($since);
        $keys   = array_keys($map);
        $result = [];

        foreach ($keys as $i => $key) {
            $prevKey = $i > 0 ? $keys[$i - 1] : null;
            [$y, $mo] = explode('-', $key);
            $yoyKey   = ($y - 1) . '-' . $mo;

            $result[] = [
                'month'   => $key,
                'total'   => (float) $map[$key],
                'mom_pct' => $prevKey ? $this->pctChange($map[$key], $map[$prevKey] ?? 0) : null,
                'yoy_pct' => isset($map[$yoyKey]) ? $this->pctChange($map[$key], $map[$yoyKey]) : null,
            ];
        }
        return array_values(array_slice($result, -$months));
    }

    /*
    |--------------------------------------------------------------------------
    | 6. ข้อมูลยอดขายรายเดือน (Year-major table: 5 years × 12 months)
    |--------------------------------------------------------------------------
    */
    public function getMonthlySalesYearMajor(int $yearsBack = 5): array
    {
        $since = (int) Carbon::now()->subYears($yearsBack)->format('Y');
        $map   = $this->buildRevenueMap($since);
        $now   = Carbon::now();
        $cYear = (int) $now->format('Y');

        $result = [];
        for ($y = $cYear; $y > $cYear - $yearsBack; $y--) {
            $row = ['year' => $y, 'months' => [], 'total' => 0.0];
            for ($m = 1; $m <= 12; $m++) {
                $key   = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $value = (float) ($map[$key] ?? 0);
                $row['months'][] = $value;
                $row['total']   += $value;
            }
            $result[] = $row;
        }
        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | 7. ข้อมูลยอดขายรายไตรมาส (Quarterly: 5 years × 4 quarters)
    |--------------------------------------------------------------------------
    */
    public function getQuarterlySales(int $yearsBack = 5): array
    {
        $since = (int) Carbon::now()->subYears($yearsBack)->format('Y');
        $map   = $this->buildRevenueMap($since);
        $cYear = (int) Carbon::now()->format('Y');

        $result = [];
        for ($y = $cYear; $y > $cYear - $yearsBack; $y--) {
            $q = [0.0, 0.0, 0.0, 0.0];
            for ($m = 1; $m <= 12; $m++) {
                $key   = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $qIdx  = (int) ceil($m / 3) - 1;
                $q[$qIdx] += (float) ($map[$key] ?? 0);
            }
            $result[] = [
                'year' => $y,
                'q1'   => $q[0], 'q2' => $q[1], 'q3' => $q[2], 'q4' => $q[3],
                'total'=> array_sum($q),
            ];
        }
        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | 8. ข้อมูลกำไรขั้นต้นรายเดือน (Gross Profit — needs finance DB)
    | Gross = Revenue - COGS (finance_pay_record where pay_type.main_type_id = 2)
    |--------------------------------------------------------------------------
    */
    public function getMonthlyGrossProfit(int $yearsBack = 5): array
    {
        $since   = (int) Carbon::now()->subYears($yearsBack)->format('Y');
        $revenue = $this->buildRevenueMap($since);
        $cogs    = $this->getFinancePaymentsMap('cogs', $since);

        return $this->buildProfitTable($revenue, $cogs, $yearsBack);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. ข้อมูลกำไรสุทธิรายเดือน (Net Profit — needs finance DB)
    | Net = Revenue - ALL Payments
    |--------------------------------------------------------------------------
    */
    public function getMonthlyNetProfit(int $yearsBack = 5): array
    {
        $since   = (int) Carbon::now()->subYears($yearsBack)->format('Y');
        $revenue = $this->buildRevenueMap($since);
        $all     = $this->getFinancePaymentsMap('all', $since);

        return $this->buildProfitTable($revenue, $all, $yearsBack);
    }

    private function buildProfitTable(array $revenue, ?array $expense, int $yearsBack): array
    {
        $cYear = (int) Carbon::now()->format('Y');
        $rows  = [];

        for ($y = $cYear; $y > $cYear - $yearsBack; $y--) {
            $row = ['year' => $y, 'months' => [], 'total' => 0.0, 'available' => $expense !== null];

            for ($m = 1; $m <= 12; $m++) {
                $key = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                $rev = (float) ($revenue[$key] ?? 0);
                $exp = $expense !== null ? (float) ($expense[$key] ?? 0) : 0;
                $profit = $rev - $exp;
                $pct    = $rev > 0 ? round(($profit / $rev) * 100, 1) : null;
                $row['months'][] = ['profit' => $profit, 'pct' => $pct];
                $row['total']   += $profit;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Fetch payment totals from finance_pay_record (mysql_second).
     * $mode = 'cogs' uses only payments whose pay_type.main_type_id = 2.
     * $mode = 'all' uses every status=1 payment.
     * Returns map {YYYY-MM => totalPayment}, or null if connection unavailable.
     */
    private function getFinancePaymentsMap(string $mode, int $since): ?array
    {
        try {
            $query = DB::connection('mysql_second')->table('finance_pay_record')
                ->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(amount) as total')
                ->where('status', '1')
                ->whereYear('date', '>=', $since);

            if ($mode === 'cogs') {
                $cogsTypeIds = DB::connection('mysql_second')->table('finance_pay_type')
                    ->where('main_type_id', 2)->pluck('id');
                if ($cogsTypeIds->isEmpty()) return [];
                $query->whereIn('type', $cogsTypeIds);
            }

            $rows = $query->groupByRaw('YEAR(date), MONTH(date)')->get();
            $map  = [];
            foreach ($rows as $r) {
                $map[$r->year . '-' . str_pad($r->month, 2, '0', STR_PAD_LEFT)] = (float) $r->total;
            }
            return $map;
        } catch (Throwable $e) {
            return null;   // Finance DB unavailable — UI shows empty state
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 10. ยอดขายสินค้าแยกตามหมวดสินค้า — Top 5 products in a category (12-mo line chart)
    |--------------------------------------------------------------------------
    */
    public function getProductSalesByCategory(int $year, ?int $categoryId = null): array
    {
        $productsQuery = DB::table('sma_products')->where('status', 1);
        if ($categoryId) $productsQuery->where('category_id', $categoryId);
        $productIds = $productsQuery->pluck('id');

        if ($productIds->isEmpty()) return ['products' => [], 'months' => []];

        // Top 5 by yearly quantity
        $top5 = DB::table('sma_sale_items as si')
            ->join('sma_products as p', 'p.id', '=', 'si.product_id')
            ->select('p.id', 'p.name', 'p.code', DB::raw('SUM(si.quantity) as qty'))
            ->whereIn('si.product_id', $productIds)
            ->whereYear('si.date', $year)
            ->groupBy('p.id', 'p.name', 'p.code')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        if ($top5->isEmpty()) return ['products' => [], 'months' => []];

        $months   = [];
        $products = [];
        foreach ($top5 as $p) {
            $products[] = ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'qty' => (float) $p->qty, 'series' => []];
        }
        for ($m = 1; $m <= 12; $m++) {
            $months[] = Carbon::create($year, $m)->format('M');
            foreach ($products as $i => $prod) {
                $qty = DB::table('sma_sale_items')
                    ->where('product_id', $prod['id'])
                    ->whereYear('date', $year)->whereMonth('date', $m)
                    ->sum('quantity');
                $products[$i]['series'][] = (float) $qty;
            }
        }
        return ['products' => $products, 'months' => $months];
    }

    /*
    |--------------------------------------------------------------------------
    | 11. สรุปต้นทุนและรายได้ — Cost & Revenue Summary (12-month for selected year)
    |--------------------------------------------------------------------------
    */
    public function getCostRevenueSummary(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth()->toDateTimeString();
            $end   = Carbon::create($year, $m, 1)->endOfMonth()->endOfDay()->toDateTimeString();
            $prefix = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);

            // Revenue: same 6-stream calculation, but for one month
            $rev = 0.0;
            $rev += DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('bill_vip', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('payment_method', '!=', 'CREDIT')->sum('grand_total');
            $rev += DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('payment_method', '!=', 'CREDIT')->where('online', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
                ->whereNotIn('special_tag', [10])->where('bill_type', 'VIP')
                ->where('shop_id', '!=', 22)->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');
            $rev += DB::table('sma_sales')->whereBetween('credit_pay_date', [$start, $end])
                ->where('payment_method', 'CREDIT')->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');
            $rev += DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])
                ->where('payment_status', 'paid')->where('sell_by_company', 0)->sum('total');
            $rev += DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('online', 1)->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->sum('grand_total');

            // Purchases (sma_purchases): cost of goods bought this month
            $purchase = 0.0;
            try {
                $purchase = (float) DB::table('sma_purchases')->where('date', 'like', "$prefix%")
                    ->where('payment_status', 'paid')->sum('total');
            } catch (Throwable) { /* table may not exist */ }

            // Operations (finance_pay_record on mysql_second, all except types 6 and 1)
            $operations = 0.0;
            try {
                $operations = (float) DB::connection('mysql_second')->table('finance_pay_record')
                    ->whereNotIn('type', [6, 1])->where('date', 'like', "$prefix%")
                    ->sum('amount');
            } catch (Throwable) { /* finance DB unavailable */ }

            $rows[] = [
                'month'      => Carbon::create($year, $m)->format('M'),
                'revenue'    => (float) $rev,
                'purchase'   => $purchase,
                'operations' => $operations,
            ];
        }
        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | 12. สัดส่วนรายได้ในและต่างประเทศ — Country Split (Thailand vs Export)
    |--------------------------------------------------------------------------
    */
    public function getCountrySplit(string $start, string $end): array
    {
        // country_id = 226 → Thailand (per legacy convention)
        try {
            $thaiIds = DB::table('sma_companies')->where('country_id', 226)->pluck('id');
            $exportIds = DB::table('sma_companies')->where('country_id', '!=', 226)
                ->whereNotNull('country_id')->pluck('id');
        } catch (Throwable) {
            return [['name' => 'Thailand', 'value' => 0], ['name' => 'Export', 'value' => 0]];
        }

        $sumChunks = function ($ids) use ($start, $end) {
            $total = 0.0;
            foreach (array_chunk($ids->toArray(), 1000) as $chunk) {
                $total += (float) DB::table('sma_sales')
                    ->whereBetween('date', [$start, $end])
                    ->whereIn('customer_id', $chunk)
                    ->where('payment_status', 'paid')
                    ->sum('grand_total');
            }
            return $total;
        };

        return [
            ['name' => 'Thailand', 'value' => $sumChunks($thaiIds)],
            ['name' => 'Export',   'value' => $sumChunks($exportIds)],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 13. แสดงสัดส่วนรายได้ / CS — Profit per CS staff
    |--------------------------------------------------------------------------
    */
    public function getCSProfit(string $start, string $end): array
    {
        $month   = Carbon::parse($start)->month;
        $csStaff = $this->getCSStaff();
        if (empty($csStaff)) return [];

        $rows = [];
        foreach ($csStaff as $cs) {
            $smaId = $cs['sma_id'];
            if (!$smaId) continue;

            $retail = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('bill_vip', 0)->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online'])
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('payment_method', '!=', 'CREDIT')->where('created_by', $smaId)
                ->sum('grand_total');

            $retailC = (float) DB::table('sma_sales')->whereBetween('credit_pay_date', [$start, $end])
                ->where('payment_method', 'CREDIT')->where('bill_vip', 0)
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('created_by', $smaId)->sum('grand_total');

            $wholesale = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('payment_method', '!=', 'CREDIT')->where('online', 0)
                ->whereNotIn('shop_method', ['Shopee', 'Lazada', 'Tiktok', 'Online', 'Other'])
                ->whereNotIn('special_tag', [10])->where('bill_type', 'VIP')
                ->where('shop_id', '!=', 22)->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->where('created_by', $smaId)
                ->sum('grand_total');

            $wholesaleC = (float) DB::table('sma_sales')->whereBetween('credit_pay_date', [$start, $end])
                ->where('payment_method', 'CREDIT')->where('bill_type', 'VIP')
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('created_by', $smaId)->sum('grand_total');

            $marketplace = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->whereIn('shop_method', ['Shopee', 'Lazada', 'Tiktok'])
                ->where('payment_status', 'paid')->where('sell_by_company', 0)
                ->where('created_by', $smaId)->sum('total');

            $online = (float) DB::table('sma_sales')->whereBetween('date', [$start, $end])
                ->where('online', 1)->where('payment_status', 'paid')
                ->where('sell_by_company', 0)->where('created_by', $smaId)
                ->sum('grand_total');

            $rTotal = $retail + $retailC;
            $wTotal = $wholesale + $wholesaleC;
            $mTotal = $marketplace + $online;
            $total  = $rTotal + $wTotal + $mTotal;
            if ($total <= 0) continue;

            $rows[] = [
                'name'        => $cs['name'],
                'retail'      => $rTotal,
                'wholesale'   => $wTotal,
                'marketplace' => $mTotal,
                'total'       => $total,
            ];
        }

        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);
        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | 14 & 15. สัดส่วนยอดขายแบ่งตามประเภท (Year & Month pies)
    |--------------------------------------------------------------------------
    */
    public function getYearSalesByCategory(int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay()->toDateTimeString();
        $end   = Carbon::create($year, 12, 31)->endOfDay()->toDateTimeString();
        return $this->categorySalesPie($start, $end);
    }

    public function getMonthSalesByCategory(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateTimeString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->toDateTimeString();
        return $this->categorySalesPie($start, $end);
    }

    private function categorySalesPie(string $start, string $end): array
    {
        $rows = DB::table('sma_sale_items as si')
            ->join('sma_products as p', 'p.id', '=', 'si.product_id')
            ->leftJoin('sma_categories as c', 'c.id', '=', 'p.category_id')
            ->select('p.category_id', DB::raw('COALESCE(c.name, "Uncategorised") as name'),
                     DB::raw('SUM(si.quantity) as qty'))
            ->whereBetween('si.date', [$start, $end])
            ->where('p.status', 1)
            ->groupBy('p.category_id', 'c.name')
            ->orderByDesc('qty')
            ->get();

        return $rows->map(fn ($r) => [
            'id'   => $r->category_id, 'name' => $r->name,
            'qty'  => (float) $r->qty,
        ])->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Lookup helpers — categories & CS staff lists for selectors
    |--------------------------------------------------------------------------
    */
    public function getCategories(): array
    {
        try {
            return DB::table('sma_categories')->select('id', 'name')
                ->orderBy('name')->get()->toArray();
        } catch (Throwable) { return []; }
    }

    public function getCSStaff(): array
    {
        try {
            // CS positions per legacy: 1 (Admin), 13, 18, 25 — best-effort
            $rows = DB::table('hr_employee')
                ->select('hr_employee.id', 'hr_employee.fname', 'hr_employee.lname', 'hr_employee.sma_user as sma_id')
                ->whereIn('hr_employee.position_id', [1, 13, 18, 25])
                ->where('hr_employee.status', 1)
                ->get();
            return $rows->map(fn ($r) => [
                'id'     => $r->id,
                'sma_id' => $r->sma_id,
                'name'   => trim(($r->fname ?? '') . ' ' . ($r->lname ?? '')),
            ])->filter(fn ($r) => $r['sma_id'])->values()->toArray();
        } catch (Throwable) { return []; }
    }

    /*
    |--------------------------------------------------------------------------
    | Customer tables (Sprint 6 — for API)
    |--------------------------------------------------------------------------
    */
    public function getRetailCustomers(string $start, string $end): array
    {
        $yearStart = Carbon::parse($start)->startOfYear()->toDateString();
        $yearEnd   = Carbon::parse($end)->endOfYear()->toDateString();

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
            ->orderByDesc('filter_total')->get();

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
            ])->values()->toArray();
    }

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
            ->orderByDesc('filter_total')->get();

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
            ])->values()->toArray();
    }
}
