<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardThreeService
{
    // ── Shared constants ──────────────────────────────────────────────────────
    private const THAILAND_ID = 226;
    private const MARKETPLACE = ['Shopee', 'Lazada', 'Tiktok'];
    private const CS_POSITIONS = [1, 13, 18, 25];

    private const CYCLE_BUCKETS = [
        '≤30 วัน' => [0, 30],
        '31–60 วัน' => [31, 60],
        '61–90 วัน' => [61, 90],
        '91–180 วัน' => [91, 180],
        '>180 วัน' => [181, 99999],
    ];

    private const STAGE_LABELS = [
        1 => 'นำเสนอ',
        2 => 'คุยรายละเอียด',
        3 => 'ส่งสินค้าทดลอง',
        4 => 'ติดตาม',
        5 => 'ส่งใบเสนอราคา',
        6 => 'ปิดการขาย',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    //  SHARED HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** Dynamic amount column — marketplace uses `total`, others use `grand_total` */
    private function amountExpr(string $alias = 's'): string
    {
        $mp = implode("','", self::MARKETPLACE);
        return "CASE WHEN {$alias}.shop_method IN ('{$mp}') THEN {$alias}.total ELSE {$alias}.grand_total END";
    }

    /** Base paid-sale query with correct date routing */
    private function baseSales(string $start, string $end): \Illuminate\Database\Query\Builder
    {
        $mp = implode("','", self::MARKETPLACE);
        return DB::table('sma_sales as s')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->where(function ($q) use ($start, $end, $mp) {
                $q->where(function ($q2) use ($start, $end, $mp) {
                    $q2->where('s.payment_method', '!=', 'CREDIT')
                        ->whereBetween('s.date', [$start, $end]);
                })->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('s.payment_method', 'CREDIT')
                        ->whereBetween('s.credit_pay_date', [$start, $end]);
                });
            });
    }

    /** Comparison period: returns [prevStart, prevEnd] */
    private function comparisonPeriod(string $start, string $end): array
    {
        $s = new \DateTime($start);
        $e = new \DateTime($end);
        $days = (int) $s->diff($e)->days + 1;
        $prevEnd = (clone $s)->modify('-1 day')->format('Y-m-d');
        $prevStart = (clone $s)->modify("-{$days} days")->format('Y-m-d');
        return [$prevStart, $prevEnd];
    }

    private function pctChange($cur, $prev): ?float
    {
        if (!$prev)
            return null;
        return round(($cur - $prev) / $prev * 100, 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  MAIN SECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function getMainSummary(string $start, string $end): array
    {
        $amount = $this->amountExpr();
        [$ps, $pe] = $this->comparisonPeriod($start, $end);

        $fetch = function (string $s, string $e) use ($amount): array {
            $row = $this->baseSales($s, $e)
                ->selectRaw("
                    SUM({$amount}) AS total_sales,
                    SUM(CASE WHEN co.country_id = ? THEN {$amount} ELSE 0 END) AS thai_sales,
                    SUM(CASE WHEN co.country_id != ? THEN {$amount} ELSE 0 END) AS export_sales,
                    COUNT(DISTINCT s.customer_id) AS total_customers,
                    SUM(CASE WHEN co.country_id = ? THEN 1 ELSE 0 END) AS thai_orders,
                    SUM(CASE WHEN co.country_id != ? THEN 1 ELSE 0 END) AS export_orders,
                    COUNT(*) AS total_orders
                ", [self::THAILAND_ID, self::THAILAND_ID, self::THAILAND_ID, self::THAILAND_ID])
                ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
                ->first();

            // New customers (first-ever paid sale in range)
            $newCustomers = DB::table('sma_sales as s')
                ->where('s.payment_status', 'paid')
                ->where('s.sell_by_company', 0)
                ->whereBetween('s.date', [$s, $e])
                ->whereNotExists(function ($q) use ($s) {
                    $q->from('sma_sales as s2')
                        ->whereColumn('s2.customer_id', 's.customer_id')
                        ->where('s2.payment_status', 'paid')
                        ->where('s2.sell_by_company', 0)
                        ->where('s2.date', '<', $s);
                })
                ->distinct('customer_id')
                ->count('customer_id');

            return [
                'total_sales' => (float) ($row->total_sales ?? 0),
                'thai_sales' => (float) ($row->thai_sales ?? 0),
                'export_sales' => (float) ($row->export_sales ?? 0),
                'total_customers' => (int) ($row->total_customers ?? 0),
                'thai_orders' => (int) ($row->thai_orders ?? 0),
                'export_orders' => (int) ($row->export_orders ?? 0),
                'total_orders' => (int) ($row->total_orders ?? 0),
                'new_customers' => (int) $newCustomers,
                'avg_order' => $row->total_orders > 0
                    ? round($row->total_sales / $row->total_orders, 2) : 0,
            ];
        };

        $cur = $fetch($start, $end);
        $prev = $fetch($ps, $pe);

        return [
            'current' => $cur,
            'previous' => $prev,
            'pct_change' => [
                'total_sales' => $this->pctChange($cur['total_sales'], $prev['total_sales']),
                'thai_sales' => $this->pctChange($cur['thai_sales'], $prev['thai_sales']),
                'export_sales' => $this->pctChange($cur['export_sales'], $prev['export_sales']),
                'total_customers' => $this->pctChange($cur['total_customers'], $prev['total_customers']),
                'new_customers' => $this->pctChange($cur['new_customers'], $prev['new_customers']),
                'total_orders' => $this->pctChange($cur['total_orders'], $prev['total_orders']),
                'avg_order' => $this->pctChange($cur['avg_order'], $prev['avg_order']),
            ],
        ];
    }

    public function getThaiChart(string $start, string $end): array
    {
        return $this->topCustomersChart($start, $end, true, 10);
    }

    public function getExportChart(string $start, string $end): array
    {
        return $this->topCustomersChart($start, $end, false, 10);
    }

    private function topCustomersChart(string $start, string $end, bool $thailand, int $limit): array
    {
        $amount = $this->amountExpr();
        $rows = $this->baseSales($start, $end)
            ->selectRaw("co.name, SUM({$amount}) AS total")
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->where('co.country_id', $thailand ? '=' : '!=', self::THAILAND_ID)
            ->groupBy('s.customer_id', 'co.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'values' => $rows->pluck('total')->map(fn($v) => (float) $v)->toArray(),
        ];
    }

    public function getThaiTop10(string $start, string $end): array
    {
        return $this->topCustomersTable($start, $end, true, 10);
    }

    public function getExportTop10(string $start, string $end): array
    {
        return $this->topCustomersTable($start, $end, false, 10);
    }

    public function getThaiAll(string $start, string $end, int $page = 1, string $search = ''): array
    {
        return $this->customersTable($start, $end, true, $page, $search);
    }

    public function getExportAll(string $start, string $end, int $page = 1, string $search = ''): array
    {
        return $this->customersTable($start, $end, false, $page, $search);
    }

    private function topCustomersTable(string $start, string $end, bool $thailand, int $limit): array
    {
        $amount = $this->amountExpr();
        return $this->baseSales($start, $end)
            ->selectRaw("
                co.id, co.name, co.cf_3 AS tier,
                COUNT(*) AS orders,
                SUM({$amount}) AS total,
                SUM(si.quantity) AS qty,
                SUM(si.net_cost) AS cost
            ")
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->leftJoin('sma_sale_items as si', 'si.sale_id', '=', 's.id')
            ->where('co.country_id', $thailand ? '=' : '!=', self::THAILAND_ID)
            ->groupBy('s.customer_id', 'co.id', 'co.name', 'co.cf_3')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'tier' => $r->tier,
                'orders' => (int) $r->orders,
                'total' => (float) $r->total,
                'qty' => (int) $r->qty,
                'cost' => (float) $r->cost,
                'margin' => $r->total > 0 ? round(($r->total - $r->cost) / $r->total * 100, 1) : 0,
            ])
            ->toArray();
    }

    private function customersTable(string $start, string $end, bool $thailand, int $page, string $search): array
    {
        $amount = $this->amountExpr();
        $perPage = 20;

        $q = $this->baseSales($start, $end)
            ->selectRaw("
                co.id, co.name, co.cf_3 AS tier,
                COUNT(*) AS orders,
                SUM({$amount}) AS total
            ")
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->where('co.country_id', $thailand ? '=' : '!=', self::THAILAND_ID)
            ->groupBy('s.customer_id', 'co.id', 'co.name', 'co.cf_3')
            ->orderByDesc('total');

        if ($search) {
            $q->where('co.name', 'like', "%{$search}%");
        }

        $total = DB::table(DB::raw("({$q->toSql()}) as t"))
            ->mergeBindings($q)
            ->count();

        $rows = $q->forPage($page, $perPage)->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'tier' => $r->tier,
                'orders' => (int) $r->orders,
                'total' => (float) $r->total,
            ])
            ->toArray();

        return [
            'data' => $rows,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function getSliceDetail(int $customerId, string $start, string $end): array
    {
        $amount = $this->amountExpr();

        $company = DB::table('sma_companies')->where('id', $customerId)->first();
        if (!$company)
            return [];

        $sales = $this->baseSales($start, $end)
            ->selectRaw("s.id, s.date, s.reference_no, {$amount} AS amount, s.payment_method, s.shop_method")
            ->where('s.customer_id', $customerId)
            ->orderByDesc('s.date')
            ->get();

        $topProducts = DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->join('sma_products as p', 'p.id', '=', 'si.product_id')
            ->selectRaw('p.name, SUM(si.quantity) AS qty, SUM(si.subtotal) AS revenue')
            ->where('s.customer_id', $customerId)
            ->where('s.payment_status', 'paid')
            ->whereBetween('s.date', [$start, $end])
            ->groupBy('si.product_id', 'p.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return [
            'company' => ['id' => $company->id, 'name' => $company->name, 'tier' => $company->cf_3],
            'sales' => $sales,
            'top_products' => $topProducts,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BEHAVIOR SECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function getBehaviorSummary(string $start, string $end): array
    {
        [$ps, $pe] = $this->comparisonPeriod($start, $end);

        $fetch = function (string $s, string $e): array {
            $totals = DB::table('sma_companies')
                ->where('group_id', 3)
                ->selectRaw("
                    COUNT(*) AS total,
                    SUM(CASE WHEN country_id = ? THEN 1 ELSE 0 END) AS thai,
                    SUM(CASE WHEN country_id != ? THEN 1 ELSE 0 END) AS foreign_count
                ", [self::THAILAND_ID, self::THAILAND_ID])
                ->first();

            $newCustomers = DB::table('sma_companies')
                ->where('group_id', 3)
                ->whereBetween('regi_date', [$s, $e])
                ->count();

            $converted = DB::table('sma_companies as c')
                ->join('sma_sales as s', 'c.id', '=', 's.customer_id')
                ->where('c.group_id', 3)
                ->whereBetween('c.regi_date', [$s, $e])
                ->where('s.payment_status', 'paid')
                ->where('s.sell_by_company', 0)
                ->distinct('c.id')
                ->count('c.id');

            $avgDecisionDays = DB::table('sma_companies as c')
                ->join('sma_sales as s', 'c.id', '=', 's.customer_id')
                ->where('c.group_id', 3)
                ->whereBetween('c.regi_date', [$s, $e])
                ->where('s.payment_status', 'paid')
                ->selectRaw('ROUND(AVG(DATEDIFF(MIN(DATE(s.date)), DATE(c.regi_date))), 0) AS days')
                ->groupBy('c.id')
                ->get()
                ->avg('days');

            return [
                'total_customers' => (int) ($totals->total ?? 0),
                'thai_customers' => (int) ($totals->thai ?? 0),
                'export_customers' => (int) ($totals->foreign_count ?? 0),
                'new_customers' => (int) $newCustomers,
                'converted' => (int) $converted,
                'conversion_rate' => $newCustomers > 0 ? round($converted / $newCustomers * 100, 1) : 0,
                'avg_decision_days' => round((float) $avgDecisionDays, 0),
            ];
        };

        $cur = $fetch($start, $end);
        $prev = $fetch($ps, $pe);

        return [
            'current' => $cur,
            'previous' => $prev,
            'pct_change' => [
                'new_customers' => $this->pctChange($cur['new_customers'], $prev['new_customers']),
                'converted' => $this->pctChange($cur['converted'], $prev['converted']),
                'conversion_rate' => $this->pctChange($cur['conversion_rate'], $prev['conversion_rate']),
            ],
        ];
    }

    public function getBehaviorCharts(string $start, string $end): array
    {
        // Lead source (channel) — from sma_companies joined to sma_channels
        $leadSource = DB::table('sma_companies as c')
            ->leftJoin('sma_channels as ch', 'ch.id', '=', 'c.channel_id')
            ->selectRaw("COALESCE(ch.name, 'ไม่ระบุ') AS channel, COUNT(*) AS total")
            ->where('c.group_id', 3)
            ->whereBetween('c.regi_date', [$start, $end])
            ->groupBy('c.channel_id', 'ch.name')
            ->orderByDesc('total')
            ->get();

        // Contact type (0=inbound, 1=outbound)
        $contactType = DB::table('sma_companies')
            ->where('group_id', 3)
            ->whereBetween('regi_date', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN contact_type = 0 OR contact_type IS NULL THEN 1 ELSE 0 END) AS inbound,
                SUM(CASE WHEN contact_type = 1 THEN 1 ELSE 0 END) AS outbound
            ")
            ->first();

        // Buying cycle buckets — using buy_period_avg
        $cycleRaw = DB::table('sma_companies')
            ->where('group_id', 3)
            ->whereNotNull('buy_period_avg')
            ->where('buy_period_avg', '>', 0)
            ->selectRaw('buy_period_avg')
            ->get()
            ->pluck('buy_period_avg')
            ->toArray();

        $cycleBuckets = [];
        foreach (self::CYCLE_BUCKETS as $label => [$min, $max]) {
            $cycleBuckets[$label] = count(array_filter($cycleRaw, fn($v) => $v >= $min && $v <= $max));
        }

        return [
            'lead_source' => [
                'labels' => $leadSource->pluck('channel')->toArray(),
                'values' => $leadSource->pluck('total')->map(fn($v) => (int) $v)->toArray(),
            ],
            'contact_type' => [
                'inbound' => (int) ($contactType->inbound ?? 0),
                'outbound' => (int) ($contactType->outbound ?? 0),
            ],
            'buying_cycle' => [
                'labels' => array_keys($cycleBuckets),
                'values' => array_values($cycleBuckets),
            ],
        ];
    }

    public function getBehaviorTierCustomers(string $start, string $end, string $tier = ''): array
    {
        $amount = $this->amountExpr();

        $q = $this->baseSales($start, $end)
            ->selectRaw("
                co.id, co.name, co.cf_3 AS tier,
                COUNT(*) AS orders,
                SUM({$amount}) AS total,
                MAX(s.date) AS last_order_date
            ")
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->groupBy('s.customer_id', 'co.id', 'co.name', 'co.cf_3')
            ->orderByDesc('total')
            ->limit(50);

        if ($tier) {
            $q->where('co.cf_3', $tier);
        }

        return $q->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'tier' => $r->tier,
                'orders' => (int) $r->orders,
                'total' => (float) $r->total,
                'last_order_date' => $r->last_order_date,
            ])
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  FINANCIAL SECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function getFinancialSummary(string $start, string $end): array
    {
        [$ps, $pe] = $this->comparisonPeriod($start, $end);

        $fetch = function (string $s, string $e): array {
            $amount = $this->amountExpr();

            $revenue = $this->baseSales($s, $e)
                ->selectRaw("SUM({$amount}) AS total_revenue")
                ->value('total_revenue') ?? 0;

            $margin = DB::table('sma_sale_items as si')
                ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
                ->where('s.payment_status', 'paid')
                ->where('s.sell_by_company', 0)
                ->whereBetween('s.date', [$s, $e])
                ->selectRaw('SUM(si.net_cost) AS cost, SUM(si.subtotal) AS item_revenue')
                ->first();

            $cost = (float) ($margin->cost ?? 0);
            $itemRevenue = (float) ($margin->item_revenue ?? 0);
            $grossProfit = $itemRevenue - $cost;
            $marginPct = $itemRevenue > 0 ? round($grossProfit / $itemRevenue * 100, 1) : 0;

            // COGS from finance DB
            $cogs = null;
            try {
                $cogs = DB::connection('mysql_second')
                    ->table('finance_pay_record as fpr')
                    ->join('finance_pay_type as fpt', 'fpt.id', '=', 'fpr.type')
                    ->where('fpt.main_type_id', 2)
                    ->where('fpr.status', '1')
                    ->whereBetween('fpr.date', [$s, $e])
                    ->sum('fpr.amount');
            } catch (\Throwable) {
            }

            $highRisk = DB::table('sma_sales as s')
                ->join('sma_companies as co', 'co.id', '=', 's.customer_id')
                ->where('s.payment_method', 'CREDIT')
                ->where('s.payment_status', 'paid')
                ->whereBetween('s.credit_pay_date', [$s, $e])
                ->whereRaw('DATEDIFF(s.credit_pay_date, s.date) > (COALESCE(co.payment_term, 0) + 15)')
                ->distinct('s.customer_id')
                ->count('s.customer_id');

            return [
                'total_revenue' => (float) $revenue,
                'gross_profit' => $grossProfit,
                'margin_pct' => $marginPct,
                'cogs' => $cogs !== null ? (float) $cogs : null,
                'high_risk_customers' => (int) $highRisk,
            ];
        };

        $cur = $fetch($start, $end);
        $prev = $fetch($ps, $pe);

        return [
            'current' => $cur,
            'previous' => $prev,
            'pct_change' => [
                'total_revenue' => $this->pctChange($cur['total_revenue'], $prev['total_revenue']),
                'gross_profit' => $this->pctChange($cur['gross_profit'], $prev['gross_profit']),
                'margin_pct' => $this->pctChange($cur['margin_pct'], $prev['margin_pct']),
            ],
        ];
    }

    public function getFinancialCharts(string $start, string $end): array
    {
        // Top 10 by profit
        $topProfit = DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("co.name, SUM(si.subtotal) AS revenue, SUM(si.net_cost) AS cost")
            ->groupBy('s.customer_id', 'co.name')
            ->orderByRaw('SUM(si.subtotal) - SUM(si.net_cost) DESC')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'name' => $r->name,
                'revenue' => (float) $r->revenue,
                'cost' => (float) $r->cost,
                'gross_profit' => (float) $r->revenue - (float) $r->cost,
                'margin_pct' => $r->revenue > 0
                    ? round(($r->revenue - $r->cost) / $r->revenue * 100, 1) : 0,
            ]);

        // Margin distribution buckets
        $marginsRaw = DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("s.customer_id, SUM(si.subtotal) AS rev, SUM(si.net_cost) AS cost")
            ->groupBy('s.customer_id')
            ->get()
            ->map(fn($r) => $r->rev > 0 ? round(($r->rev - $r->cost) / $r->rev * 100, 1) : 0);

        $distribution = [
            'high' => $marginsRaw->filter(fn($m) => $m >= 25)->count(),
            'medium' => $marginsRaw->filter(fn($m) => $m >= 10 && $m < 25)->count(),
            'low' => $marginsRaw->filter(fn($m) => $m < 10)->count(),
        ];

        return [
            'top_profit' => $topProfit->toArray(),
            'distribution' => $distribution,
        ];
    }

    public function getFinancialTopCustomers(string $start, string $end): array
    {
        return DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("
                co.id, co.name, co.cf_3 AS tier,
                SUM(si.subtotal) AS revenue,
                SUM(si.net_cost) AS cost,
                COUNT(DISTINCT s.id) AS orders
            ")
            ->groupBy('s.customer_id', 'co.id', 'co.name', 'co.cf_3')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'tier' => $r->tier,
                'orders' => (int) $r->orders,
                'revenue' => (float) $r->revenue,
                'cost' => (float) $r->cost,
                'gross_profit' => (float) $r->revenue - (float) $r->cost,
                'margin_pct' => $r->revenue > 0
                    ? round(($r->revenue - $r->cost) / $r->revenue * 100, 1) : 0,
            ])
            ->toArray();
    }

    public function getFinancialPaymentAnalysis(string $start, string $end): array
    {
        // CREDIT payment terms buckets
        $credit = DB::table('sma_sales as s')
            ->join('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->where('s.payment_method', 'CREDIT')
            ->where('s.payment_status', 'paid')
            ->whereBetween('s.credit_pay_date', [$start, $end])
            ->selectRaw("
                CASE
                    WHEN co.payment_term IS NULL OR co.payment_term = 0 THEN 'เงินสด (0 วัน)'
                    WHEN co.payment_term <= 15  THEN '1–15 วัน'
                    WHEN co.payment_term <= 30  THEN '16–30 วัน'
                    WHEN co.payment_term <= 60  THEN '31–60 วัน'
                    ELSE '>60 วัน'
                END AS term_bucket,
                COUNT(*) AS bill_count,
                SUM(s.grand_total) AS total_amount,
                AVG(DATEDIFF(s.credit_pay_date, s.date)) AS avg_actual_days,
                SUM(CASE WHEN DATEDIFF(s.credit_pay_date, s.date) <= COALESCE(co.payment_term, 0) THEN 1 ELSE 0 END) AS paid_on_time
            ")
            ->groupByRaw('term_bucket')
            ->orderBy('term_bucket')
            ->get();

        // Marketplace COD
        $mp = implode("','", self::MARKETPLACE);
        $cod = DB::table('sma_sales as s')
            ->whereRaw("s.shop_method IN ('{$mp}')")
            ->where('s.payment_status', 'paid')
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("
                COUNT(*) AS bill_count,
                SUM(s.total) AS total_amount,
                AVG(DATEDIFF(s.cod_pay_date, s.date)) AS avg_days
            ")
            ->first();

        return [
            'credit_buckets' => $credit->toArray(),
            'cod' => $cod ? [
                'bill_count' => (int) $cod->bill_count,
                'total_amount' => (float) $cod->total_amount,
                'avg_days' => round((float) $cod->avg_days, 1),
            ] : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  OPERATION SECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function getOperationSummary(string $start, string $end): array
    {
        $telesaleContacts = DB::table('backend_telesale')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $csContacts = DB::table('backend_telesale_cs')
            ->whereBetween('created_at', [$start, $end])
            ->count();
        ;

        $followupsDone = DB::table('operation_followups')
            ->whereBetween('follow_up_date', [$start, $end])
            ->count();

        $followupsPending = DB::table('operation_followups')
            ->where('status', 0)
            ->whereBetween('next_due_date', [$start, $end])
            ->count();

        $openDeals = DB::table('backend_pipeline_deal')
            ->where('status', 0)
            ->count();

        $overdueFollowups = DB::table('operation_followups')
            ->where('status', 0)
            ->where('next_due_date', '<', now()->toDateString())
            ->count();

        return [
            'telesale_contacts' => (int) $telesaleContacts,
            'cs_contacts' => (int) $csContacts,
            'total_contacts' => (int) $telesaleContacts + (int) $csContacts,
            'followups_done' => (int) $followupsDone,
            'followups_pending' => (int) $followupsPending,
            'open_deals' => (int) $openDeals,
            'overdue_followups' => (int) $overdueFollowups,
        ];
    }

    public function getOperationCharts(string $start, string $end): array
    {
        // Stage funnel from sma_companies dealing_status
        $stageFunnel = DB::table('sma_companies')
            ->where('dealing', 1)
            ->selectRaw('dealing_status, COUNT(*) AS count')
            ->groupBy('dealing_status')
            ->get()
            ->mapWithKeys(fn($r) => [
                (int) $r->dealing_status => (int) $r->count
            ])
            ->toArray();

        $funnelData = [];
        foreach (self::STAGE_LABELS as $stage => $label) {
            $funnelData[] = [
                'stage' => $stage,
                'label' => $label,
                'count' => $stageFunnel[$stage] ?? 0,
            ];
        }

        // Pipeline deals by stage + weighted value
        $pipelineDeals = DB::table('backend_pipeline_deal')
            ->where('status', 0)
            ->selectRaw('stage, COUNT(*) AS count, SUM(expected_value) AS expected, SUM(expected_value * probability / 100) AS weighted')
            ->groupBy('stage')
            ->get()
            ->toArray();

        return [
            'stage_funnel' => $funnelData,
            'pipeline_deals' => $pipelineDeals,
        ];
    }

    public function getOperationTimeline(string $start, string $end): array
    {
        $telesale = DB::table('backend_telesale as t')
            ->leftJoin('sma_companies as co', 'co.id', '=', 't.customer_id')
            ->whereBetween('t.created_at', [$start, $end])
            ->selectRaw("t.created_at AS event_at, co.name AS customer_name, 'โทรติดต่อ (Sales)' AS type, t.note AS note")
            ->limit(50);

        $csTelesale = DB::table('backend_telesale_cs as t')
            ->leftJoin('sma_companies as co', 'co.id', '=', 't.customer_id')
            ->whereBetween('t.created_at', [$start, $end])
            ->selectRaw("t.created_at AS event_at, co.name AS customer_name, 'โทรติดต่อ (CS)' AS type, t.note AS note")
            ->limit(50);

        $followups = DB::table('operation_followups as f')
            ->leftJoin('sma_companies as co', 'co.id', '=', 'f.customer_id')
            ->whereBetween('f.follow_up_date', [$start, $end])
            ->selectRaw("f.follow_up_date AS event_at, co.name AS customer_name, 'ติดตามแล้ว' AS type, f.notes AS note")
            ->limit(50);

        $orders = $this->baseSales($start, $end)
            ->leftJoin('sma_companies as co', 'co.id', '=', 's.customer_id')
            ->selectRaw("s.date AS event_at, co.name AS customer_name, 'ออเดอร์ใหม่' AS type, s.reference_no AS note")
            ->limit(50);

        $timeline = $telesale
            ->unionAll($csTelesale)
            ->unionAll($followups)
            ->unionAll($orders)
            ->orderByDesc('event_at')
            ->limit(100)
            ->get();

        return $timeline->toArray();
    }

    public function getOperationFollowups(string $start, string $end): array
    {
        $today = now()->toDateString();

        $rows = DB::table('operation_followups as f')
            ->leftJoin('sma_companies as co', 'co.id', '=', 'f.customer_id')
            ->leftJoin('hr_employee as emp', 'emp.id', '=', 'f.cs_id')
            ->selectRaw("
                f.id, f.customer_id, co.name AS customer_name, f.tier,
                f.stage, f.cs_id, emp.employee_fnmt AS cs_name,
                f.follow_up_date, f.next_due_date, f.follow_up_type,
                f.notes, f.status, f.expected_value,
                CASE
                    WHEN f.status = 1 THEN 'done'
                    WHEN f.next_due_date < ? THEN 'overdue'
                    WHEN f.next_due_date = ? THEN 'today'
                    ELSE 'pending'
                END AS urgency
            ", [$today, $today])
            ->whereBetween('f.next_due_date', [$start, $end])
            ->orderByRaw("FIELD(urgency, 'overdue','today','pending','done')")
            ->orderBy('f.next_due_date')
            ->get();

        return $rows->toArray();
    }

    public function createFollowup(array $data): array
    {
        $id = DB::table('operation_followups')->insertGetId([
            'customer_id' => $data['customer_id'],
            'tier' => $data['tier'] ?? null,
            'stage' => $data['stage'] ?? null,
            'cs_id' => $data['cs_id'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? now()->toDateString(),
            'next_due_date' => $data['next_due_date'] ?? null,
            'follow_up_type' => $data['follow_up_type'] ?? 'call',
            'notes' => $data['notes'] ?? null,
            'expected_value' => $data['expected_value'] ?? null,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'success' => true];
    }

    public function markFollowupDone(int $id): array
    {
        $updated = DB::table('operation_followups')
            ->where('id', $id)
            ->update(['status' => 1, 'updated_at' => now()]);

        return ['success' => $updated > 0];
    }

    public function deleteFollowup(int $id): array
    {
        $deleted = DB::table('operation_followups')->where('id', $id)->delete();
        return ['success' => $deleted > 0];
    }

    public function getOperationPipelineDeals(?int $stage = null): array
    {
        $q = DB::table('backend_pipeline_deal as d')
            ->leftJoin('sma_companies as co', 'co.id', '=', 'd.customer_id')
            ->selectRaw("d.*, co.name AS customer_name")
            ->where('d.status', 0)
            ->orderBy('d.stage')
            ->orderByDesc('d.expected_value');

        if ($stage) {
            $q->where('d.stage', $stage);
        }

        return $q->get()->map(fn($r) => array_merge(
            (array) $r,
            ['stage_label' => self::STAGE_LABELS[$r->stage] ?? '—']
        ))->toArray();
    }

    public function createPipelineDeal(array $data): array
    {
        $id = DB::table('backend_pipeline_deal')->insertGetId([
            'customer_id' => $data['customer_id'],
            'expected_value' => $data['expected_value'] ?? 0,
            'stage' => $data['stage'] ?? 1,
            'probability' => $data['probability'] ?? 50,
            'notes' => $data['notes'] ?? null,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'success' => true];
    }

    public function updatePipelineDeal(int $id, array $data): array
    {
        $updated = DB::table('backend_pipeline_deal')
            ->where('id', $id)
            ->update(array_merge(
                array_intersect_key($data, array_flip(['expected_value', 'stage', 'probability', 'notes', 'status'])),
                ['updated_at' => now()]
            ));

        return ['success' => $updated > 0];
    }

    public function deletePipelineDeal(int $id): array
    {
        $deleted = DB::table('backend_pipeline_deal')->where('id', $id)->delete();
        return ['success' => $deleted > 0];
    }

    public function getCsStaff(): array
    {
        return DB::table('hr_employee')
            ->whereIn('position_id', self::CS_POSITIONS)
            ->where('status', 1)
            ->select('id', 'employee_fnmt AS name', 'sma_user')
            ->orderBy('employee_fnmt')
            ->get()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PRODUCT SECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function getProductSummary(string $start, string $end): array
    {
        [$ps, $pe] = $this->comparisonPeriod($start, $end);

        $fetch = function (string $s, string $e): array {
            $stats = DB::table('sma_sale_items as si')
                ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
                ->where('s.payment_status', '!=', 'returned')
                ->whereBetween('s.date', [$s, $e])
                ->selectRaw('COUNT(DISTINCT si.product_id) AS sku_count, SUM(si.subtotal) AS revenue, SUM(si.net_cost) AS cost')
                ->first();

            $slowMovers = DB::table('sma_products as p')
                ->where('p.status', 1)
                ->whereNotExists(function ($q) {
                    $q->from('sma_sale_items as si')
                        ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
                        ->whereColumn('si.product_id', 'p.id')
                        ->where('s.date', '>=', now()->subDays(60)->toDateString());
                })
                ->count();

            $avgHealth = DB::table('bluemocha_product_health_score')->avg('score');

            return [
                'sku_count' => (int) ($stats->sku_count ?? 0),
                'revenue' => (float) ($stats->revenue ?? 0),
                'cost' => (float) ($stats->cost ?? 0),
                'gross_profit' => (float) ($stats->revenue ?? 0) - (float) ($stats->cost ?? 0),
                'margin_pct' => $stats->revenue > 0
                    ? round(($stats->revenue - $stats->cost) / $stats->revenue * 100, 1) : 0,
                'slow_movers' => (int) $slowMovers,
                'avg_health' => $avgHealth ? round((float) $avgHealth, 1) : null,
            ];
        };

        $cur = $fetch($start, $end);
        $prev = $fetch($ps, $pe);

        return [
            'current' => $cur,
            'previous' => $prev,
            'pct_change' => [
                'revenue' => $this->pctChange($cur['revenue'], $prev['revenue']),
                'gross_profit' => $this->pctChange($cur['gross_profit'], $prev['gross_profit']),
                'margin_pct' => $this->pctChange($cur['margin_pct'], $prev['margin_pct']),
            ],
        ];
    }

    public function getProductCharts(string $start, string $end): array
    {
        // Top 20 products by revenue
        $topProducts = DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->join('sma_products as p', 'p.id', '=', 'si.product_id')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("p.id, p.name, p.code, SUM(si.quantity) AS qty, SUM(si.subtotal) AS revenue, SUM(si.net_cost) AS cost")
            ->groupBy('si.product_id', 'p.id', 'p.name', 'p.code')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->code,
                'qty' => (int) $r->qty,
                'revenue' => (float) $r->revenue,
                'cost' => (float) $r->cost,
                'gross_profit' => (float) $r->revenue - (float) $r->cost,
                'margin_pct' => $r->revenue > 0
                    ? round(($r->revenue - $r->cost) / $r->revenue * 100, 1) : 0,
            ]);

        // Category margin
        $categoryMargin = DB::table('sma_sale_items as si')
            ->join('sma_sales as s', 's.id', '=', 'si.sale_id')
            ->join('sma_products as p', 'p.id', '=', 'si.product_id')
            ->leftJoin('sma_categories as cat', 'cat.id', '=', 'p.category_id')
            ->where('s.payment_status', 'paid')
            ->where('s.sell_by_company', 0)
            ->whereBetween('s.date', [$start, $end])
            ->selectRaw("COALESCE(cat.name, 'ไม่ระบุ') AS category, SUM(si.subtotal) AS revenue, SUM(si.net_cost) AS cost")
            ->groupBy('p.category_id', 'cat.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'category' => $r->category,
                'revenue' => (float) $r->revenue,
                'cost' => (float) $r->cost,
                'gross_profit' => (float) $r->revenue - (float) $r->cost,
                'margin_pct' => $r->revenue > 0
                    ? round(($r->revenue - $r->cost) / $r->revenue * 100, 1) : 0,
            ]);

        return [
            'top_products' => $topProducts->toArray(),
            'category_margin' => $categoryMargin->toArray(),
        ];
    }

    public function getProductSlowMovers(int $days = 60): array
    {
        return DB::table('sma_products as p')
            ->leftJoin('sma_categories as cat', 'cat.id', '=', 'p.category_id')
            ->where('p.status', 1)
            ->selectRaw("
                p.id, p.name, p.code, COALESCE(cat.name,'ไม่ระบุ') AS category,
                (SELECT MAX(s2.date) FROM sma_sale_items si2
                 JOIN sma_sales s2 ON s2.id = si2.sale_id AND s2.payment_status != 'returned'
                 WHERE si2.product_id = p.id) AS last_sale_date,
                DATEDIFF(NOW(), (SELECT MAX(s2.date) FROM sma_sale_items si2
                 JOIN sma_sales s2 ON s2.id = si2.sale_id AND s2.payment_status != 'returned'
                 WHERE si2.product_id = p.id)) AS days_since
            ")
            ->havingRaw("days_since >= ? OR last_sale_date IS NULL", [$days])
            ->orderByDesc('days_since')
            ->limit(60)
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function getProductHealthScores(): array
    {
        return DB::table('bluemocha_product_health_score as phs')
            ->join('sma_products as p', 'p.id', '=', 'phs.product_id')
            ->leftJoin('sma_categories as cat', 'cat.id', '=', 'p.category_id')
            ->selectRaw("
                p.id, p.name, p.code, COALESCE(cat.name,'ไม่ระบุ') AS category,
                phs.score, phs.velocity_score, phs.margin_score,
                phs.consistency_score, phs.total_revenue, phs.margin_pct,
                phs.days_with_sales, phs.computed_at
            ")
            ->orderByDesc('phs.score')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function searchCustomers(string $q): array
    {
        return DB::table('sma_companies')
            ->where('name', 'like', "%{$q}%")
            ->orWhere('cf_1', 'like', "%{$q}%")
            ->select('id', 'name', 'cf_3 as tier')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function searchProducts(string $q): array
    {
        return DB::table('sma_products')
            ->where('name', 'like', "%{$q}%")
            ->orWhere('code', 'like', "%{$q}%")
            ->where('status', 1)
            ->select('id', 'name', 'code')
            ->limit(20)
            ->get()
            ->toArray();
    }

    // Competitive references
    public function getCompetitiveRefs(?int $customerId = null): array
    {
        $q = DB::table('bluemocha_competitive_reference as cr')
            ->leftJoin('sma_companies as co', 'co.id', '=', 'cr.customer_id')
            ->leftJoin('bluemocha_competitors as comp', 'comp.id', '=', 'cr.competitor_id')
            ->selectRaw("cr.*, co.name AS customer_name, comp.name AS competitor_display_name")
            ->orderByDesc('cr.recorded_at');

        if ($customerId) {
            $q->where('cr.customer_id', $customerId);
        }

        return $q->limit(100)->get()->map(fn($r) => (array) $r)->toArray();
    }

    public function createCompetitiveRef(array $data): array
    {
        $id = DB::table('bluemocha_competitive_reference')->insertGetId([
            'customer_id' => $data['customer_id'],
            'product_name' => $data['product_name'],
            'competitor_id' => $data['competitor_id'] ?? null,
            'competitor_name' => $data['competitor_name'] ?? null,
            'competitor_price' => $data['competitor_price'],
            'our_price' => $data['our_price'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'success' => true];
    }

    public function deleteCompetitiveRef(int $id): array
    {
        $deleted = DB::table('bluemocha_competitive_reference')->where('id', $id)->delete();
        return ['success' => $deleted > 0];
    }

    public function getCompetitiveSummary(): array
    {
        return DB::table('bluemocha_competitive_reference as cr')
            ->leftJoin('bluemocha_competitors as comp', 'comp.id', '=', 'cr.competitor_id')
            ->selectRaw("
                COALESCE(comp.name, cr.competitor_name, 'ไม่ระบุ') AS competitor,
                COUNT(*) AS ref_count,
                AVG(cr.competitor_price) AS avg_competitor_price,
                AVG(cr.our_price) AS avg_our_price
            ")
            ->groupByRaw("COALESCE(comp.name, cr.competitor_name, 'ไม่ระบุ')")
            ->orderByDesc('ref_count')
            ->get()
            ->map(fn($r) => array_merge((array) $r, [
                'avg_diff_pct' => $r->avg_competitor_price > 0
                    ? round(($r->avg_our_price - $r->avg_competitor_price) / $r->avg_competitor_price * 100, 1) : null,
            ]))
            ->toArray();
    }

    // Product feedback
    public function getProductFeedback(?int $productId = null): array
    {
        $q = DB::table('bluemocha_product_feedback as pf')
            ->leftJoin('sma_products as p', 'p.id', '=', 'pf.product_id')
            ->leftJoin('sma_companies as co', 'co.id', '=', 'pf.customer_id')
            ->selectRaw("pf.*, p.name AS product_name, co.name AS customer_name")
            ->orderByDesc('pf.created_at');

        if ($productId) {
            $q->where('pf.product_id', $productId);
        }

        return $q->limit(100)->get()->map(fn($r) => (array) $r)->toArray();
    }

    public function createProductFeedback(array $data): array
    {
        $id = DB::table('bluemocha_product_feedback')->insertGetId([
            'product_id' => $data['product_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'type' => $data['type'] ?? 'general',
            'category' => $data['category'] ?? null,
            'score' => $data['score'] ?? null,
            'text' => $data['text'] ?? null,
            'source' => $data['source'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'success' => true];
    }

    public function deleteProductFeedback(int $id): array
    {
        $deleted = DB::table('bluemocha_product_feedback')->where('id', $id)->delete();
        return ['success' => $deleted > 0];
    }

    public function getFeedbackOverview(string $start, string $end): array
    {
        $categories = DB::table('bluemocha_product_feedback')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("COALESCE(category,'ไม่ระบุ') AS category, COUNT(*) AS count, AVG(score) AS avg_score")
            ->groupBy('category')
            ->get();

        $typeBreakdown = DB::table('bluemocha_product_feedback')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("type, COUNT(*) AS count")
            ->groupBy('type')
            ->get();

        return [
            'categories' => $categories->toArray(),
            'type_breakdown' => $typeBreakdown->toArray(),
        ];
    }

    // Competitor master
    public function getCompetitors(): array
    {
        return DB::table('bluemocha_competitors')
            ->select('id', 'name', 'category')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function createCompetitor(array $data): array
    {
        $id = DB::table('bluemocha_competitors')->insertGetId([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'success' => true];
    }

    public function deleteCompetitor(int $id): array
    {
        $deleted = DB::table('bluemocha_competitors')->where('id', $id)->delete();
        return ['success' => $deleted > 0];
    }

    // Selector helpers
    public function getCategories(): array
    {
        return DB::table('sma_categories')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
