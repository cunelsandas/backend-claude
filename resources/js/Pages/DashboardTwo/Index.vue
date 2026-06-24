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
    dateRange:         { type: Object, required: true },
    year:              { type: Number, required: true },
    month:             { type: Number, required: true },
    categoryId:        { type: Number, default: null },
    categories:        { type: Array,  default: () => [] },
    salesChart:        { type: Array,  default: null },
    ordersChart:       { type: Array,  default: null },
    costRevenue:       { type: Array,  default: null },
    productByCategory: { type: Object, default: null },
    profitPie:         { type: Array,  default: null },
    countrySplit:      { type: Array,  default: null },
    csProfit:          { type: Array,  default: null },
    retailCount:       { type: Number, default: null },
    marketplace:       { type: Object, default: null },
    yearByCategory:    { type: Array,  default: null },
    monthByCategory:   { type: Array,  default: null },
    salesTrend:        { type: Array,  default: null },
    monthlyMatrix:     { type: Array,  default: null },
    quarterly:         { type: Array,  default: null },
    grossProfit:       { type: Array,  default: null },
    netProfit:         { type: Array,  default: null },
});

// ── Filters ───────────────────────────────────────────────────────────────
const dateFilter = ref([props.dateRange.start, props.dateRange.end]);
const yearOpts   = computed(() => { const c = new Date().getFullYear(); return [c, c-1, c-2, c-3]; });
const monthOpts  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

function reload(extra) {
    router.reload({
        data: {
            start: props.dateRange.start, end: props.dateRange.end,
            year: props.year, month: props.month, category_id: props.categoryId,
            ...extra,
        },
        preserveScroll: true,
    });
}
const applyDateRange = d => d?.length === 2 && reload({ start: d[0], end: d[1] });
const applyYear      = y => reload({ year: y });
const applyMonth     = m => reload({ month: m });
const applyCategory  = c => reload({ category_id: c });

