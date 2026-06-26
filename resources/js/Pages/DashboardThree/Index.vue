<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import {
  Chart as ChartJS,
  ArcElement, BarElement, LineElement, PointElement,
  CategoryScale, LinearScale,
  Title, Tooltip, Legend,
} from 'chart.js'
import { Doughnut, Bar } from 'vue-chartjs'

ChartJS.register(
  ArcElement, BarElement, LineElement, PointElement,
  CategoryScale, LinearScale,
  Title, Tooltip, Legend,
)

const props = defineProps({
  dateRange: { type: Object,  default: () => ({ start: '', end: '' }) },
  categories:{ type: Array,   default: () => [] },
  csStaff:   { type: Array,   default: () => [] },
})

// ── State ─────────────────────────────────────────────────────────────────────
const activeTab  = ref('main')
const start      = ref(props.dateRange.start)
const end        = ref(props.dateRange.end)
const loaded     = ref({}) // keyed by tab name
const modalOpen  = ref(null)
const modalData  = ref({})

// per-tab data
const mainData       = ref(null)
const behaviorData   = ref(null)
const financialData  = ref(null)
const operationData  = ref(null)
const productData    = ref(null)

// sub-states for expandable tables / CRUD
const thaiAllData    = ref(null)
const exportAllData  = ref(null)
const thaiAllPage    = ref(1)
const exportAllPage  = ref(1)
const thaiSearch     = ref('')
const exportSearch   = ref('')
const tierFilter     = ref('')
const followupRows   = ref([])
const timelineRows   = ref([])
const pipelineDeals  = ref([])
const compRefs       = ref([])
const feedbackRows   = ref([])
const competitors    = ref([])
const slowMovers     = ref([])
const healthScores   = ref([])
const compSummary    = ref([])
const feedbackOverview = ref(null)

const loadingMap = ref({})
const errorMap   = ref({})

const tabs = [
  { key: 'main',      label: 'ภาพรวม' },
  { key: 'behavior',  label: 'Behavior' },
  { key: 'financial', label: 'Financial' },
  { key: 'operation', label: 'Operation' },
  { key: 'product',   label: 'Product' },
]

const STAGE_LABELS = {
  1:'นำเสนอ', 2:'คุยรายละเอียด', 3:'ส่งสินค้าทดลอง',
  4:'ติดตาม', 5:'ส่งใบเสนอราคา', 6:'ปิดการขาย',
}

// ── API helpers ───────────────────────────────────────────────────────────────
function qs(extra = {}) {
  const p = new URLSearchParams({ start: start.value, end: end.value, ...extra })
  return '?' + p.toString()
}

async function apiFetch(key, url) {
  loadingMap.value[key] = true
  errorMap.value[key]   = null
  try {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return await res.json()
  } catch (e) {
    errorMap.value[key] = e.message
    return null
  } finally {
    loadingMap.value[key] = false
  }
}

async function csrfPost(url, body = {}) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token,
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(body),
  })
}

async function csrfDelete(url) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
  return fetch(url, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
  })
}

// ── Load functions ────────────────────────────────────────────────────────────
async function loadMain() {
  const [summary, thai, exp, thaiT, expT] = await Promise.all([
    apiFetch('main_summary',  '/dashboard-three/api/summary'     + qs()),
    apiFetch('main_thai_c',   '/dashboard-three/api/thai-chart'  + qs()),
    apiFetch('main_exp_c',    '/dashboard-three/api/export-chart'+ qs()),
    apiFetch('main_thai_t',   '/dashboard-three/api/thai-top10'  + qs()),
    apiFetch('main_exp_t',    '/dashboard-three/api/export-top10'+ qs()),
  ])
  mainData.value = { summary, thai, exp, thaiTop: thaiT, expTop: expT }
  loaded.value.main = true
}

async function loadBehavior() {
  const [summary, charts, tier] = await Promise.all([
    apiFetch('beh_sum',   '/dashboard-three/behavior/api/summary' + qs()),
    apiFetch('beh_chrt',  '/dashboard-three/behavior/api/charts'  + qs()),
    apiFetch('beh_tier',  '/dashboard-three/behavior/api/tier-customers' + qs()),
  ])
  behaviorData.value = { summary, charts, tier }
  loaded.value.behavior = true
}

async function loadFinancial() {
  const [summary, charts, top, payment] = await Promise.all([
    apiFetch('fin_sum',  '/dashboard-three/financial/api/summary' + qs()),
    apiFetch('fin_chrt', '/dashboard-three/financial/api/charts'  + qs()),
    apiFetch('fin_top',  '/dashboard-three/financial/api/top'     + qs()),
    apiFetch('fin_pay',  '/dashboard-three/financial/api/payment' + qs()),
  ])
  financialData.value = { summary, charts, top, payment }
  loaded.value.financial = true
}

async function loadOperation() {
  const [summary, charts, followups, timeline, deals] = await Promise.all([
    apiFetch('op_sum',  '/dashboard-three/operation/api/summary'        + qs()),
    apiFetch('op_chrt', '/dashboard-three/operation/api/charts'         + qs()),
    apiFetch('op_fu',   '/dashboard-three/operation/api/followup'       + qs()),
    apiFetch('op_tl',   '/dashboard-three/operation/api/timeline'       + qs()),
    apiFetch('op_pd',   '/dashboard-three/operation/api/pipeline-deals' + qs()),
  ])
  operationData.value = { summary, charts }
  followupRows.value  = followups ?? []
  timelineRows.value  = timeline  ?? []
  pipelineDeals.value = deals     ?? []
  loaded.value.operation = true
}

async function loadProduct() {
  const [summary, charts, slow, health] = await Promise.all([
    apiFetch('prod_sum',  '/dashboard-three/product/api/summary'      + qs()),
    apiFetch('prod_chrt', '/dashboard-three/product/api/charts'       + qs()),
    apiFetch('prod_slow', '/dashboard-three/product/api/slow-movers'  + qs()),
    apiFetch('prod_hs',   '/dashboard-three/product/api/health-scores'),
  ])
  productData.value = { summary, charts }
  slowMovers.value  = slow   ?? []
  healthScores.value= health ?? []
  loaded.value.product = true
}

async function loadThaiAll() {
  const data = await apiFetch('thai_all', '/dashboard-three/api/thai-all' + qs({ page: thaiAllPage.value, search: thaiSearch.value }))
  thaiAllData.value = data
}

async function loadExportAll() {
  const data = await apiFetch('exp_all', '/dashboard-three/api/export-all' + qs({ page: exportAllPage.value, search: exportSearch.value }))
  exportAllData.value = data
}

