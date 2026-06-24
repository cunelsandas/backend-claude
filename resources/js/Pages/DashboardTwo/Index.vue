<script setup>
import { Deferred, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale, LinearScale, BarElement, LineElement,
    PointElement, ArcElement, Filler, Tooltip, Legend, Title,
} from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';
import Badge from '@/Components/UI/Badge.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Filler, Tooltip, Legend, Title);

const props = defineProps({
    dateRange:   { type: Object, required: true },
    year:        { type: Number, required: true },
    salesChart:  { type: Array,  default: null },
    ordersChart: { type: Array,  default: null },
    profitPie:   { type: Array,  default: null },
    retailCount: { type: Number, default: null },
    marketplace: { type: Object, default: null },
    salesTrend:  { type: Array,  default: null },
});

// ── Date / Year filter ────────────────────────────────────────────────────
const dateFilter = ref([props.dateRange.start, props.dateRange.end]);
const yearOpts   = computed(() => {
    const cur = new Date().getFullYear();
    return [cur, cur - 1, cur - 2, cur - 3];
});

function applyDateRange(dates) {
    if (!dates || dates.length < 2) return;
    router.reload({ data: { start: dates[0], end: dates[1], year: props.year }, preserveScroll: true });
}
function applyYear(y) {
    router.reload({ data: { year: y, start: props.dateRange.start, end: props.dateRange.end }, preserveScroll: true });
}

// ── Formatters ────────────────────────────────────────────────────────────
const fmt  = n => Number(n ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = n => Number(n ?? 0).toLocaleString('th-TH');
const pct  = n => { const v = Number(n ?? 0); return (v >= 0 ? '+' : '') + v.toFixed(1) + '%'; };
const pctTone = n => Number(n ?? 0) >= 0 ? 'success' : 'danger';

// ── Sales Chart (8-series Line) ───────────────────────────────────────────
const salesChartData = computed(() => {
    const d = props.salesChart ?? [];
    return {
        labels: d.map(r => r.month),
        datasets: [
            { label: 'Retail',     data: d.map(r => r.retail),      borderColor: 'rgb(63,111,154)',  backgroundColor: 'rgba(63,111,154,0.06)',  fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Wholesale',  data: d.map(r => r.wholesale),   borderColor: 'rgb(74,124,89)',   backgroundColor: 'rgba(74,124,89,0.06)',   fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Credit ▲',  data: d.map(r => r.credit_current), borderColor: 'rgb(140,100,180)', fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Credit ↩',  data: d.map(r => r.credit_past),   borderColor: 'rgb(180,140,100)', fill: false, tension: 0.3, pointRadius: 2, borderDash: [4,3] },
            { label: 'Shopee',     data: d.map(r => r.shopee),      borderColor: 'rgb(238,77,45)',   fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Lazada',     data: d.map(r => r.lazada),      borderColor: 'rgb(245,130,32)',  fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'TikTok',     data: d.map(r => r.tiktok),      borderColor: 'rgb(0,0,0)',       fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Marketplace',data: d.map(r => r.marketplace), borderColor: 'rgb(196,137,47)',  backgroundColor: 'rgba(196,137,47,0.06)', fill: false, tension: 0.3, pointRadius: 2, borderDash: [6,2] },
        ],
    };
});
const lineOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => fmtN(v) } },
    },
};

// ── Orders Chart (3-series Bar) ───────────────────────────────────────────
const ordersChartData = computed(() => {
    const d = props.ordersChart ?? [];
    return {
        labels: d.map(r => r.month),
        datasets: [
            { label: 'Retail',      data: d.map(r => r.retail),      backgroundColor: 'rgba(63,111,154,0.7)' },
            { label: 'Wholesale',   data: d.map(r => r.wholesale),   backgroundColor: 'rgba(74,124,89,0.7)' },
            { label: 'Marketplace', data: d.map(r => r.marketplace), backgroundColor: 'rgba(196,137,47,0.7)' },
        ],
    };
});
const barOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
    scales: {
        x: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
};