// ── Formatters ────────────────────────────────────────────────────────────
const fmt  = n => Number(n ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtN = n => Number(n ?? 0).toLocaleString('th-TH');
const pct  = n => { const v = Number(n ?? 0); return (v >= 0 ? '+' : '') + v.toFixed(1) + '%'; };
const pctTone = n => Number(n ?? 0) >= 0 ? 'success' : 'danger';

// ── Chart datasets ────────────────────────────────────────────────────────
const salesChartData = computed(() => {
    const d = props.salesChart ?? [];
    return {
        labels: d.map(r => r.month),
        datasets: [
            { label: 'Retail',     data: d.map(r => r.retail),         borderColor: 'rgb(63,111,154)',  fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Wholesale',  data: d.map(r => r.wholesale),      borderColor: 'rgb(74,124,89)',   fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Credit ▲',   data: d.map(r => r.credit_current), borderColor: 'rgb(140,100,180)', fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Credit ↩',   data: d.map(r => r.credit_past),    borderColor: 'rgb(180,140,100)', fill: false, tension: 0.3, pointRadius: 2, borderDash: [4,3] },
            { label: 'Shopee',     data: d.map(r => r.shopee),         borderColor: 'rgb(238,77,45)',   fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Lazada',     data: d.map(r => r.lazada),         borderColor: 'rgb(245,130,32)',  fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'TikTok',     data: d.map(r => r.tiktok),         borderColor: 'rgb(0,0,0)',       fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Marketplace',data: d.map(r => r.marketplace),    borderColor: 'rgb(196,137,47)',  fill: false, tension: 0.3, pointRadius: 2, borderDash: [6,2] },
        ],
    };
});

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

const costRevenueData = computed(() => {
    const d = props.costRevenue ?? [];
    return {
        labels: d.map(r => r.month),
        datasets: [
            { label: 'Revenue',    data: d.map(r => r.revenue),    borderColor: 'rgb(74,124,89)',  backgroundColor: 'rgba(74,124,89,0.08)',  fill: true, tension: 0.3, pointRadius: 2 },
            { label: 'Purchases',  data: d.map(r => r.purchase),   borderColor: 'rgb(196,137,47)', backgroundColor: 'rgba(196,137,47,0.06)', fill: false, tension: 0.3, pointRadius: 2 },
            { label: 'Operations', data: d.map(r => r.operations), borderColor: 'rgb(179,73,63)',  backgroundColor: 'rgba(179,73,63,0.06)',  fill: false, tension: 0.3, pointRadius: 2 },
        ],
    };
});

const productCatPalette = ['rgb(63,111,154)','rgb(74,124,89)','rgb(196,137,47)','rgb(179,73,63)','rgb(140,100,180)'];
const productByCategoryData = computed(() => {
    const d = props.productByCategory ?? { products: [], months: [] };
    return {
        labels: d.months,
        datasets: d.products.map((p, i) => ({
            label: p.name || p.code,
            data: p.series,
            borderColor: productCatPalette[i % productCatPalette.length],
            backgroundColor: productCatPalette[i % productCatPalette.length].replace('rgb', 'rgba').replace(')', ',0.06)'),
            fill: false, tension: 0.3, pointRadius: 2,
        })),
    };
});

const PIE_COLORS = ['rgba(63,111,154,0.85)', 'rgba(74,124,89,0.85)', 'rgba(196,137,47,0.85)', 'rgba(179,73,63,0.85)', 'rgba(140,100,180,0.85)', 'rgba(180,140,100,0.85)', 'rgba(100,150,180,0.85)', 'rgba(120,180,120,0.85)'];

const profitPieData = computed(() => ({
    labels: (props.profitPie ?? []).map(r => r.name),
    datasets: [{ data: (props.profitPie ?? []).map(r => r.value), backgroundColor: PIE_COLORS, borderWidth: 1 }],
}));
const countryPieData = computed(() => ({
    labels: (props.countrySplit ?? []).map(r => r.name),
    datasets: [{ data: (props.countrySplit ?? []).map(r => r.value), backgroundColor: PIE_COLORS, borderWidth: 1 }],
}));
const yearCatPieData = computed(() => ({
    labels: (props.yearByCategory ?? []).slice(0, 8).map(r => r.name),
    datasets: [{ data: (props.yearByCategory ?? []).slice(0, 8).map(r => r.qty), backgroundColor: PIE_COLORS, borderWidth: 1 }],
}));
const monthCatPieData = computed(() => ({
    labels: (props.monthByCategory ?? []).slice(0, 8).map(r => r.name),
    datasets: [{ data: (props.monthByCategory ?? []).slice(0, 8).map(r => r.qty), backgroundColor: PIE_COLORS, borderWidth: 1 }],
}));

// CS Profit — horizontal stacked bar (one row per CS user)
const csProfitData = computed(() => {
    const d = props.csProfit ?? [];
    return {
        labels: d.map(r => r.name),
        datasets: [
            { label: 'Retail',      data: d.map(r => r.retail),      backgroundColor: 'rgba(63,111,154,0.8)' },
            { label: 'Wholesale',   data: d.map(r => r.wholesale),   backgroundColor: 'rgba(74,124,89,0.8)' },
            { label: 'Marketplace', data: d.map(r => r.marketplace), backgroundColor: 'rgba(196,137,47,0.8)' },
        ],
    };
});

// ── Chart options ─────────────────────────────────────────────────────────
const lineOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
    scales: {
        x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => fmtN(v) } },
    },
};
const barOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
    scales: {
        x: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
        y: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
    },
};
const pieOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12 } } },
};
const hBarOpts = {
    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
    plugins: { legend: { position: 'top', labels: { font: { size: 10 }, boxWidth: 12 } } },
    scales: {
        x: { stacked: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 }, callback: v => fmtN(v) } },
        y: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
    },
};

// ── Customer tables (API-loaded) ──────────────────────────────────────────
const retailRows    = ref([]);
const wholesaleRows = ref([]);
const loadingRetail    = ref(false);
const loadingWholesale = ref(false);

async function fetchCustomerTables() {
    const params = `start=${props.dateRange.start}&end=${props.dateRange.end}`;
    loadingRetail.value = true;
    fetch(`/dashboard-two/retail-customers?${params}`).then(r => r.json())
        .then(d => { retailRows.value = d; }).finally(() => { loadingRetail.value = false; });
    loadingWholesale.value = true;
    fetch(`/dashboard-two/wholesale-customers?${params}`).then(r => r.json())
        .then(d => { wholesaleRows.value = d; }).finally(() => { loadingWholesale.value = false; });
}
onMounted(fetchCustomerTables);
watch(() => [props.dateRange.start, props.dateRange.end], fetchCustomerTables);