async function loadCompRefs() {
  compRefs.value  = await apiFetch('comp_refs', '/dashboard-three/product/api/competitive') ?? []
  compSummary.value = await apiFetch('comp_sum', '/dashboard-three/product/api/competitive-summary') ?? []
}

async function loadFeedback() {
  feedbackRows.value    = await apiFetch('fb_rows', '/dashboard-three/product/api/feedback') ?? []
  feedbackOverview.value = await apiFetch('fb_ov',  '/dashboard-three/product/api/feedback-overview' + qs()) ?? null
  competitors.value      = await apiFetch('comps',  '/dashboard-three/product/api/competitors') ?? []
}

// ── Activate tab ──────────────────────────────────────────────────────────────
async function activateTab(key) {
  activeTab.value = key
  if (loaded.value[key]) return
  if (key === 'main')      return loadMain()
  if (key === 'behavior')  return loadBehavior()
  if (key === 'financial') return loadFinancial()
  if (key === 'operation') return loadOperation()
  if (key === 'product')   return loadProduct()
}

function onDateChange() {
  loaded.value = {}
  activateTab(activeTab.value)
}

// ── CRUD actions ──────────────────────────────────────────────────────────────
async function markDone(id) {
  await csrfPost(`/dashboard-three/operation/api/followup/${id}/done`)
  const row = followupRows.value.find(r => r.id === id)
  if (row) row.status = 1
}

async function deleteFollowup(id) {
  if (!confirm('ลบ follow-up นี้?')) return
  await csrfDelete(`/dashboard-three/operation/api/followup/${id}`)
  followupRows.value = followupRows.value.filter(r => r.id !== id)
}

async function saveFollowup() {
  await csrfPost('/dashboard-three/operation/api/followup', modalData.value)
  closeModal()
  const data = await apiFetch('op_fu', '/dashboard-three/operation/api/followup' + qs())
  followupRows.value = data ?? []
}

async function deleteDeal(id) {
  if (!confirm('ลบ deal นี้?')) return
  await csrfDelete(`/dashboard-three/operation/api/pipeline-deals/${id}`)
  pipelineDeals.value = pipelineDeals.value.filter(d => d.id !== id)
}

async function saveDeal() {
  await csrfPost('/dashboard-three/operation/api/pipeline-deals', modalData.value)
  closeModal()
  pipelineDeals.value = await apiFetch('op_pd', '/dashboard-three/operation/api/pipeline-deals') ?? []
}

async function saveCompRef() {
  await csrfPost('/dashboard-three/product/api/competitive', modalData.value)
  closeModal()
  await loadCompRefs()
}

async function deleteCompRef(id) {
  if (!confirm('ลบ?')) return
  await csrfDelete(`/dashboard-three/product/api/competitive/${id}`)
  compRefs.value = compRefs.value.filter(r => r.id !== id)
}

async function saveFeedback() {
  await csrfPost('/dashboard-three/product/api/feedback', modalData.value)
  closeModal()
  feedbackRows.value = await apiFetch('fb_rows', '/dashboard-three/product/api/feedback') ?? []
}

async function deleteFeedback(id) {
  if (!confirm('ลบ?')) return
  await csrfDelete(`/dashboard-three/product/api/feedback/${id}`)
  feedbackRows.value = feedbackRows.value.filter(r => r.id !== id)
}

// ── Modals ────────────────────────────────────────────────────────────────────
function openModal(type, defaults = {}) {
  modalOpen.value = type
  modalData.value = { ...defaults }
}

function closeModal() {
  modalOpen.value = null
  modalData.value = {}
}

function onEsc(e) { if (e.key === 'Escape') closeModal() }
onMounted(() => {
  document.addEventListener('keydown', onEsc)
  activateTab('main')
})
onUnmounted(() => document.removeEventListener('keydown', onEsc))

// ── Formatters / helpers ──────────────────────────────────────────────────────
const thb = (n) => '฿' + (+(n ?? 0)).toLocaleString('th-TH', { maximumFractionDigits: 0 })
const num = (n) => (+(n ?? 0)).toLocaleString('th-TH')
const pct = (n) => n == null ? null : `${n > 0 ? '+' : ''}${n}%`

const TIER_COLORS = {
  'S':  'bg-purple-100 text-purple-800',
  'A+': 'bg-indigo-100 text-indigo-800',
  'A':  'bg-blue-100 text-blue-800',
  'B':  'bg-teal-100 text-teal-800',
  'C':  'bg-green-100 text-green-800',
  'D':  'bg-amber-100 text-amber-800',
  'E':  'bg-orange-100 text-orange-800',
  'F':  'bg-red-100 text-red-800',
}
const tierClass = (t) => TIER_COLORS[t] ?? 'bg-gray-100 text-gray-600'

const CHART_COLORS = [
  '#3b82f6','#0ea5e9','#14b8a6','#f59e0b','#8b5cf6',
  '#ef4444','#10b981','#f97316','#6366f1','#ec4899',
]

// ── Chart data factories ──────────────────────────────────────────────────────
const mainThaiChart = computed(() => {
  const d = mainData.value?.thai
  if (!d) return null
  return {
    labels: d.labels,
    datasets: [{ data: d.values, backgroundColor: CHART_COLORS, borderWidth: 2, borderColor: '#fff' }],
  }
})

const mainExpChart = computed(() => {
  const d = mainData.value?.exp
  if (!d) return null
  return {
    labels: d.labels,
    datasets: [{ data: d.values, backgroundColor: CHART_COLORS, borderWidth: 2, borderColor: '#fff' }],
  }
})

const doughnutOpts = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } } },
}

const behLeadChart = computed(() => {
  const d = behaviorData.value?.charts?.lead_source
  if (!d) return null
  return {
    labels: d.labels,
    datasets: [{ data: d.values, backgroundColor: CHART_COLORS[0], borderRadius: 4 }],
  }
})

const behCycleChart = computed(() => {
  const d = behaviorData.value?.charts?.buying_cycle
  if (!d) return null
  return {
    labels: d.labels,
    datasets: [{ data: d.values, backgroundColor: CHART_COLORS[2], borderRadius: 4 }],
  }
})

const behContactChart = computed(() => {
  const d = behaviorData.value?.charts?.contact_type
  if (!d) return null
  return {
    labels: ['Inbound', 'Outbound'],
    datasets: [{ data: [d.inbound, d.outbound], backgroundColor: [CHART_COLORS[0], CHART_COLORS[3]] }],
  }
})

const finTopChart = computed(() => {
  const d = financialData.value?.charts?.top_profit
  if (!d?.length) return null
  return {
    labels: d.map(r => r.name),
    datasets: [
      { label: 'Revenue', data: d.map(r => r.revenue), backgroundColor: CHART_COLORS[0] },
      { label: 'Gross Profit', data: d.map(r => r.gross_profit), backgroundColor: CHART_COLORS[2] },
    ],
  }
})