// ── Profit Pie ────────────────────────────────────────────────────────────
const PIE_COLORS = ['rgba(63,111,154,0.8)', 'rgba(74,124,89,0.8)', 'rgba(196,137,47,0.8)'];
const profitPieData = computed(() => {
    const d = props.profitPie ?? [];
    return {
        labels: d.map(r => r.name),
        datasets: [{ data: d.map(r => r.value), backgroundColor: PIE_COLORS, borderWidth: 1 }],
    };
});
const pieOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
};

// ── Customer Tables (API-loaded) ──────────────────────────────────────────
const retailRows    = ref([]);
const wholesaleRows = ref([]);
const loadingRetail    = ref(false);
const loadingWholesale = ref(false);

async function fetchCustomerTables() {
    const params = `start=${props.dateRange.start}&end=${props.dateRange.end}`;

    loadingRetail.value = true;
    fetch(`/dashboard-two/retail-customers?${params}`)
        .then(r => r.json()).then(d => { retailRows.value = d; }).finally(() => { loadingRetail.value = false; });

    loadingWholesale.value = true;
    fetch(`/dashboard-two/wholesale-customers?${params}`)
        .then(r => r.json()).then(d => { wholesaleRows.value = d; }).finally(() => { loadingWholesale.value = false; });
}

onMounted(fetchCustomerTables);
watch(() => [props.dateRange.start, props.dateRange.end], fetchCustomerTables);
</script>

