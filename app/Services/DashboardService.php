<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const MARKETPLACE = ['Shopee', 'Lazada', 'Tiktok'];
    private const EXCLUDED_EMPLOYEES = [33, 27, 77];
    private const MANAGER_POSITIONS = [1, 13, 2, 30, 31];
    private const MANAGER_UNRESTRICTED = [54, 16, 18, 19, 36, 95];

    // ---------- Date helpers ----------

    /**
     * Build a WHERE fragment that handles the post-July-2024 marketplace date switch.
     * Returns a raw SQL snippet and its bindings.
     */
    private function salesDateWhere(string $start, string $end, string $alias = ''): array
    {
        $p = $alias ? "{$alias}." : '';
        return [
            "(
                ({$p}shop_method NOT IN ('Shopee','Lazada','Tiktok') AND {$p}date BETWEEN ? AND ?)
                OR ({$p}shop_method IN ('Shopee','Lazada','Tiktok') AND YEAR({$p}date) <= 2024 AND {$p}date BETWEEN ? AND ?)
                OR ({$p}shop_method IN ('Shopee','Lazada','Tiktok') AND YEAR({$p}date) > 2024 AND {$p}cod_pay_date BETWEEN ? AND ?)
            )",
            [$start, $end, $start, $end, $start, $end],
        ];
    }

    // ---------- KPI Cards ----------

    public function getKpis(string $start, string $end): array
    {
        [$dateWhere, $bindings] = $this->salesDateWhere($start, $end);

        $row = DB::selectOne("
            SELECT
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 THEN grand_total ELSE 0 END) AS sale_total,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND bill_vip=0
                    AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online')
                    AND payment_method != 'CREDIT' THEN grand_total ELSE 0 END) AS retail,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND bill_vip=0
                    AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online')
                    AND payment_method = 'CREDIT' THEN grand_total ELSE 0 END) AS retail_credit,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND bill_type='VIP'
                    AND payment_method != 'CREDIT' AND online=0
                    AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online','Other')
                    AND special_tag != 10 AND shop_id != 22 THEN grand_total ELSE 0 END) AS wholesale,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND payment_method='CREDIT'
                    AND bill_type='VIP' THEN grand_total ELSE 0 END) AS credit,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0
                    AND shop_method IN ('Shopee','Lazada','Tiktok') THEN total ELSE 0 END) AS marketplace,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND shop_method='Shopee' THEN total ELSE 0 END) AS shopee,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND shop_method='Lazada' THEN total ELSE 0 END) AS lazada,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND shop_method='Tiktok'
                    AND from_live=0 AND shop_brand=2 AND shop_id != 42 THEN total ELSE 0 END) AS tiktok,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND shop_method='Tiktok'
                    AND from_live=1 THEN total ELSE 0 END) AS tiktok_live,
                SUM(CASE WHEN payment_status='paid' AND online=1 THEN grand_total ELSE 0 END) AS online_sale,
                SUM(CASE WHEN sell_by_company=0 AND shop_id=22 THEN grand_total ELSE 0 END) AS thailand_cf,
                COUNT(CASE WHEN payment_status='paid' AND sell_by_company=0
                    AND shop_method NOT IN ('Shopee','Lazada') THEN 1 ELSE NULL END) AS order_count,
                COUNT(CASE WHEN payment_status='paid' AND sell_by_company=0
                    AND shop_method IN ('Shopee','Lazada') THEN 1 ELSE NULL END) AS marketplace_order_count,
                SUM(CASE WHEN payment_status!='paid' AND sell_by_company=0 THEN grand_total ELSE 0 END) AS unpaid_total
            FROM sma_sales
            WHERE {$dateWhere}
        ", $bindings);

        return [
            'sale_total'            => (float) ($row->sale_total ?? 0),
            'retail'                => (float) ($row->retail ?? 0),
            'retail_credit'         => (float) ($row->retail_credit ?? 0),
            'wholesale'             => (float) ($row->wholesale ?? 0),
            'credit'                => (float) ($row->credit ?? 0),
            'retail_wholesale'      => (float) ($row->retail ?? 0) + (float) ($row->wholesale ?? 0),
            'marketplace'           => (float) ($row->marketplace ?? 0),
            'shopee'                => (float) ($row->shopee ?? 0),
            'lazada'                => (float) ($row->lazada ?? 0),
            'tiktok'                => (float) ($row->tiktok ?? 0),
            'tiktok_live'           => (float) ($row->tiktok_live ?? 0),
            'online_sale'           => (float) ($row->online_sale ?? 0),
            'thailand_cf'           => (float) ($row->thailand_cf ?? 0),
            'order_count'           => (int) ($row->order_count ?? 0),
            'marketplace_orders'    => (int) ($row->marketplace_order_count ?? 0),
            'unpaid_total'          => (float) ($row->unpaid_total ?? 0),
        ];
    }

    // ---------- Section 2 (period comparisons) ----------

    public function getSection2(): array
    {
        $now        = Carbon::now();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd   = $now->copy()->endOfMonth()->toDateString();
        $lastStart  = $now->copy()->subMonth()->startOfMonth()->toDateString();
        $lastEnd    = $now->copy()->subMonth()->endOfMonth()->toDateString();
        $today      = $now->toDateString();
        $yesterday  = $now->copy()->subDay()->toDateString();

        $periods = [
            'this_month' => [$monthStart, $monthEnd],
            'last_month' => [$lastStart,  $lastEnd],
            'today'      => [$today,       $today],
            'yesterday'  => [$yesterday,   $yesterday],
        ];

        $results = [];
        foreach ($periods as $key => [$s, $e]) {
            [$dateWhere, $bindings] = $this->salesDateWhere($s, $e);
            $row = DB::selectOne("
                SELECT
                    SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 THEN grand_total ELSE 0 END) AS total,
                    SUM(CASE WHEN payment_status='paid' AND sell_by_company=0
                        AND (bill_vip=0 OR bill_type='VIP')
                        AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online')
                        AND payment_method != 'CREDIT' THEN grand_total ELSE 0 END) AS retail_wholesale,
                    COUNT(CASE WHEN payment_status='paid' AND sell_by_company=0
                        AND shop_method NOT IN ('Shopee','Lazada') THEN 1 ELSE NULL END) AS orders
                FROM sma_sales
                WHERE {$dateWhere}
            ", $bindings);
            $results[$key] = [
                'total'           => (float) ($row->total ?? 0),
                'retail_wholesale'=> (float) ($row->retail_wholesale ?? 0),
                'orders'          => (int) ($row->orders ?? 0),
            ];
        }

        $calc = fn($a, $b) => $b > 0 ? round((($a - $b) / $b) * 100, 1) : 0;

        return [
            'this_month'      => $results['this_month'],
            'last_month'      => $results['last_month'],
            'today'           => $results['today'],
            'yesterday'       => $results['yesterday'],
            'month_vs_last'   => $calc($results['this_month']['total'],           $results['last_month']['total']),
            'rw_vs_last'      => $calc($results['this_month']['retail_wholesale'], $results['last_month']['retail_wholesale']),
            'orders_vs_last'  => $calc($results['this_month']['orders'],           $results['last_month']['orders']),
            'today_vs_yest'   => $calc($results['today']['total'],                 $results['yesterday']['total']),
        ];
    }

    // ---------- Sales Rank ----------

    public function getSaleRank(string $start, string $end, User $authUser): array
    {
        $restrictSelf = in_array($authUser->employee_record?->employee_position ?? 0, self::MANAGER_POSITIONS)
                        && ! in_array($authUser->sma_id, self::MANAGER_UNRESTRICTED);

        $empIds = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('online', 0)
            ->where('payment_status', 'paid')
            ->where('sell_by_company', 0)
            ->whereNotIn('created_by', self::EXCLUDED_EMPLOYEES)
            ->when($restrictSelf, fn ($q) => $q->where('created_by', $authUser->sma_id))
            ->distinct()->pluck('created_by');

        $employees = DB::table('hr_employee')
            ->whereIn('sma_user', $empIds)
            ->get(['id', 'sma_user', 'employee_fnmt', 'employee_lnmt', 'employee_photo'])
            ->keyBy('sma_user');

        $list = [];
        foreach ($empIds as $smaId) {
            $base = DB::table('sma_sales')
                ->whereBetween('date', [$start, $end])
                ->where('created_by', $smaId)
                ->where('grand_total', '>', 0)
                ->where('online', 0)
                ->where('shop_id', '!=', 22);

            $total      = (clone $base)->where('payment_status', 'paid')->sum('grand_total');
            $retail     = (clone $base)->whereNull('bill_type')->where('payment_status', 'paid')->sum('grand_total');
            $retailCnt  = (clone $base)->whereNull('bill_type')->where('payment_status', 'paid')->count();
            $wholesale  = (clone $base)->where('bill_type', 'VIP')->where('payment_status', 'paid')->sum('grand_total');
            $wholesaleCnt = (clone $base)->where('bill_type', 'VIP')->where('payment_status', 'paid')->count();
            $orderCount = (clone $base)->where('payment_status', 'paid')->count();

            $emp = $employees[$smaId] ?? null;
            $list[] = [
                'sma_id'        => $smaId,
                'name'          => $emp ? trim("{$emp->employee_fnmt} {$emp->employee_lnmt}") : "SMA {$smaId}",
                'photo'         => $emp?->employee_photo,
                'total'         => (float) $total,
                'retail'        => (float) $retail,
                'retail_count'  => (int) $retailCnt,
                'wholesale'     => (float) $wholesale,
                'wholesale_count'=> (int) $wholesaleCnt,
                'order_count'   => (int) $orderCount,
            ];
        }

        usort($list, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_values($list);
    }

    // ---------- Best Sale Products ----------

    public function getBestSale(string $start, string $end): array
    {
        [$dateWhere, $bindings] = $this->salesDateWhere($start, $end, 's');

        $rows = DB::select("
            SELECT
                si.product_id,
                p.name    AS product_name,
                p.image   AS product_image,
                s.shop_method,
                s.bill_vip,
                SUM(si.quantity) AS qty
            FROM sma_sales s
            JOIN sma_sale_items si ON si.sale_id = s.id
            JOIN sma_products p   ON p.id = si.product_id
            WHERE {$dateWhere}
              AND s.payment_status = 'paid'
            GROUP BY si.product_id, p.name, p.image, s.shop_method, s.bill_vip
            ORDER BY qty DESC
        ", $bindings);

        $grouped = [];
        $grandTotal = 0;
        foreach ($rows as $r) {
            $id = $r->product_id;
            if (! isset($grouped[$id])) {
                $grouped[$id] = [
                    'name'          => $r->product_name,
                    'image'         => $r->product_image,
                    'total_qty'     => 0,
                    'retail_qty'    => 0,
                    'wholesale_qty' => 0,
                ];
            }
            $grouped[$id]['total_qty']     += $r->qty;
            $grouped[$id]['retail_qty']    += ($r->bill_vip == 0 ? $r->qty : 0);
            $grouped[$id]['wholesale_qty'] += ($r->bill_vip == 1 ? $r->qty : 0);
            $grandTotal += $r->qty;
        }

        uasort($grouped, fn ($a, $b) => $b['total_qty'] <=> $a['total_qty']);

        return array_values(array_map(function ($p) use ($grandTotal) {
            return [
                ...$p,
                'percent' => $grandTotal > 0 ? round($p['total_qty'] * 100 / $grandTotal, 1) : 0,
            ];
        }, array_slice($grouped, 0, 20, true)));
    }

    // ---------- Commissions ----------

    public function getCommissions(string $start, string $end): array
    {
        return [
            'online'     => $this->commOnline($start, $end),
            'thailand_cf'=> $this->commThailandCF($start, $end),
            'tiktok_live'=> $this->commTiktokLive($start, $end),
            'tiktok_teafac'    => $this->commTiktokTeafac($start, $end),
            'tiktok_affiliate' => $this->commTiktokAffiliate($start, $end),
        ];
    }

    private function commOnline(string $start, string $end): array
    {
        $row = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('grand_total', '>', 0)
            ->where('online', 1)
            ->selectRaw('SUM(total) as total, COUNT(*) as orders')
            ->first();

        $total = (float) ($row->total ?? 0);
        return [
            'total'      => $total,
            'orders'     => (int) ($row->orders ?? 0),
            'commission' => round($total * 0.02, 2),
            'rate'       => '2%',
        ];
    }

    private function commThailandCF(string $start, string $end): array
    {
        $row = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('grand_total', '>', 0)
            ->where('shop_id', 22)
            ->where('sell_by_company', 0)
            ->selectRaw('SUM(total) as total, COUNT(*) as orders')
            ->first();

        $total = (float) ($row->total ?? 0);
        return [
            'total'      => $total,
            'orders'     => (int) ($row->orders ?? 0),
            'commission' => round($total * 0.02, 2),
            'rate'       => '2%',
        ];
    }

    private function commTiktokLive(string $start, string $end): array
    {
        $row = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('grand_total', '>', 0)
            ->where('shop_method', 'Tiktok')
            ->where('shop_id', 33)
            ->where('payment_status', 'paid')
            ->selectRaw('SUM(total) as total, COUNT(*) as orders')
            ->first();

        $total = (float) ($row->total ?? 0);
        $startCarbon = Carbon::parse($start);
        $yearMonth   = $startCarbon->format('Y-m');

        $split = match (true) {
            $yearMonth === '2024-06' => 6,
            $yearMonth === '2024-07' => 4,
            default                  => 3,
        };

        return [
            'total'          => $total,
            'orders'         => (int) ($row->orders ?? 0),
            'commission_1pct'=> round($total * 0.01, 2),
            'commission_2pct'=> round($total * 0.02, 2),
            'per_employee'   => $split > 0 ? round(($total * 0.01) / $split, 2) : 0,
            'split_count'    => $split,
            'rate'           => '1% / 2%',
        ];
    }

    private function commTiktokTeafac(string $start, string $end): array
    {
        $row = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('grand_total', '>', 0)
            ->where('shop_method', 'Tiktok')
            ->where('shop_id', 42)
            ->where('payment_status', 'paid')
            ->selectRaw('SUM(total) as total, COUNT(*) as orders')
            ->first();

        $total = (float) ($row->total ?? 0);
        $startCarbon = Carbon::parse($start);
        $yearMonth   = $startCarbon->format('Y-m');

        $split = match (true) {
            $yearMonth === '2024-06' => 6,
            $yearMonth === '2024-07' => 4,
            default                  => 3,
        };

        return [
            'total'          => $total,
            'orders'         => (int) ($row->orders ?? 0),
            'commission_1pct'=> round($total * 0.01, 2),
            'commission_2pct'=> round($total * 0.02, 2),
            'per_employee'   => $split > 0 ? round(($total * 0.01) / $split, 2) : 0,
            'split_count'    => $split,
            'rate'           => '1% / 2%',
        ];
    }

    private function commTiktokAffiliate(string $start, string $end): array
    {
        $row = DB::table('sma_sales')
            ->whereBetween('date', [$start, $end])
            ->where('grand_total', '>', 0)
            ->where('shop_method', 'Tiktok')
            ->where('payment_status', 'paid')
            ->where('affiliate_commission', '>', 0)
            ->where('sell_by_company', 0)
            ->where('from_live', 0)
            ->where('shop_brand', 2)
            ->selectRaw('SUM(total) as total, COUNT(*) as orders')
            ->first();

        $total = (float) ($row->total ?? 0);
        return [
            'total'           => $total,
            'orders'          => (int) ($row->orders ?? 0),
            'commission_1pct' => round($total * 0.01, 2),
            'commission_2pct' => round($total * 0.02, 2),
            'per_employee'    => round(($total * 0.01) / 2, 2),
            'split_count'     => 2,
            'rate'            => '1% / 2%',
        ];
    }

    // ---------- Brand Sales ----------

    public function getBrandSale(string $start, string $end): array
    {
        $brands = DB::table('sma_brands')->where('status', 1)->get(['id', 'name', 'color']);

        [$dateWhere, $bindings] = $this->salesDateWhere($start, $end, '');

        $salesRows = DB::select("
            SELECT shop_brand, shop_method,
                SUM(CASE WHEN shop_method IN ('Shopee','Lazada','Tiktok') THEN total ELSE grand_total END) AS amount
            FROM sma_sales
            WHERE {$dateWhere}
              AND payment_status = 'paid'
            GROUP BY shop_brand, shop_method
        ", $bindings);

        $byBrand = [];
        $grandTotal = 0;
        foreach ($salesRows as $r) {
            $key = $r->shop_brand ?? 0;
            $byBrand[$key] = ($byBrand[$key] ?? 0) + (float) $r->amount;
            $grandTotal += (float) $r->amount;
        }

        $result = [];
        foreach ($brands as $brand) {
            $amount = $byBrand[$brand->id] ?? 0;
            $result[] = [
                'id'      => $brand->id,
                'name'    => $brand->name,
                'color'   => $brand->color,
                'amount'  => $amount,
                'percent' => $grandTotal > 0 ? round($amount * 100 / $grandTotal, 1) : 0,
            ];
        }

        usort($result, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        return $result;
    }

    // ---------- Shipping Method ----------

    public function getShipping(string $start, string $end): array
    {
        $methods = DB::table('sma_shipping_method')->where('status', 1)->get(['id', 'code', 'name', 'image']);

        $counts = DB::table('sma_deliveries')
            ->whereBetween('date', [$start, $end])
            ->selectRaw('shipping_method, SUM(count_box) as boxes')
            ->groupBy('shipping_method')
            ->pluck('boxes', 'shipping_method');

        return $methods->map(fn ($m) => [
            'id'    => $m->id,
            'code'  => $m->code,
            'name'  => $m->name,
            'image' => $m->image,
            'boxes' => (int) ($counts[$m->code] ?? 0),
        ])->values()->toArray();
    }

    // ---------- Customer Count ----------

    public function getCustomerCount(): array
    {
        $onlineChannels = DB::table('sma_channels')
            ->whereIn('code', ['Lazada', 'Shopee', 'Tiktok'])
            ->pluck('id');

        return [
            'total'   => DB::table('sma_companies')->count(),
            'retail'  => DB::table('sma_companies')->where('vip', 0)->whereNotIn('channel_id', $onlineChannels)->count(),
            'vip'     => DB::table('sma_companies')->where('vip', 1)->whereNotIn('channel_id', $onlineChannels)->count(),
            'lazada'  => DB::table('sma_companies')->whereIn('channel_id', DB::table('sma_channels')->where('code', 'Lazada')->pluck('id'))->count(),
            'shopee'  => DB::table('sma_companies')->whereIn('channel_id', DB::table('sma_channels')->where('code', 'Shopee')->pluck('id'))->count(),
            'tiktok'  => DB::table('sma_companies')->whereIn('channel_id', DB::table('sma_channels')->where('code', 'Tiktok')->pluck('id'))->count(),
        ];
    }

    // ---------- New Customers ----------

    public function getNewCustomers(string $start, string $end): array
    {
        $onlineChannels = DB::table('sma_channels')
            ->whereIn('code', ['Lazada', 'Shopee', 'Tiktok'])
            ->pluck('id');

        $retail = DB::table('sma_companies')
            ->whereBetween('regi_date', [$start, $end])
            ->where('vip', 0)->whereNotIn('channel_id', $onlineChannels)->count();

        $vip = DB::table('sma_companies')
            ->whereBetween('regi_date', [$start, $end])
            ->where('vip', 1)->whereNotIn('channel_id', $onlineChannels)->count();

        return [
            'total'  => $retail + $vip,
            'retail' => $retail,
            'vip'    => $vip,
        ];
    }

    // ---------- Charts ----------

    public function getDailyChart(string $start, string $end): array
    {
        [$dateWhere, $bindings] = $this->salesDateWhere($start, $end);

        $rows = DB::select("
            SELECT
                DATE(date) AS day,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 THEN grand_total ELSE 0 END) AS total,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND bill_vip=0
                    AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online')
                    AND payment_method != 'CREDIT' THEN grand_total ELSE 0 END) AS retail,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0 AND bill_type='VIP'
                    AND payment_method != 'CREDIT' AND online=0
                    AND shop_method NOT IN ('Shopee','Lazada','Tiktok','Online','Other')
                    AND special_tag != 10 AND shop_id != 22 THEN grand_total ELSE 0 END) AS wholesale,
                SUM(CASE WHEN payment_status='paid' AND sell_by_company=0
                    AND shop_method IN ('Shopee','Lazada','Tiktok') THEN total ELSE 0 END) AS marketplace
            FROM sma_sales
            WHERE {$dateWhere}
            GROUP BY DATE(date)
            ORDER BY DATE(date) ASC
        ", $bindings);

        return array_map(fn ($r) => [
            'day'        => $r->day,
            'total'      => (float) ($r->total ?? 0),
            'retail'     => (float) ($r->retail ?? 0),
            'wholesale'  => (float) ($r->wholesale ?? 0),
            'marketplace'=> (float) ($r->marketplace ?? 0),
        ], $rows);
    }

    public function getDayOfWeekChart(string $start, string $end): array
    {
        [$dateWhere, $bindings] = $this->salesDateWhere($start, $end);

        $rows = DB::select("
            SELECT
                DAYOFWEEK(date) AS dow,
                DAYNAME(date)   AS day_name,
                shop_method,
                COUNT(id)       AS orders
            FROM sma_sales
            WHERE {$dateWhere}
              AND payment_status = 'paid'
            GROUP BY DAYOFWEEK(date), DAYNAME(date), shop_method
            ORDER BY DAYOFWEEK(date) ASC
        ", $bindings);

        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $channels = ['Facebook','Lineat','Shop','Lazada','Shopee','Tiktok','Online','Other'];

        $matrix = [];
        foreach ($days as $d) {
            $matrix[$d] = array_fill_keys($channels, 0);
        }
        foreach ($rows as $r) {
            $day = $r->day_name;
            $ch  = $r->shop_method;
            if (isset($matrix[$day]) && isset($matrix[$day][$ch])) {
                $matrix[$day][$ch] += (int) $r->orders;
            }
        }

        return [
            'days'     => $days,
            'channels' => $channels,
            'matrix'   => $matrix,
        ];
    }
}
