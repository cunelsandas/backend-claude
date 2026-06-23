<script setup>
import { Deferred, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, BarElement, LineElement,
    PointElement, Filler, Tooltip, Legend, Title,
} from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';
import Badge from '@/Components/UI/Badge.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Tooltip, Legend, Title);

const props = defineProps({
    dateRange:     { type: Object, required: true },
    kpis:          { type: Object,  default: null },
    section2:      { type: Object,  default: null },
    saleRank:      { type: Array,   default: null },
    bestSale:      { type: Array,   default: null },
    commissions:   { type: Object,  default: null },
    brandSale:     { type: Array,   default: null },
    shipping:      { type: Array,   default: null },
    customerCount: { type: Object,  default: null },
    newCustomers:  { type: Object,  default: null },
    charts:        { type: Object,  default: null },
});

// ── Date filter ──────────────────────────────────────────────────────────
const dateFilter = ref([props.dateRange.start, props.dateRange.end]);

function applyDateRange(dates) {
    if (!dates || dates.length < 2) return;
    router.reload({
        data: { start: dates[0], end: dates[1] },
        preserveScroll: true,
    });
}

// ── Formatters ───────────────────────────────────────────────────────────
const fmt  = (n) => Number(n ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = (n) => Number(n ?? 0).toLocaleString('th-TH');
const pct  = (n) => { const v = Number(n ?? 0); return (v >= 0 ? '+' : '') + v.toFixed(1) + '%'; };
const pctTone = (n) => Number(n ?? 0) >= 0 ? 'success' : 'danger';

// ── KPI cards (guarded — kpis is null until deferred props arrive) ────────
const kpiCards = computed(() => {
    if (!props.kpis) return [];
    return [
        { label: 'Total Sales',        value: props.kpis.sale_total,      sub: `${fmtN(props.kpis.order_count + props.kpis.marketplace_orders)} orders`, tone: 'accent' },
        { label: 'Retail + Wholesale', value: props.kpis.retail_wholesale, sub: `Retail ${fmt(props.kpis.retail)}`, tone: 'default' },
        { label: 'Wholesale',          value: props.kpis.wholesale,        sub: null, tone: 'default' },
        { label: 'Credit',             value: props.kpis.credit,           sub: null, tone: 'default' },
        { label: 'Marketplace',        value: props.kpis.marketplace,      sub: `Shopee ${fmt(props.kpis.shopee)} · Lazada ${fmt(props.kpis.lazada)}`, tone: 'default' },
        { label: 'TikTok Live',        value: props.kpis.tiktok_live,      sub: `Affiliate ${fmt(props.kpis.tiktok)}`, tone: 'default' },
    ];
});

// ── Daily chart ──────────────────────────────────────────────────────────
const dailyChartData = computed(() => {
    const daily = props.charts?.daily ?? [];
    return {
        labels: daily.map(d => d.day),
        datasets: [
            { label: 'Total',      data: daily.map(d => d.total),       borderColor: 'rgb(196,137,47)',  backgroundColor: 'rgba(196,137,47,0.08)', fill: true, tension: 0.3, pointRadius: 2 },
            { label: 'Retail',     data: daily.map(d => d.retail),      borderColor: 'rgb(63,111,154)',  backgroundColor: 'rgba(63,111,154,0.06)', fill: true, tension: 0.3, pointRadius: 2 },
            { label: 'Wholesale',  data: daily.map(d => d.wholesale),   borderColor: 'rgb(74,124,89)',   backgroundColor: 'rgba(74,124,89,0.06)',  fill: true, tension: 0.3, pointRadius: 2 },
            { label: 'Marketplace',data: daily.map(d => d.marketplace), borderColor: 'rgb(179,73,63)',   backgroundColor: 'rgba(179,73,63,0.06)',  fill: true, tension: 0.3, pointRadius: 2 },
        ],
    };
});

const lineChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => fmtN(v) } },
    },
};

// ── Day-of-week chart ─────────────────────────────────────────────────────
const CHANNEL_COLORS = {
    Facebook: 'rgba(66,103,178,0.7)', Lineat: 'rgba(6,199,85,0.7)',
    Shop:     'rgba(196,137,47,0.7)', Lazada: 'rgba(245,130,32,0.7)',
    Shopee:   'rgba(238,77,45,0.7)',  Tiktok: 'rgba(0,0,0,0.6)',
    Online:   'rgba(63,111,154,0.7)', Other:  'rgba(160,160,160,0.7)',
};
const dowChartData = computed(() => {
    const dow = props.charts?.dayOfWeek ?? { days: [], channels: [], matrix: {} };
    return {
        labels: dow.days,
        datasets: dow.channels.map(ch => ({
            label: ch,
            data: dow.days.map(d => dow.matrix[d]?.[ch] ?? 0),
            backgroundColor: CHANNEL_COLORS[ch] ?? 'rgba(100,100,100,0.5)',
        })),
    };
});
const barChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
    scales: {
        x: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
};