<template>
    <AppLayout>
        <template #header>Dashboard BMC</template>

        <!-- Filters ─────────────────────────────────────────────────────────-->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">Year</span>
                <div class="flex gap-1">
                    <button
                        v-for="y in yearOpts" :key="y"
                        @click="applyYear(y)"
                        class="px-2.5 py-1 rounded text-xs font-mono font-medium transition-colors"
                        :class="y === year
                            ? 'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-800)]'
                            : 'text-[color:var(--color-brand-500)] hover:text-[color:var(--color-ink)]'"
                    >{{ y }}</button>
                </div>
            </div>
            <DateRangePicker
                :model-value="dateFilter"
                @update:model-value="applyDateRange"
                placeholder="Date range (pie / count)"
            />
        </div>

        <!-- ═══════════════════════════════════════════════════════ DEFERRED -->
        <Deferred :data="['salesChart','ordersChart','profitPie','retailCount','marketplace','salesTrend']">

            <template #fallback>
                <!-- Chart skeletons -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i"
                        class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-72">
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="h-56 bg-[color:var(--color-brand-50)] rounded"/>
                    </div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-64">
                        <div class="h-3 w-24 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="h-48 bg-[color:var(--color-brand-50)] rounded-full mx-8"/>
                    </div>
                    <div class="xl:col-span-2 bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-3 w-32 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="grid grid-cols-2 gap-4">
                            <div v-for="i in 6" :key="i" class="h-12 bg-[color:var(--color-brand-50)] rounded"/>
                        </div>
                    </div>
                </div>
                <!-- Trend skeleton -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse mb-6">
                    <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                    <div v-for="i in 6" :key="i" class="flex gap-4 py-2 border-b border-[color:var(--color-brand-50)]">
                        <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="ml-auto h-3 w-24 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                        <div class="h-3 w-16 bg-[color:var(--color-brand-100)] rounded"/>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════ CONTENT ──────── -->

            <!-- Section 1: Monthly Charts -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">Monthly Sales by Channel — {{ year }}</p>
                    <div class="h-64">
                        <Line :data="salesChartData" :options="lineOpts" />
                    </div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">Monthly Orders by Channel — {{ year }}</p>
                    <div class="h-64">
                        <Bar :data="ordersChartData" :options="barOpts" />
                    </div>
                </div>
            </div>

            <!-- Section 2: Pie + Count + Marketplace -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                <!-- Profit Pie -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">Sales Split</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ dateRange.start }} → {{ dateRange.end }}</p>
                    <div class="h-48">
                        <Doughnut :data="profitPieData" :options="pieOpts" />
                    </div>
                    <div class="mt-3 space-y-1">
                        <div v-for="(seg, i) in profitPie" :key="seg.name" class="flex justify-between text-xs">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-sm" :style="{ background: PIE_COLORS[i] }"/>
                                {{ seg.name }}
                            </span>
                            <span class="font-mono font-semibold">{{ fmt(seg.value) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Marketplace + Retail Count -->
                <div class="xl:col-span-2 space-y-4">
                    <!-- Retail Count KPI -->
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                        <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] mb-1">Retail Customers</p>
                        <p class="font-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ fmtN(retailCount) }}</p>
                        <p class="text-[10px] text-[color:var(--color-brand-400)]">Unique customers in selected period</p>
                    </div>
                    <!-- Marketplace breakdown -->
                    <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">Marketplace Breakdown</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="[label, val, sub] in [
                                ['Shopee',    marketplace.shopee,    null],
                                ['Lazada',    marketplace.lazada,    null],
                                ['TikTok',    marketplace.tiktok,    `Live ${fmt(marketplace.tiktok_live)}`],
                                ['Online',    marketplace.online,    null],
                            ]" :key="label"
                                class="rounded border border-[color:var(--color-rule)] p-3"
                            >
                                <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] mb-0.5">{{ label }}</p>
                                <p class="font-mono font-semibold text-sm text-[color:var(--color-ink)]">{{ fmt(val) }}</p>
                                <p v-if="sub" class="text-[10px] text-[color:var(--color-brand-400)] mt-0.5">{{ sub }}</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-[color:var(--color-rule)] flex justify-between text-sm">
                            <span class="text-[color:var(--color-brand-500)]">Total Marketplace</span>
                            <span class="font-mono font-bold text-[color:var(--color-accent-700)]">{{ fmt(marketplace.total) }}</span>
                        </div>
                        <!-- TikTok sub-breakdown -->
                        <div class="mt-2 grid grid-cols-4 gap-2 text-[10px] text-[color:var(--color-brand-400)]">
                            <div>TikTok Base<br><span class="font-mono text-[color:var(--color-ink)]">{{ fmt(marketplace.tiktok_base) }}</span></div>
                            <div>Live<br><span class="font-mono text-[color:var(--color-ink)]">{{ fmt(marketplace.tiktok_live) }}</span></div>
                            <div>Affiliate<br><span class="font-mono text-[color:var(--color-ink)]">{{ fmt(marketplace.tiktok_affiliate) }}</span></div>
                            <div>Video<br><span class="font-mono text-[color:var(--color-ink)]">{{ fmt(marketplace.tiktok_video) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Monthly Sales Trend Table -->
            <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
                <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)]">Monthly Sales Trend (24 months)</p>
                </div>
                <div class="overflow-auto max-h-96">
                    <table class="w-full text-sm tnum">
                        <thead class="sticky top-0 bg-white">
                            <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                <th class="px-4 py-2 text-left">Month</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">MoM %</th>
                                <th class="px-4 py-2 text-right">YoY %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in [...salesTrend].reverse()" :key="row.month"
                                class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)] transition-colors"
                            >
                                <td class="px-4 py-2 font-mono text-[color:var(--color-ink-soft)]">{{ row.month }}</td>
                                <td class="px-4 py-2 text-right font-mono font-semibold text-[color:var(--color-ink)]">{{ fmt(row.total) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <Badge v-if="row.mom_pct !== null" :tone="pctTone(row.mom_pct)" size="sm">{{ pct(row.mom_pct) }}</Badge>
                                    <span v-else class="text-[10px] text-[color:var(--color-brand-300)]">—</span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <Badge v-if="row.yoy_pct !== null" :tone="pctTone(row.yoy_pct)" size="sm">{{ pct(row.yoy_pct) }}</Badge>
                                    <span v-else class="text-[10px] text-[color:var(--color-brand-300)]">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </Deferred>

        <!-- ═══════════════════════════════════════ CUSTOMER TABLES (API) ── -->

        <!-- Retail Customer Table -->
        <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
            <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex items-center gap-3">
                <p class="text-xs font-semibold text-[color:var(--color-ink)]">Retail Customers</p>
                <span class="text-[10px] text-[color:var(--color-brand-400)]">{{ dateRange.start }} → {{ dateRange.end }}</span>
                <div v-if="loadingRetail" class="ml-auto w-3 h-3 border border-[color:var(--color-accent-400)] border-t-transparent rounded-full animate-spin"/>
            </div>
            <div class="overflow-auto max-h-80">
                <table class="w-full text-sm tnum">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th class="px-4 py-2 text-right">Period Total</th>
                            <th class="px-4 py-2 text-right">Year Total</th>
                            <th class="px-4 py-2 text-right">Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingRetail && !retailRows.length">
                            <td colspan="6">
                                <div class="p-4 space-y-2 animate-pulse">
                                    <div v-for="i in 5" :key="i" class="flex gap-4">
                                        <div class="h-3 w-4 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="h-3 w-32 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="ml-auto h-3 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="h-3 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="!retailRows.length">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-[color:var(--color-brand-400)]">No retail data for this period</td>
                        </tr>
                        <tr
                            v-for="(r, i) in retailRows" :key="r.customer_id"
                            class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)] transition-colors"
                        >
                            <td class="px-4 py-2.5 font-mono text-[color:var(--color-brand-400)]">{{ i + 1 }}</td>
                            <td class="px-4 py-2.5 font-medium text-[color:var(--color-ink)]">{{ r.customer_name }}</td>
                            <td class="px-4 py-2.5 text-[color:var(--color-ink-soft)]">{{ r.customer_phone || '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-semibold text-[color:var(--color-accent-700)]">{{ fmt(r.filter_total) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmt(r.year_total) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-brand-600)]">{{ fmtN(r.filter_orders) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Wholesale Customer Table -->
        <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
            <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex items-center gap-3">
                <p class="text-xs font-semibold text-[color:var(--color-ink)]">Wholesale Customers</p>
                <span class="text-[10px] text-[color:var(--color-brand-400)]">{{ dateRange.start }} → {{ dateRange.end }}</span>
                <div v-if="loadingWholesale" class="ml-auto w-3 h-3 border border-[color:var(--color-accent-400)] border-t-transparent rounded-full animate-spin"/>
            </div>
            <div class="overflow-auto max-h-80">
                <table class="w-full text-sm tnum">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Customer</th>
                            <th class="px-4 py-2 text-left">Phone</th>
                            <th class="px-4 py-2 text-right">Period Total</th>
                            <th class="px-4 py-2 text-right">Year Total</th>
                            <th class="px-4 py-2 text-right">Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loadingWholesale && !wholesaleRows.length">
                            <td colspan="6">
                                <div class="p-4 space-y-2 animate-pulse">
                                    <div v-for="i in 5" :key="i" class="flex gap-4">
                                        <div class="h-3 w-4 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="h-3 w-32 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="ml-auto h-3 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                                        <div class="h-3 w-20 bg-[color:var(--color-brand-100)] rounded"/>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="!wholesaleRows.length">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-[color:var(--color-brand-400)]">No wholesale data for this period</td>
                        </tr>
                        <tr
                            v-for="(r, i) in wholesaleRows" :key="r.customer_id"
                            class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)] transition-colors"
                        >
                            <td class="px-4 py-2.5 font-mono text-[color:var(--color-brand-400)]">{{ i + 1 }}</td>
                            <td class="px-4 py-2.5 font-medium text-[color:var(--color-ink)]">{{ r.customer_name }}</td>
                            <td class="px-4 py-2.5 text-[color:var(--color-ink-soft)]">{{ r.customer_phone || '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-semibold text-[color:var(--color-accent-700)]">{{ fmt(r.filter_total) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-ink-soft)]">{{ fmt(r.year_total) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-[color:var(--color-brand-600)]">{{ fmtN(r.filter_orders) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AppLayout>
</template>