const finDistChart = computed(() => {
  const d = financialData.value?.charts?.distribution
  if (!d) return null
  return {
    labels: ['สูง (≥25%)', 'กลาง (10–25%)', 'ต่ำ (<10%)'],
    datasets: [{
      data: [d.high, d.medium, d.low],
      backgroundColor: [CHART_COLORS[2], CHART_COLORS[3], CHART_COLORS[5]],
    }],
  }
})

const opFunnelChart = computed(() => {
  const d = operationData.value?.charts?.stage_funnel
  if (!d?.length) return null
  return {
    labels: d.map(r => r.label),
    datasets: [{ data: d.map(r => r.count), backgroundColor: CHART_COLORS[0], borderRadius: 4 }],
  }
})

const opPipelineChart = computed(() => {
  const d = operationData.value?.charts?.pipeline_deals
  if (!d?.length) return null
  const labels = d.map(r => STAGE_LABELS[r.stage] ?? r.stage)
  return {
    labels,
    datasets: [
      { label: 'Expected', data: d.map(r => r.expected ?? 0), backgroundColor: CHART_COLORS[0] + '99' },
      { label: 'Weighted', data: d.map(r => r.weighted ?? 0), backgroundColor: CHART_COLORS[2] },
    ],
  }
})

const prodTopChart = computed(() => {
  const d = productData.value?.charts?.top_products
  if (!d?.length) return null
  const top = d.slice(0, 15)
  return {
    labels: top.map(r => r.name),
    datasets: [{ label: 'Revenue', data: top.map(r => r.revenue), backgroundColor: CHART_COLORS[0], borderRadius: 4 }],
  }
})

const prodCatChart = computed(() => {
  const d = productData.value?.charts?.category_margin
  if (!d?.length) return null
  return {
    labels: d.map(r => r.category),
    datasets: [
      { label: 'Revenue', data: d.map(r => r.revenue), backgroundColor: CHART_COLORS[0] },
      { label: 'Gross Profit', data: d.map(r => r.gross_profit), backgroundColor: CHART_COLORS[2] },
    ],
  }
})

const hBarOpts = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { color: '#f1f5f9' } }, y: { grid: { display: false }, ticks: { font: { size: 11 } } } },
}

const barOpts = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: '#f1f5f9' } } },
}

// ── Pipeline grouped by stage ─────────────────────────────────────────────────
const pipelineByStage = computed(() => {
  const map = {}
  for (let s = 1; s <= 6; s++) map[s] = []
  for (const d of pipelineDeals.value) {
    if (map[d.stage]) map[d.stage].push(d)
  }
  return map
})

// ── Slow mover badge ──────────────────────────────────────────────────────────
function slowBadge(days) {
  if (days == null) return 'bg-gray-100 text-gray-500'
  if (days > 180) return 'bg-red-100 text-red-700'
  if (days > 90)  return 'bg-amber-100 text-amber-700'
  return 'bg-yellow-100 text-yellow-700'
}

// ── Tier filter for behavior customers ───────────────────────────────────────
const filteredTierCustomers = computed(() => {
  const rows = behaviorData.value?.tier ?? []
  if (!tierFilter.value) return rows
  return rows.filter(r => r.tier === tierFilter.value)
})
</script>