// ── Commission tab ────────────────────────────────────────────────────────
const activeCommTab = ref('online');
const commTabs = [
    { key: 'online',           label: 'Online' },
    { key: 'thailand_cf',      label: 'Thailand CF' },
    { key: 'tiktok_live',      label: 'TikTok Live' },
    { key: 'tiktok_teafac',    label: 'TikTok Teafac' },
    { key: 'tiktok_affiliate', label: 'TikTok Affiliate' },
];
</script>

<template>
    <AppLayout>
        <template #header>Dashboard</template>

        <!-- Date Range Filter — always visible immediately -->
        <div class="flex items-center justify-between mb-6">
            <p class="text-xs text-[color:var(--color-brand-500)] uppercase tracking-wider font-medium">
                {{ dateRange.start === dateRange.end ? dateRange.start : `${dateRange.start} → ${dateRange.end}` }}
            </p>
            <DateRangePicker
                :model-value="dateFilter"
                @update:model-value="applyDateRange"
                placeholder="Select date range"
            />
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════
             Deferred: skeleton shown until all heavy props arrive from server
             ═══════════════════════════════════════════════════════════════════ -->
        <Deferred :data="['kpis','section2','saleRank','bestSale','commissions','brandSale','shipping','customerCount','newCustomers','charts']">

            <template #fallback>
                <!-- KPI skeletons -->
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
                    <div v-for="i in 6" :key="i"
                        class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-2.5 w-16 bg-[color:var(--color-brand-100)] rounded mb-3"/>
                        <div class="h-5 w-24 bg-[color:var(--color-brand-100)] rounded mb-2"/>
                        <div class="h-2 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                    </div>
                </div>

                <!-- Period comparison skeletons -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div v-for="i in 4" :key="i"
                        class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-2.5 w-20 bg-[color:var(--color-brand-100)] rounded mb-3"/>
                        <div class="h-5 w-28 bg-[color:var(--color-brand-100)] rounded mb-2"/>
                        <div class="flex gap-3">
                            <div class="h-2 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-2 w-12 bg-[color:var(--color-brand-100)] rounded"/>
                        </div>
                    </div>
                </div>

                <!-- Chart skeletons -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i"
                        class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-72">
                        <div class="h-3 w-32 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="h-56 bg-[color:var(--color-brand-50)] rounded"/>
                    </div>
                </div>

                <!-- Sales rank + customer count skeletons -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                    <div class="xl:col-span-2 bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-3 w-24 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div v-for="i in 6" :key="i" class="flex items-center gap-4 py-2.5 border-b border-[color:var(--color-brand-50)]">
                            <div class="h-3 w-4 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-3 w-28 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="ml-auto h-3 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-3 w-8  bg-[color:var(--color-brand-100)] rounded"/>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div v-for="card in 2" :key="card"
                            class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                            <div class="h-2.5 w-24 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                            <div v-for="i in 4" :key="i" class="flex justify-between py-1">
                                <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                                <div class="h-3 w-12 bg-[color:var(--color-brand-100)] rounded"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best sale skeleton -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse mb-6">
                    <div class="h-3 w-32 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                    <div v-for="i in 8" :key="i" class="flex items-center gap-4 py-2.5 border-b border-[color:var(--color-brand-50)]">
                        <div class="h-3 w-4  bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="ml-auto h-3 w-12 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-3 w-12 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-3 w-12 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-2 w-24 bg-[color:var(--color-brand-100)] rounded-full"/>
                    </div>
                </div>

                <!-- Commission + brand + shipping skeletons -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse mb-6">
                    <div class="flex gap-2 mb-4">
                        <div v-for="i in 5" :key="i" class="h-6 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <div v-for="i in 4" :key="i">
                            <div class="h-2.5 w-16 bg-[color:var(--color-brand-100)] rounded mb-2"/>
                            <div class="h-4 w-24 bg-[color:var(--color-brand-100)] rounded"/>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i"
                        class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-3 w-28 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div v-for="j in 5" :key="j" class="flex justify-between py-2 border-b border-[color:var(--color-brand-50)]">
                            <div class="h-3 w-24 bg-[color:var(--color-brand-100)] rounded"/>
                            <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════════
                 CONTENT — rendered once all deferred props have arrived
                 ═══════════════════════════════════════════════════════════════ -->

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]"
                    :class="card.tone === 'accent' ? 'border-l-2 border-l-[color:var(--color-accent-400)]' : ''"
                >
                    <p class="text-[10px] uppercase tracking-wider font-medium text-[color:var(--color-brand-500)] mb-1">{{ card.label }}</p>
                    <p class="font-mono text-lg font-semibold text-[color:var(--color-ink)] leading-tight">{{ fmt(card.value) }}</p>
                    <p v-if="card.sub" class="text-[10px] text-[color:var(--color-brand-400)] mt-0.5 truncate">{{ card.sub }}</p>
                </div>
            </div>

            <!-- Section 2 — Period Comparisons -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <template v-for="(period, label) in { 'This Month': section2.this_month, 'Last Month': section2.last_month, 'Today': section2.today, 'Yesterday': section2.yesterday }" :key="label">
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-wider font-medium text-[color:var(--color-brand-500)]">{{ label }}</p>
                            <Badge v-if="label === 'This Month'" :tone="pctTone(section2.month_vs_last)" size="sm">{{ pct(section2.month_vs_last) }}</Badge>
                            <Badge v-if="label === 'Today'" :tone="pctTone(section2.today_vs_yest)" size="sm">{{ pct(section2.today_vs_yest) }}</Badge>
                        </div>
                        <p class="font-mono text-base font-semibold text-[color:var(--color-ink)]">{{ fmt(period.total) }}</p>
                        <div class="flex gap-3 mt-1">
                            <span class="text-[10px] text-[color:var(--color-brand-400)]">R+W {{ fmt(period.retail_wholesale) }}</span>
                            <span class="text-[10px] text-[color:var(--color-brand-400)]">{{ fmtN(period.orders) }} orders</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">Daily Sales Trend</p>
                    <div class="h-56">
                        <Line v-if="charts.daily.length" :data="dailyChartData" :options="lineChartOptions" />
                        <div v-else class="h-full flex items-center justify-center text-sm text-[color:var(--color-brand-400)]">No data for this range</div>
                    </div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">Orders by Day &amp; Channel</p>
                    <div class="h-56">
                        <Bar :data="dowChartData" :options="barChartOptions" />
                    </div>
                </div>
            </div>

            <!-- Sales Rank + Customer Count -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                <div class="xl:col-span-2 bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">Sales Rank</p>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-sm tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Employee</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                    <th class="px-4 py-2 text-right">Retail</th>
                                    <th class="px-4 py-2 text-right">Wholesale</th>
                                    <th class="px-4 py-2 text-right">Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!saleRank.length">
                                    <td colspan="6" class="px-4 py-8 text-center text-[color:var(--color-brand-400)] text-xs">No sales data for this period</td>
                                </tr>
                                <tr
                                    v-for="(emp, i) in saleRank" :key="emp.sma_id"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)] transition-colors"
                                >
                                    <td class="px-4 py-2.5 font-mono text-[color:var(--color-brand-400)]">{{ i + 1 }}</td>
                                    <td class="px-4 py-2.5 font-medium text-[color:var(--color-ink)]">{{ emp.name }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono font-semibold text-[color:var(--color-accent-700)]">{{ fmt(emp.total) }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmt(emp.retail) }} <span class="text-[10px] text-[color:var(--color-brand-400)]">({{ emp.retail_count }})</span></td>
                                    <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmt(emp.wholesale) }} <span class="text-[10px] text-[color:var(--color-brand-400)]">({{ emp.wholesale_count }})</span></td>
                                    <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-brand-600)]">{{ fmtN(emp.order_count) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                        <p class="text-[10px] uppercase tracking-wider font-medium text-[color:var(--color-brand-500)] mb-3">Total Customers</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">All</span><span class="font-mono font-semibold">{{ fmtN(customerCount.total) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">Retail</span><span class="font-mono">{{ fmtN(customerCount.retail) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">VIP</span><span class="font-mono">{{ fmtN(customerCount.vip) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">Shopee</span><span class="font-mono">{{ fmtN(customerCount.shopee) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">Lazada</span><span class="font-mono">{{ fmtN(customerCount.lazada) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">TikTok</span><span class="font-mono">{{ fmtN(customerCount.tiktok) }}</span></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                        <p class="text-[10px] uppercase tracking-wider font-medium text-[color:var(--color-brand-500)] mb-3">New Customers (Period)</p>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">Total</span><span class="font-mono font-semibold">{{ fmtN(newCustomers.total) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">Retail</span><span class="font-mono">{{ fmtN(newCustomers.retail) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-[color:var(--color-ink-soft)]">VIP</span><span class="font-mono">{{ fmtN(newCustomers.vip) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Best Sale Products -->
            <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
                <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)]">Best Selling Products</p>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-sm tnum">
                        <thead>
                            <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                <th class="px-4 py-2 text-left">#</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-right">Retail</th>
                                <th class="px-4 py-2 text-right">Wholesale</th>
                                <th class="px-4 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!bestSale.length">
                                <td colspan="6" class="px-4 py-8 text-center text-[color:var(--color-brand-400)] text-xs">No data for this period</td>
                            </tr>
                            <tr
                                v-for="(p, i) in bestSale" :key="i"
                                class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)] transition-colors"
                            >
                                <td class="px-4 py-2 font-mono text-[color:var(--color-brand-400)]">{{ i + 1 }}</td>
                                <td class="px-4 py-2 text-[color:var(--color-ink)] max-w-xs truncate">{{ p.name }}</td>
                                <td class="px-4 py-2 text-right font-mono font-semibold">{{ fmtN(p.total_qty) }}</td>
                                <td class="px-4 py-2 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmtN(p.retail_qty) }}</td>
                                <td class="px-4 py-2 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmtN(p.wholesale_qty) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 h-1.5 bg-[color:var(--color-brand-100)] rounded-full overflow-hidden">
                                            <div class="h-full bg-[color:var(--color-accent-400)] rounded-full" :style="{ width: p.percent + '%' }"/>
                                        </div>
                                        <span class="font-mono text-xs text-[color:var(--color-brand-500)]">{{ p.percent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Commissions -->
            <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
                <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex items-center gap-1 overflow-x-auto">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mr-3 shrink-0">Commissions</p>
                    <button
                        v-for="tab in commTabs" :key="tab.key"
                        @click="activeCommTab = tab.key"
                        class="px-3 py-1 rounded text-xs font-medium whitespace-nowrap transition-colors"
                        :class="activeCommTab === tab.key
                            ? 'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-800)]'
                            : 'text-[color:var(--color-brand-500)] hover:text-[color:var(--color-ink)]'"
                    >{{ tab.label }}</button>
                </div>
                <div class="p-5">
                    <template v-for="tab in commTabs" :key="tab.key">
                        <div v-if="activeCommTab === tab.key" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-[10px] text-[color:var(--color-brand-500)] uppercase tracking-wider mb-0.5">Sales Total</p>
                                <p class="font-mono font-semibold text-[color:var(--color-ink)]">{{ fmt(commissions[tab.key].total) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[color:var(--color-brand-500)] uppercase tracking-wider mb-0.5">Orders</p>
                                <p class="font-mono font-semibold text-[color:var(--color-ink)]">{{ fmtN(commissions[tab.key].orders) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[color:var(--color-brand-500)] uppercase tracking-wider mb-0.5">Commission ({{ commissions[tab.key].rate }})</p>
                                <p class="font-mono font-semibold text-[color:var(--color-accent-700)]">{{ fmt(commissions[tab.key].commission_1pct) }}</p>
                            </div>
                            <div v-if="commissions[tab.key].per_employee !== undefined">
                                <p class="text-[10px] text-[color:var(--color-brand-500)] uppercase tracking-wider mb-0.5">Per Employee (÷{{ commissions[tab.key].split_count }})</p>
                                <p class="font-mono font-semibold text-[color:var(--color-ink)]">{{ fmt(commissions[tab.key].per_employee) }}</p>
                            </div>
                            <div v-else>
                                <p class="text-[10px] text-[color:var(--color-brand-500)] uppercase tracking-wider mb-0.5">Full Commission (2%)</p>
                                <p class="font-mono font-semibold text-[color:var(--color-ink)]">{{ fmt(commissions[tab.key].commission) }}</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Brand Sales + Shipping -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">Sales by Brand</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div v-if="!brandSale.length" class="text-xs text-[color:var(--color-brand-400)] text-center py-4">No data</div>
                        <div v-for="b in brandSale" :key="b.id" class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-sm shrink-0" :style="{ background: b.color || '#aaa' }"/>
                            <span class="text-sm text-[color:var(--color-ink-soft)] w-28 truncate">{{ b.name }}</span>
                            <div class="flex-1 h-1.5 bg-[color:var(--color-brand-100)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full" :style="{ width: b.percent + '%', background: b.color || 'var(--color-accent-400)' }"/>
                            </div>
                            <span class="font-mono text-sm text-[color:var(--color-ink)] w-28 text-right">{{ fmt(b.amount) }}</span>
                            <span class="font-mono text-xs text-[color:var(--color-brand-400)] w-10 text-right">{{ b.percent }}%</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">Shipping Methods</p>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-sm tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-4 py-2 text-left">Method</th>
                                    <th class="px-4 py-2 text-right">Boxes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!shipping.length">
                                    <td colspan="2" class="px-4 py-6 text-center text-[color:var(--color-brand-400)] text-xs">No data</td>
                                </tr>
                                <tr
                                    v-for="s in shipping" :key="s.id"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]"
                                >
                                    <td class="px-4 py-2.5 text-[color:var(--color-ink)]">{{ s.name || s.code }}</td>
                                    <td class="px-4 py-2.5 text-right font-mono font-semibold">{{ fmtN(s.boxes) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </Deferred>
    </AppLayout>
</template>