// ── Total helpers for year-major tables ───────────────────────────────────
const monthLabels = monthOpts;
</script>

<template>
    <AppLayout>
        <template #header>Dashboard BMC</template>

        <!-- ═══════════════════════════════════════ FILTER BAR ──────────── -->
        <div class="flex flex-wrap items-center gap-4 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">Year</span>
                <div class="flex gap-1">
                    <button v-for="y in yearOpts" :key="y" @click="applyYear(y)"
                        class="px-2.5 py-1 rounded text-xs font-mono font-medium transition-colors"
                        :class="y === year ? 'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-800)]' : 'text-[color:var(--color-brand-500)] hover:text-[color:var(--color-ink)]'"
                    >{{ y }}</button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">Month</span>
                <select :value="month" @change="applyMonth($event.target.value)"
                    class="text-xs px-2 py-1 rounded border border-[color:var(--color-rule)] bg-white">
                    <option v-for="(m, i) in monthOpts" :key="i" :value="i+1">{{ m }}</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">Category</span>
                <select :value="categoryId" @change="applyCategory($event.target.value)"
                    class="text-xs px-2 py-1 rounded border border-[color:var(--color-rule)] bg-white min-w-32">
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <DateRangePicker class="ml-auto"
                :model-value="dateFilter" @update:model-value="applyDateRange"
                placeholder="Date range" />
        </div>

        <!-- ═══════════════════════════════════════ YEAR-DRIVEN BIG CHARTS ── -->
        <Deferred :data="['salesChart','ordersChart']">
            <template #fallback>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-72">
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="h-56 bg-[color:var(--color-brand-50)] rounded"/>
                    </div>
                </div>
            </template>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">ยอดขายรายเดือนแยกตามปี — {{ year }}</p>
                    <div class="h-64"><Line :data="salesChartData" :options="lineOpts" /></div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">จำนวนออเดอร์แยกตามปี — {{ year }}</p>
                    <div class="h-64"><Bar :data="ordersChartData" :options="barOpts" /></div>
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ COST & REVENUE + PRODUCT-CATEGORY ── -->
        <Deferred :data="['costRevenue','productByCategory']">
            <template #fallback>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-72">
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div class="h-56 bg-[color:var(--color-brand-50)] rounded"/>
                    </div>
                </div>
            </template>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">สรุปต้นทุนและรายได้ — {{ year }}</p>
                    <div class="h-64"><Line :data="costRevenueData" :options="lineOpts" /></div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-3">ยอดขายสินค้าแยกตามหมวดสินค้า — Top 5 ({{ year }})</p>
                    <div class="h-64">
                        <Line v-if="productByCategory?.products?.length" :data="productByCategoryData" :options="lineOpts" />
                        <div v-else class="h-full flex items-center justify-center text-xs text-[color:var(--color-brand-400)]">No sales for this category in {{ year }}</div>
                    </div>
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ FOUR PIE CHARTS ────── -->
        <Deferred :data="['profitPie','countrySplit','yearByCategory','monthByCategory']">
            <template #fallback>
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
                    <div v-for="i in 4" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-64">
                        <div class="h-3 w-24 bg-[color:var(--color-brand-100)] rounded mb-3"/>
                        <div class="h-44 bg-[color:var(--color-brand-50)] rounded-full mx-6"/>
                    </div>
                </div>
            </template>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">สัดส่วนรายได้ / ยอดขาย</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ dateRange.start }} → {{ dateRange.end }}</p>
                    <div class="h-44"><Doughnut :data="profitPieData" :options="pieOpts" /></div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">ในและต่างประเทศ</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ dateRange.start }} → {{ dateRange.end }}</p>
                    <div class="h-44"><Doughnut :data="countryPieData" :options="pieOpts" /></div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">ตามประเภท (ปี)</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ year }}</p>
                    <div class="h-44"><Doughnut :data="yearCatPieData" :options="pieOpts" /></div>
                </div>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">ตามประเภท (เดือน)</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ monthOpts[month-1] }} {{ year }}</p>
                    <div class="h-44"><Doughnut :data="monthCatPieData" :options="pieOpts" /></div>
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ CS PROFIT ─────────── -->
        <Deferred :data="['csProfit']">
            <template #fallback>
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse mb-6 h-72">
                    <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                    <div class="h-56 bg-[color:var(--color-brand-50)] rounded"/>
                </div>
            </template>
            <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] mb-6">
                <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-1">สัดส่วนรายได้และมูลค่าจากยอดขาย / CS</p>
                <p class="text-[10px] text-[color:var(--color-brand-400)] mb-3">{{ dateRange.start }} → {{ dateRange.end }}</p>
                <div v-if="!csProfit?.length" class="text-xs text-[color:var(--color-brand-400)] text-center py-8">No CS sales data for this period</div>
                <div v-else :style="{ height: Math.max(180, csProfit.length * 32 + 60) + 'px' }">
                    <Bar :data="csProfitData" :options="hBarOpts" />
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ QUARTERLY & MONTHLY ── -->
        <Deferred :data="['quarterly','monthlyMatrix']">
            <template #fallback>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div v-for="j in 5" :key="j" class="h-6 bg-[color:var(--color-brand-50)] rounded mb-1"/>
                    </div>
                </div>
            </template>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <!-- Quarterly Sales -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">ข้อมูลยอดขายรายไตรมาส</p>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-sm tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-3 py-2 text-left">Year</th>
                                    <th class="px-3 py-2 text-right">Q1</th>
                                    <th class="px-3 py-2 text-right">Q2</th>
                                    <th class="px-3 py-2 text-right">Q3</th>
                                    <th class="px-3 py-2 text-right">Q4</th>
                                    <th class="px-3 py-2 text-right bg-[color:var(--color-brand-50)]">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in quarterly" :key="row.year"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
                                    <td class="px-3 py-2 font-mono font-semibold">{{ row.year }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ fmt(row.q1) }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ fmt(row.q2) }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ fmt(row.q3) }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ fmt(row.q4) }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-[color:var(--color-accent-700)] bg-[color:var(--color-brand-50)]">{{ fmt(row.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Matrix -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">ข้อมูลยอดขายรายเดือน</p>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-xs tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-2 py-2 text-left sticky left-0 bg-white">Year</th>
                                    <th v-for="m in monthLabels" :key="m" class="px-1.5 py-2 text-right">{{ m }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in monthlyMatrix" :key="row.year"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
                                    <td class="px-2 py-2 font-mono font-semibold sticky left-0 bg-white">{{ row.year }}</td>
                                    <td v-for="(v, i) in row.months" :key="i" class="px-1.5 py-2 text-right font-mono whitespace-nowrap"
                                        :class="v > 0 ? 'text-[color:var(--color-ink)]' : 'text-[color:var(--color-brand-300)]'"
                                    >{{ fmtN(Math.round(v)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ GROSS & NET PROFIT ── -->
        <Deferred :data="['grossProfit','netProfit']">
            <template #fallback>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                    <div v-for="i in 2" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse">
                        <div class="h-3 w-40 bg-[color:var(--color-brand-100)] rounded mb-4"/>
                        <div v-for="j in 5" :key="j" class="h-6 bg-[color:var(--color-brand-50)] rounded mb-1"/>
                    </div>
                </div>
            </template>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
                <!-- Gross Profit -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex items-center gap-2">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">ข้อมูลกำไรขั้นต้นรายเดือน (Gross Profit)</p>
                        <span v-if="grossProfit?.[0] && !grossProfit[0].available"
                            class="text-[10px] px-1.5 py-0.5 rounded bg-[color:var(--color-brand-100)] text-[color:var(--color-brand-600)]">Finance DB n/a</span>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-xs tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-2 py-2 text-left sticky left-0 bg-white">Year</th>
                                    <th v-for="m in monthLabels" :key="m" class="px-1.5 py-2 text-right">{{ m }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in grossProfit" :key="row.year"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
                                    <td class="px-2 py-2 font-mono font-semibold sticky left-0 bg-white">{{ row.year }}</td>
                                    <td v-for="(cell, i) in row.months" :key="i" class="px-1.5 py-2 text-right font-mono whitespace-nowrap"
                                        :class="cell.profit >= 0 ? 'text-[color:var(--color-accent-700)]' : 'text-[color:var(--color-feedback-danger,#b3493f)]'"
                                    >{{ fmtN(Math.round(cell.profit)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Net Profit -->
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)]">
                    <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex items-center gap-2">
                        <p class="text-xs font-semibold text-[color:var(--color-ink)]">ข้อมูลกำไรสุทธิรายเดือน (Net Profit)</p>
                        <span v-if="netProfit?.[0] && !netProfit[0].available"
                            class="text-[10px] px-1.5 py-0.5 rounded bg-[color:var(--color-brand-100)] text-[color:var(--color-brand-600)]">Finance DB n/a</span>
                    </div>
                    <div class="overflow-auto">
                        <table class="w-full text-xs tnum">
                            <thead>
                                <tr class="border-b border-[color:var(--color-rule)] text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">
                                    <th class="px-2 py-2 text-left sticky left-0 bg-white">Year</th>
                                    <th v-for="m in monthLabels" :key="m" class="px-1.5 py-2 text-right">{{ m }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in netProfit" :key="row.year"
                                    class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
                                    <td class="px-2 py-2 font-mono font-semibold sticky left-0 bg-white">{{ row.year }}</td>
                                    <td v-for="(cell, i) in row.months" :key="i" class="px-1.5 py-2 text-right font-mono whitespace-nowrap"
                                        :class="cell.profit >= 0 ? 'text-[color:var(--color-accent-700)]' : 'text-[color:var(--color-feedback-danger,#b3493f)]'"
                                    >{{ fmtN(Math.round(cell.profit)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Deferred>

        <!-- ═══════════════════════════════════════ MARKETPLACE + COUNT + TREND -->
        <Deferred :data="['retailCount','marketplace','salesTrend']">
            <template #fallback>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                    <div v-for="i in 3" :key="i" class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)] animate-pulse h-32">
                        <div class="h-3 w-24 bg-[color:var(--color-brand-100)] rounded mb-3"/>
                        <div class="h-16 bg-[color:var(--color-brand-50)] rounded"/>
                    </div>
                </div>
            </template>

            <!-- KPI Strip -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] mb-1">Retail Customers</p>
                    <p class="font-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ fmtN(retailCount) }}</p>
                    <p class="text-[10px] text-[color:var(--color-brand-400)]">Unique customers in selected period</p>
                </div>
                <div class="xl:col-span-2 bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] p-4 shadow-[var(--shadow-paper)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)] mb-2">Marketplace Breakdown</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div v-for="[label, val] in [['Shopee',marketplace.shopee],['Lazada',marketplace.lazada],['TikTok',marketplace.tiktok],['Online',marketplace.online]]" :key="label"
                            class="rounded border border-[color:var(--color-rule)] p-2"
                        >
                            <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)]">{{ label }}</p>
                            <p class="font-mono font-semibold text-sm">{{ fmt(val) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-[color:var(--color-rule)] flex justify-between text-sm">
                        <span class="text-[color:var(--color-brand-500)]">Total</span>
                        <span class="font-mono font-bold text-[color:var(--color-accent-700)]">{{ fmt(marketplace.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Sales Trend Table -->
            <div class="bg-white rounded-[var(--radius-md)] border border-[color:var(--color-rule)] shadow-[var(--shadow-paper)] mb-6">
                <div class="px-4 py-3 border-b border-[color:var(--color-rule)]">
                    <p class="text-xs font-semibold text-[color:var(--color-ink)]">Monthly Sales Trend (24 months, with MoM &amp; YoY)</p>
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
                            <tr v-for="row in [...salesTrend].reverse()" :key="row.month"
                                class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
                                <td class="px-4 py-2 font-mono text-[color:var(--color-ink-soft)]">{{ row.month }}</td>
                                <td class="px-4 py-2 text-right font-mono font-semibold">{{ fmt(row.total) }}</td>
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

        <!-- ═══════════════════════════════════ CUSTOMER TABLES (API) ──── -->
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
                        <tr v-if="!retailRows.length && !loadingRetail">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-[color:var(--color-brand-400)]">No retail data for this period</td>
                        </tr>
                        <tr v-for="(r, i) in retailRows" :key="r.customer_id"
                            class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
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
                        <tr v-if="!wholesaleRows.length && !loadingWholesale">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-[color:var(--color-brand-400)]">No wholesale data for this period</td>
                        </tr>
                        <tr v-for="(r, i) in wholesaleRows" :key="r.customer_id"
                            class="border-b border-[color:var(--color-rule)] hover:bg-[color:var(--color-brand-50)]">
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