<template>
  <AppLayout>
    <template #header>Dashboard III</template>

    <!-- Top bar: date range + tabs -->
    <div class="mb-6">
      <!-- Date range -->
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <span class="text-xs font-medium text-[color:var(--color-muted)] uppercase tracking-wider">ช่วงวันที่</span>
        <input
          v-model="start"
          type="date"
          class="px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] bg-white text-[color:var(--color-ink)]"
          @change="onDateChange"
        />
        <span class="text-[color:var(--color-muted)]">—</span>
        <input
          v-model="end"
          type="date"
          class="px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] bg-white text-[color:var(--color-ink)]"
          @change="onDateChange"
        />
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 border-b border-[color:var(--color-rule)]">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activateTab(tab.key)"
          class="px-4 py-2 text-sm font-medium transition-colors relative"
          :class="activeTab === tab.key
            ? 'text-[color:var(--color-accent-400)] after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-[color:var(--color-accent-400)]'
            : 'text-[color:var(--color-muted)] hover:text-[color:var(--color-ink)]'"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  TAB: MAIN                                                             -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'main'">

      <!-- Skeleton -->
      <template v-if="!loaded.main">
        <div class="grid grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
          <div v-for="i in 6" :key="i" class="h-24 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
        <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="h-72 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
          <div class="h-72 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
      </template>

      <template v-else-if="mainData">
        <!-- KPI row -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
          <template v-for="(item, key) in {
            'ยอดขายรวม':     [mainData.summary?.current?.total_sales,     mainData.summary?.pct_change?.total_sales],
            'ยอดขายในประเทศ':[mainData.summary?.current?.thai_sales,      mainData.summary?.pct_change?.thai_sales],
            'ยอดขายต่างประเทศ':[mainData.summary?.current?.export_sales,  mainData.summary?.pct_change?.export_sales],
            'ลูกค้า':        [mainData.summary?.current?.total_customers,  mainData.summary?.pct_change?.total_customers],
            'ลูกค้าใหม่':   [mainData.summary?.current?.new_customers,    mainData.summary?.pct_change?.new_customers],
            'เฉลี่ย/ออเดอร์':[mainData.summary?.current?.avg_order,       mainData.summary?.pct_change?.avg_order],
          }" :key="key">
            <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
              <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">{{ key }}</p>
              <p class="text-lg font-semibold text-[color:var(--color-ink)]">
                {{ typeof item[0] === 'number' && item[0] > 999 ? thb(item[0]) : num(item[0]) }}
              </p>
              <p v-if="item[1] != null" class="text-xs mt-0.5" :class="item[1] > 0 ? 'text-emerald-600' : item[1] < 0 ? 'text-red-500' : 'text-gray-400'">
                {{ item[1] > 0 ? '↑' : item[1] < 0 ? '↓' : '' }} {{ pct(item[1]) }}
              </p>
            </div>
          </template>
        </div>

        <!-- Donut charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium text-[color:var(--color-ink)] mb-3">Top 10 ลูกค้าในประเทศ</p>
            <div class="h-64">
              <Doughnut v-if="mainThaiChart" :data="mainThaiChart" :options="doughnutOpts" />
            </div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium text-[color:var(--color-ink)] mb-3">Top 10 ลูกค้าต่างประเทศ</p>
            <div class="h-64">
              <Doughnut v-if="mainExpChart" :data="mainExpChart" :options="doughnutOpts" />
            </div>
          </div>
        </div>

        <!-- Top 10 tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <!-- Thailand -->
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
              <p class="text-sm font-medium">ลูกค้าในประเทศ Top 10</p>
              <button
                @click="openModal('thai_all'); loadThaiAll()"
                class="text-xs text-[color:var(--color-accent-400)] hover:underline"
              >ดูทั้งหมด →</button>
            </div>
            <table class="w-full text-xs">
              <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ชื่อ</th>
                <th class="text-center px-2 py-2">Tier</th>
                <th class="text-right px-2 py-2">Orders</th>
                <th class="text-right px-4 py-2">ยอดขาย</th>
                <th class="text-right px-4 py-2">Margin</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (mainData.thaiTop ?? [])" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-2 text-[color:var(--color-ink)]">{{ r.name }}</td>
                  <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                  <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.orders }}</td>
                  <td class="px-4 py-2 text-right font-medium">{{ thb(r.total) }}</td>
                  <td class="px-4 py-2 text-right" :class="r.margin > 20 ? 'text-emerald-600' : r.margin > 0 ? 'text-amber-600' : 'text-red-500'">{{ r.margin }}%</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Export -->
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
            <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
              <p class="text-sm font-medium">ลูกค้าต่างประเทศ Top 10</p>
              <button
                @click="openModal('exp_all'); loadExportAll()"
                class="text-xs text-[color:var(--color-accent-400)] hover:underline"
              >ดูทั้งหมด →</button>
            </div>
            <table class="w-full text-xs">
              <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ชื่อ</th>
                <th class="text-center px-2 py-2">Tier</th>
                <th class="text-right px-2 py-2">Orders</th>
                <th class="text-right px-4 py-2">ยอดขาย</th>
                <th class="text-right px-4 py-2">Margin</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (mainData.expTop ?? [])" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-2 text-[color:var(--color-ink)]">{{ r.name }}</td>
                  <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                  <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.orders }}</td>
                  <td class="px-4 py-2 text-right font-medium">{{ thb(r.total) }}</td>
                  <td class="px-4 py-2 text-right" :class="r.margin > 20 ? 'text-emerald-600' : r.margin > 0 ? 'text-amber-600' : 'text-red-500'">{{ r.margin }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  TAB: BEHAVIOR                                                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'behavior'">
      <template v-if="!loaded.behavior">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div v-for="i in 4" :key="i" class="h-24 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div v-for="i in 3" :key="i" class="h-64 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
      </template>

      <template v-else-if="behaviorData">
        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div v-for="(item, label) in {
            'ลูกค้าทั้งหมด':     [behaviorData.summary?.current?.total_customers, null],
            'ลูกค้าใหม่':        [behaviorData.summary?.current?.new_customers,   behaviorData.summary?.pct_change?.new_customers],
            'Conversion':        [behaviorData.summary?.current?.converted,        behaviorData.summary?.pct_change?.converted],
            'เฉลี่ยวันตัดสินใจ':[behaviorData.summary?.current?.avg_decision_days,null],
          }" :key="label" class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">{{ label }}</p>
            <p class="text-lg font-semibold text-[color:var(--color-ink)]">{{ num(item[0]) }}</p>
            <p v-if="item[1] != null" class="text-xs mt-0.5" :class="item[1] > 0 ? 'text-emerald-600' : item[1] < 0 ? 'text-red-500' : 'text-gray-400'">
              {{ item[1] > 0 ? '↑' : '↓' }} {{ pct(item[1]) }}
            </p>
          </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">Lead Source</p>
            <div class="h-64"><Bar v-if="behLeadChart" :data="behLeadChart" :options="barOpts" /></div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">ประเภทการติดต่อ</p>
            <div class="h-64"><Doughnut v-if="behContactChart" :data="behContactChart" :options="doughnutOpts" /></div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">วงจรการซื้อ</p>
            <div class="h-64"><Bar v-if="behCycleChart" :data="behCycleChart" :options="barOpts" /></div>
          </div>
        </div>

        <!-- Tier table -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
          <div class="flex items-center gap-2 px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="text-sm font-medium mr-4">ลูกค้าตาม Tier</p>
            <button
              v-for="t in ['', 'A+', 'A', 'B', 'C']" :key="t"
              @click="tierFilter = t"
              class="px-2.5 py-1 text-xs rounded-full border transition-colors"
              :class="tierFilter === t
                ? 'bg-[color:var(--color-brand-900)] text-white border-[color:var(--color-brand-900)]'
                : 'border-[color:var(--color-rule)] text-[color:var(--color-muted)] hover:border-[color:var(--color-brand-400)]'"
            >{{ t || 'ทั้งหมด' }}</button>
          </div>
          <table class="w-full text-xs">
            <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
              <th class="text-left px-4 py-2">ชื่อ</th>
              <th class="text-center px-2 py-2">Tier</th>
              <th class="text-right px-2 py-2">Orders</th>
              <th class="text-right px-4 py-2">ยอดขาย</th>
              <th class="text-right px-4 py-2">สั่งล่าสุด</th>
            </tr></thead>
            <tbody>
              <tr v-for="r in filteredTierCustomers" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ r.name }}</td>
                <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.orders }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ thb(r.total) }}</td>
                <td class="px-4 py-2 text-right text-[color:var(--color-muted)]">{{ r.last_order_date?.substring(0,10) ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  TAB: FINANCIAL                                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'financial'">
      <template v-if="!loaded.financial">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div v-for="i in 4" :key="i" class="h-24 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
        <div class="h-72 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse mb-4" />
      </template>

      <template v-else-if="financialData">
        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">รายได้รวม</p>
            <p class="text-lg font-semibold">{{ thb(financialData.summary?.current?.total_revenue) }}</p>
            <p v-if="financialData.summary?.pct_change?.total_revenue != null" class="text-xs mt-0.5"
              :class="financialData.summary.pct_change.total_revenue > 0 ? 'text-emerald-600' : 'text-red-500'">
              {{ financialData.summary.pct_change.total_revenue > 0 ? '↑' : '↓' }}
              {{ pct(financialData.summary.pct_change.total_revenue) }}
            </p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Gross Profit</p>
            <p class="text-lg font-semibold">{{ thb(financialData.summary?.current?.gross_profit) }}</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Margin %</p>
            <p class="text-lg font-semibold">{{ financialData.summary?.current?.margin_pct }}%</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4" :class="(financialData.summary?.current?.high_risk_customers ?? 0) > 0 ? 'border-red-200 bg-red-50' : ''">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">High Risk</p>
            <p class="text-lg font-semibold" :class="(financialData.summary?.current?.high_risk_customers ?? 0) > 0 ? 'text-red-600' : ''">
              {{ num(financialData.summary?.current?.high_risk_customers) }} ราย
            </p>
          </div>
        </div>

        <!-- Top profit horizontal bar -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4 mb-4">
          <p class="text-sm font-medium mb-3">Top 10 ลูกค้าตาม Gross Profit</p>
          <div class="h-72"><Bar v-if="finTopChart" :data="finTopChart" :options="{ ...barOpts, indexAxis: 'y' }" /></div>
        </div>

        <!-- Distribution + Payment analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">การกระจาย Margin</p>
            <div class="h-52"><Doughnut v-if="finDistChart" :data="finDistChart" :options="doughnutOpts" /></div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
            <p class="text-sm font-medium px-4 py-3 border-b border-[color:var(--color-rule)]">วิเคราะห์การชำระเงิน</p>
            <table class="w-full text-xs">
              <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">เงื่อนไข</th>
                <th class="text-right px-2 py-2">Bills</th>
                <th class="text-right px-2 py-2">Amount</th>
                <th class="text-right px-4 py-2">Avg Days</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (financialData.payment?.credit_buckets ?? [])" :key="r.term_bucket"
                  class="border-b border-[color:var(--color-rule)] last:border-0">
                  <td class="px-4 py-2">{{ r.term_bucket }}</td>
                  <td class="px-2 py-2 text-right">{{ r.bill_count }}</td>
                  <td class="px-2 py-2 text-right">{{ thb(r.total_amount) }}</td>
                  <td class="px-4 py-2 text-right">{{ r.avg_actual_days ? +r.avg_actual_days.toFixed(1) : '—' }}</td>
                </tr>
                <tr v-if="financialData.payment?.cod" class="bg-amber-50 border-t border-amber-100">
                  <td class="px-4 py-2 font-medium text-amber-700">COD (Marketplace)</td>
                  <td class="px-2 py-2 text-right">{{ financialData.payment.cod.bill_count }}</td>
                  <td class="px-2 py-2 text-right">{{ thb(financialData.payment.cod.total_amount) }}</td>
                  <td class="px-4 py-2 text-right">{{ financialData.payment.cod.avg_days }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top customers table -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
          <p class="text-sm font-medium px-4 py-3 border-b border-[color:var(--color-rule)]">ลูกค้า Top 20 ตามรายได้</p>
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ชื่อ</th>
                <th class="text-center px-2 py-2">Tier</th>
                <th class="text-right px-2 py-2">Orders</th>
                <th class="text-right px-3 py-2">Revenue</th>
                <th class="text-right px-3 py-2">Gross Profit</th>
                <th class="text-right px-4 py-2">Margin</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (financialData.top ?? [])" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-2">{{ r.name }}</td>
                  <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                  <td class="px-2 py-2 text-right">{{ r.orders }}</td>
                  <td class="px-3 py-2 text-right font-medium">{{ thb(r.revenue) }}</td>
                  <td class="px-3 py-2 text-right">{{ thb(r.gross_profit) }}</td>
                  <td class="px-4 py-2 text-right" :class="r.margin_pct > 20 ? 'text-emerald-600' : r.margin_pct > 0 ? 'text-amber-600' : 'text-red-500'">{{ r.margin_pct }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  TAB: OPERATION                                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'operation'">
      <template v-if="!loaded.operation">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div v-for="i in 4" :key="i" class="h-24 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
        <div class="h-72 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse mb-4" />
      </template>

      <template v-else-if="operationData">
        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">ติดต่อทั้งหมด</p>
            <p class="text-lg font-semibold">{{ num(operationData.summary?.total_contacts) }}</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Follow-up รอดำเนินการ</p>
            <p class="text-lg font-semibold">{{ num(operationData.summary?.followups_pending) }}</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Open Deals</p>
            <p class="text-lg font-semibold">{{ num(operationData.summary?.open_deals) }}</p>
          </div>
          <div
            class="border rounded-[var(--radius-sm)] p-4"
            :class="(operationData.summary?.overdue_followups ?? 0) > 0
              ? 'bg-red-50 border-red-200'
              : 'bg-white border-[color:var(--color-rule)]'"
          >
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Overdue</p>
            <p class="text-lg font-semibold" :class="(operationData.summary?.overdue_followups ?? 0) > 0 ? 'text-red-600' : ''">
              {{ num(operationData.summary?.overdue_followups) }}
            </p>
          </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">Stage Funnel (สถานะลูกค้า)</p>
            <div class="h-56"><Bar v-if="opFunnelChart" :data="opFunnelChart" :options="{ ...hBarOpts, plugins: { legend: { display: false } } }" /></div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">Pipeline Deals ตาม Stage</p>
            <div class="h-56"><Bar v-if="opPipelineChart" :data="opPipelineChart" :options="barOpts" /></div>
          </div>
        </div>

        <!-- Pipeline Kanban -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] mb-4">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="text-sm font-medium">Pipeline Deals</p>
            <button
              @click="openModal('add_deal', { stage: 1, probability: 50 })"
              class="text-xs px-3 py-1 bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]"
            >+ Deal</button>
          </div>
          <div class="overflow-x-auto">
            <div class="flex gap-3 p-3 min-w-max">
              <div v-for="s in [1,2,3,4,5,6]" :key="s" class="w-44 shrink-0">
                <p class="text-[11px] font-semibold text-[color:var(--color-muted)] uppercase tracking-wider mb-2 px-1">{{ STAGE_LABELS[s] }}</p>
                <div class="space-y-2">
                  <div
                    v-for="deal in pipelineByStage[s]" :key="deal.id"
                    class="bg-gray-50 border border-[color:var(--color-rule)] rounded p-2 relative"
                  >
                    <button @click="deleteDeal(deal.id)" class="absolute top-1 right-1 text-gray-300 hover:text-red-400 text-xs">×</button>
                    <p class="text-xs font-medium text-[color:var(--color-ink)] pr-3 truncate">{{ deal.customer_name }}</p>
                    <p class="text-xs text-emerald-600 font-medium">{{ thb(deal.expected_value) }}</p>
                    <p class="text-[10px] text-[color:var(--color-muted)]">{{ deal.probability }}% likely</p>
                  </div>
                  <div v-if="!pipelineByStage[s].length" class="text-[11px] text-gray-300 text-center py-2">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Follow-up table -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] mb-4">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="text-sm font-medium">Follow-up</p>
            <button
              @click="openModal('add_followup', { follow_up_date: start, next_due_date: '' })"
              class="text-xs px-3 py-1 bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]"
            >+ Follow-up</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ลูกค้า</th>
                <th class="text-left px-2 py-2">CS</th>
                <th class="text-left px-2 py-2">Stage</th>
                <th class="text-left px-2 py-2">Due</th>
                <th class="text-left px-3 py-2">Note</th>
                <th class="px-4 py-2"></th>
              </tr></thead>
              <tbody>
                <tr
                  v-for="r in followupRows" :key="r.id"
                  class="border-b border-[color:var(--color-rule)] last:border-0"
                  :class="{
                    'bg-red-50':   r.urgency === 'overdue',
                    'bg-amber-50': r.urgency === 'today',
                    'opacity-50':  r.status === 1,
                  }"
                >
                  <td class="px-4 py-2" :class="r.status === 1 ? 'line-through' : ''">{{ r.customer_name }}</td>
                  <td class="px-2 py-2 text-[color:var(--color-muted)]">{{ r.cs_name ?? '—' }}</td>
                  <td class="px-2 py-2">{{ STAGE_LABELS[r.stage] ?? '—' }}</td>
                  <td class="px-2 py-2" :class="r.urgency === 'overdue' ? 'text-red-600 font-medium' : ''">{{ r.next_due_date?.substring(0,10) ?? '—' }}</td>
                  <td class="px-3 py-2 max-w-xs truncate text-[color:var(--color-muted)]">{{ r.notes ?? '—' }}</td>
                  <td class="px-4 py-2 flex gap-1 justify-end">
                    <button v-if="r.status === 0" @click="markDone(r.id)" class="px-2 py-0.5 text-[10px] bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200">Done</button>
                    <button @click="deleteFollowup(r.id)" class="px-2 py-0.5 text-[10px] bg-red-100 text-red-600 rounded hover:bg-red-200">Del</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
          <p class="text-sm font-medium px-4 py-3 border-b border-[color:var(--color-rule)]">Activity Timeline</p>
          <div class="divide-y divide-[color:var(--color-rule)] max-h-96 overflow-y-auto">
            <div v-for="(e, i) in timelineRows.slice(0,50)" :key="i" class="flex items-start gap-3 px-4 py-3">
              <span class="text-[10px] text-[color:var(--color-muted)] shrink-0 w-20">{{ String(e.event_at).substring(0,10) }}</span>
              <span class="px-1.5 py-0.5 text-[10px] rounded font-medium shrink-0" :class="{
                'bg-blue-100 text-blue-700':   e.type?.includes('Sales'),
                'bg-teal-100 text-teal-700':    e.type?.includes('CS'),
                'bg-amber-100 text-amber-700':  e.type?.includes('ติดตาม'),
                'bg-purple-100 text-purple-700':e.type?.includes('ออเดอร์'),
              }">{{ e.type }}</span>
              <span class="text-xs font-medium text-[color:var(--color-ink)] shrink-0">{{ e.customer_name }}</span>
              <span class="text-xs text-[color:var(--color-muted)] truncate">{{ e.note }}</span>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  TAB: PRODUCT                                                          -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'product'">
      <template v-if="!loaded.product">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div v-for="i in 4" :key="i" class="h-24 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse" />
        </div>
        <div class="h-72 rounded-[var(--radius-sm)] bg-gray-100 animate-pulse mb-4" />
      </template>

      <template v-else-if="productData">
        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">SKU ที่ขาย</p>
            <p class="text-lg font-semibold">{{ num(productData.summary?.current?.sku_count) }}</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Revenue</p>
            <p class="text-lg font-semibold">{{ thb(productData.summary?.current?.revenue) }}</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Margin %</p>
            <p class="text-lg font-semibold">{{ productData.summary?.current?.margin_pct }}%</p>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4" :class="(productData.summary?.current?.slow_movers ?? 0) > 5 ? 'border-amber-200 bg-amber-50' : ''">
            <p class="text-[11px] uppercase tracking-wider text-[color:var(--color-muted)] mb-1">Slow Movers</p>
            <p class="text-lg font-semibold" :class="(productData.summary?.current?.slow_movers ?? 0) > 5 ? 'text-amber-600' : ''">
              {{ num(productData.summary?.current?.slow_movers) }}
            </p>
          </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">Top 15 Products (Revenue)</p>
            <div class="h-80"><Bar v-if="prodTopChart" :data="prodTopChart" :options="{ ...hBarOpts, plugins: { legend: { display: false } } }" /></div>
          </div>
          <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] p-4">
            <p class="text-sm font-medium mb-3">Category Margin</p>
            <div class="h-80"><Bar v-if="prodCatChart" :data="prodCatChart" :options="barOpts" /></div>
          </div>
        </div>

        <!-- Slow movers table -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] mb-4">
          <p class="text-sm font-medium px-4 py-3 border-b border-[color:var(--color-rule)]">Slow Movers (≥60 วันไม่ขาย)</p>
          <table class="w-full text-xs">
            <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
              <th class="text-left px-4 py-2">ชื่อ</th>
              <th class="text-left px-2 py-2">Code</th>
              <th class="text-left px-2 py-2">Category</th>
              <th class="text-right px-2 py-2">ขายล่าสุด</th>
              <th class="text-right px-4 py-2">วันที่ไม่ขาย</th>
            </tr></thead>
            <tbody>
              <tr v-for="r in slowMovers" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ r.name }}</td>
                <td class="px-2 py-2 font-mono text-[color:var(--color-muted)]">{{ r.code }}</td>
                <td class="px-2 py-2 text-[color:var(--color-muted)]">{{ r.category }}</td>
                <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.last_sale_date?.substring(0,10) ?? 'ไม่เคย' }}</td>
                <td class="px-4 py-2 text-right">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="slowBadge(r.days_since)">
                    {{ r.days_since ?? '∞' }} วัน
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Health Scores -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] mb-4">
          <p class="text-sm font-medium px-4 py-3 border-b border-[color:var(--color-rule)]">Product Health Scores</p>
          <table class="w-full text-xs">
            <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
              <th class="text-left px-4 py-2">ชื่อ</th>
              <th class="text-right px-2 py-2">Score</th>
              <th class="text-right px-2 py-2">Velocity</th>
              <th class="text-right px-2 py-2">Margin</th>
              <th class="text-right px-2 py-2">Consistency</th>
              <th class="text-right px-4 py-2">Revenue</th>
            </tr></thead>
            <tbody>
              <tr v-for="r in healthScores.slice(0,30)" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ r.name }}</td>
                <td class="px-2 py-2 text-right">
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                    :class="r.score >= 80 ? 'bg-emerald-100 text-emerald-700' : r.score >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600'">
                    {{ r.score }}
                  </span>
                </td>
                <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.velocity_score }}</td>
                <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.margin_score }}</td>
                <td class="px-2 py-2 text-right text-[color:var(--color-muted)]">{{ r.consistency_score }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ thb(r.total_revenue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Competitive References -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] mb-4">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="text-sm font-medium">ราคาคู่แข่ง</p>
            <div class="flex gap-2">
              <button
                v-if="!compRefs.length"
                @click="loadCompRefs()"
                class="text-xs text-[color:var(--color-accent-400)] hover:underline"
              >โหลดข้อมูล</button>
              <button
                @click="openModal('add_comp_ref', { recorded_at: new Date().toISOString().substring(0,10) })"
                class="text-xs px-3 py-1 bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]"
              >+ เพิ่ม</button>
            </div>
          </div>
          <table class="w-full text-xs">
            <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
              <th class="text-left px-4 py-2">ลูกค้า</th>
              <th class="text-left px-2 py-2">Product</th>
              <th class="text-left px-2 py-2">Competitor</th>
              <th class="text-right px-2 py-2">ราคาคู่แข่ง</th>
              <th class="text-right px-2 py-2">ราคาเรา</th>
              <th class="text-right px-4 py-2">วันที่</th>
              <th class="px-4 py-2"></th>
            </tr></thead>
            <tbody>
              <tr v-for="r in compRefs" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ r.customer_name }}</td>
                <td class="px-2 py-2">{{ r.product_name }}</td>
                <td class="px-2 py-2">{{ r.competitor_display_name || r.competitor_name }}</td>
                <td class="px-2 py-2 text-right">{{ thb(r.competitor_price) }}</td>
                <td class="px-2 py-2 text-right">{{ r.our_price ? thb(r.our_price) : '—' }}</td>
                <td class="px-4 py-2 text-right text-[color:var(--color-muted)]">{{ r.recorded_at?.substring(0,10) }}</td>
                <td class="px-4 py-2 text-right"><button @click="deleteCompRef(r.id)" class="text-red-400 hover:text-red-600 text-[10px]">Del</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Feedback -->
        <div class="bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="text-sm font-medium">Feedback สินค้า</p>
            <div class="flex gap-2">
              <button
                v-if="!feedbackRows.length"
                @click="loadFeedback()"
                class="text-xs text-[color:var(--color-accent-400)] hover:underline"
              >โหลดข้อมูล</button>
              <button
                @click="openModal('add_feedback')"
                class="text-xs px-3 py-1 bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]"
              >+ เพิ่ม</button>
            </div>
          </div>
          <table class="w-full text-xs">
            <thead><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
              <th class="text-left px-4 py-2">สินค้า</th>
              <th class="text-left px-2 py-2">ลูกค้า</th>
              <th class="text-left px-2 py-2">ประเภท</th>
              <th class="text-left px-2 py-2">หมวด</th>
              <th class="text-right px-2 py-2">Score</th>
              <th class="text-left px-3 py-2">ความคิดเห็น</th>
              <th class="px-4 py-2"></th>
            </tr></thead>
            <tbody>
              <tr v-for="r in feedbackRows" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                <td class="px-4 py-2">{{ r.product_name }}</td>
                <td class="px-2 py-2 text-[color:var(--color-muted)]">{{ r.customer_name ?? '—' }}</td>
                <td class="px-2 py-2">{{ r.type }}</td>
                <td class="px-2 py-2 text-[color:var(--color-muted)]">{{ r.category ?? '—' }}</td>
                <td class="px-2 py-2 text-right">
                  <span v-if="r.score" class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                    :class="r.score >= 4 ? 'bg-emerald-100 text-emerald-700' : r.score >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600'">
                    {{ r.score }}/5
                  </span>
                  <span v-else class="text-gray-300">—</span>
                </td>
                <td class="px-3 py-2 max-w-xs truncate text-[color:var(--color-muted)]">{{ r.text ?? '—' }}</td>
                <td class="px-4 py-2 text-right"><button @click="deleteFeedback(r.id)" class="text-red-400 hover:text-red-600 text-[10px]">Del</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!--  MODALS                                                                -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->

    <!-- Overlay -->
    <Teleport to="body">
      <div
        v-if="modalOpen"
        class="fixed inset-0 bg-black/40 z-40 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <!-- All Thailand customers -->
        <div v-if="modalOpen === 'thai_all'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-2xl max-h-[80vh] flex flex-col">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">ลูกค้าในประเทศ — ทั้งหมด</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-3 border-b border-[color:var(--color-rule)]">
            <input v-model="thaiSearch" @input="thaiAllPage = 1; loadThaiAll()" type="text" placeholder="ค้นหาชื่อ..." class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
          </div>
          <div class="overflow-auto flex-1">
            <table class="w-full text-xs">
              <thead class="sticky top-0 bg-white"><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ชื่อ</th>
                <th class="text-center px-2 py-2">Tier</th>
                <th class="text-right px-2 py-2">Orders</th>
                <th class="text-right px-4 py-2">ยอดขาย</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (thaiAllData?.data ?? [])" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-2">{{ r.name }}</td>
                  <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                  <td class="px-2 py-2 text-right">{{ r.orders }}</td>
                  <td class="px-4 py-2 text-right font-medium">{{ thb(r.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="thaiAllData" class="flex items-center justify-between px-4 py-2 border-t border-[color:var(--color-rule)] text-xs text-[color:var(--color-muted)]">
            <span>{{ thaiAllData.total }} รายการ</span>
            <div class="flex gap-1">
              <button :disabled="thaiAllPage <= 1" @click="thaiAllPage--; loadThaiAll()" class="px-2 py-1 border border-[color:var(--color-rule)] rounded disabled:opacity-30">←</button>
              <span class="px-2 py-1">{{ thaiAllPage }}/{{ thaiAllData.last_page }}</span>
              <button :disabled="thaiAllPage >= thaiAllData.last_page" @click="thaiAllPage++; loadThaiAll()" class="px-2 py-1 border border-[color:var(--color-rule)] rounded disabled:opacity-30">→</button>
            </div>
          </div>
        </div>

        <!-- All Export customers -->
        <div v-if="modalOpen === 'exp_all'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-2xl max-h-[80vh] flex flex-col">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">ลูกค้าต่างประเทศ — ทั้งหมด</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-3 border-b border-[color:var(--color-rule)]">
            <input v-model="exportSearch" @input="exportAllPage = 1; loadExportAll()" type="text" placeholder="ค้นหาชื่อ..." class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
          </div>
          <div class="overflow-auto flex-1">
            <table class="w-full text-xs">
              <thead class="sticky top-0 bg-white"><tr class="text-[color:var(--color-muted)] border-b border-[color:var(--color-rule)]">
                <th class="text-left px-4 py-2">ชื่อ</th>
                <th class="text-center px-2 py-2">Tier</th>
                <th class="text-right px-2 py-2">Orders</th>
                <th class="text-right px-4 py-2">ยอดขาย</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in (exportAllData?.data ?? [])" :key="r.id" class="border-b border-[color:var(--color-rule)] last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-2">{{ r.name }}</td>
                  <td class="px-2 py-2 text-center"><span class="px-1.5 py-0.5 rounded text-[10px] font-medium" :class="tierClass(r.tier)">{{ r.tier || '—' }}</span></td>
                  <td class="px-2 py-2 text-right">{{ r.orders }}</td>
                  <td class="px-4 py-2 text-right font-medium">{{ thb(r.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="exportAllData" class="flex items-center justify-between px-4 py-2 border-t border-[color:var(--color-rule)] text-xs text-[color:var(--color-muted)]">
            <span>{{ exportAllData.total }} รายการ</span>
            <div class="flex gap-1">
              <button :disabled="exportAllPage <= 1" @click="exportAllPage--; loadExportAll()" class="px-2 py-1 border border-[color:var(--color-rule)] rounded disabled:opacity-30">←</button>
              <span class="px-2 py-1">{{ exportAllPage }}/{{ exportAllData.last_page }}</span>
              <button :disabled="exportAllPage >= exportAllData.last_page" @click="exportAllPage++; loadExportAll()" class="px-2 py-1 border border-[color:var(--color-rule)] rounded disabled:opacity-30">→</button>
            </div>
          </div>
        </div>

        <!-- Add Follow-up -->
        <div v-if="modalOpen === 'add_followup'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">เพิ่ม Follow-up</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Customer ID</label>
              <input v-model.number="modalData.customer_id" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" placeholder="Customer ID" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">Stage</label>
                <select v-model.number="modalData.stage" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
                  <option v-for="(label, s) in STAGE_LABELS" :key="s" :value="Number(s)">{{ label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">CS Staff</label>
                <select v-model.number="modalData.cs_id" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
                  <option :value="null">— ไม่ระบุ —</option>
                  <option v-for="s in csStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">วันที่ Follow-up</label>
                <input v-model="modalData.follow_up_date" type="date" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
              </div>
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">Next Due</label>
                <input v-model="modalData.next_due_date" type="date" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Expected Value</label>
              <input v-model.number="modalData.expected_value" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" placeholder="0" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Note</label>
              <textarea v-model="modalData.notes" rows="2" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
          </div>
          <div class="flex justify-end gap-2 px-4 py-3 border-t border-[color:var(--color-rule)]">
            <button @click="closeModal" class="px-4 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] hover:bg-gray-50">ยกเลิก</button>
            <button @click="saveFollowup" class="px-4 py-1.5 text-sm bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]">บันทึก</button>
          </div>
        </div>

        <!-- Add Pipeline Deal -->
        <div v-if="modalOpen === 'add_deal'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">เพิ่ม Pipeline Deal</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Customer ID</label>
              <input v-model.number="modalData.customer_id" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">Stage</label>
                <select v-model.number="modalData.stage" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
                  <option v-for="(label, s) in STAGE_LABELS" :key="s" :value="Number(s)">{{ label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">Probability %</label>
                <input v-model.number="modalData.probability" type="number" min="0" max="100" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Expected Value (฿)</label>
              <input v-model.number="modalData.expected_value" type="number" min="0" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Notes</label>
              <textarea v-model="modalData.notes" rows="2" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
          </div>
          <div class="flex justify-end gap-2 px-4 py-3 border-t border-[color:var(--color-rule)]">
            <button @click="closeModal" class="px-4 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] hover:bg-gray-50">ยกเลิก</button>
            <button @click="saveDeal" class="px-4 py-1.5 text-sm bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]">บันทึก</button>
          </div>
        </div>

        <!-- Add Competitive Ref -->
        <div v-if="modalOpen === 'add_comp_ref'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">เพิ่มราคาคู่แข่ง</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Customer ID</label>
              <input v-model.number="modalData.customer_id" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">ชื่อสินค้า</label>
              <input v-model="modalData.product_name" type="text" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">คู่แข่ง</label>
              <input v-model="modalData.competitor_name" type="text" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">ราคาคู่แข่ง</label>
                <input v-model.number="modalData.competitor_price" type="number" min="0" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
              </div>
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">ราคาเรา</label>
                <input v-model.number="modalData.our_price" type="number" min="0" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">วันที่</label>
              <input v-model="modalData.recorded_at" type="date" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Notes</label>
              <textarea v-model="modalData.notes" rows="2" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
          </div>
          <div class="flex justify-end gap-2 px-4 py-3 border-t border-[color:var(--color-rule)]">
            <button @click="closeModal" class="px-4 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] hover:bg-gray-50">ยกเลิก</button>
            <button @click="saveCompRef" class="px-4 py-1.5 text-sm bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]">บันทึก</button>
          </div>
        </div>

        <!-- Add Feedback -->
        <div v-if="modalOpen === 'add_feedback'" class="bg-white rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] w-full max-w-md">
          <div class="flex items-center justify-between px-4 py-3 border-b border-[color:var(--color-rule)]">
            <p class="font-medium text-sm">เพิ่ม Feedback สินค้า</p>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Product ID</label>
              <input v-model.number="modalData.product_id" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Customer ID (ถ้ามี)</label>
              <input v-model.number="modalData.customer_id" type="number" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">ประเภท</label>
                <input v-model="modalData.type" type="text" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" placeholder="general" />
              </div>
              <div>
                <label class="block text-xs text-[color:var(--color-muted)] mb-1">หมวด</label>
                <select v-model="modalData.category" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]">
                  <option value="">— ไม่ระบุ —</option>
                  <option v-for="c in ['รสชาติ','กลิ่น','สี','ความละลาย','บรรจุภัณฑ์','อื่นๆ']" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">Score (1–5)</label>
              <input v-model.number="modalData.score" type="number" min="1" max="5" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
            <div>
              <label class="block text-xs text-[color:var(--color-muted)] mb-1">ความคิดเห็น</label>
              <textarea v-model="modalData.text" rows="2" class="w-full px-3 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)]" />
            </div>
          </div>
          <div class="flex justify-end gap-2 px-4 py-3 border-t border-[color:var(--color-rule)]">
            <button @click="closeModal" class="px-4 py-1.5 text-sm border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] hover:bg-gray-50">ยกเลิก</button>
            <button @click="saveFeedback" class="px-4 py-1.5 text-sm bg-[color:var(--color-brand-900)] text-white rounded-[var(--radius-sm)] hover:bg-[color:var(--color-brand-800)]">บันทึก</button>
          </div>
        </div>
      </div>
    </Teleport>

  </AppLayout>
</template>
