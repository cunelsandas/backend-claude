<script setup>
/**
 * <DataTable> — feature-rich Inertia-paginated table.
 *
 * Props:
 *   rows         (Array)   — current page of records
 *   columns      (Array)   — column definitions, see shape below
 *   pagination   (Object)  — Laravel paginator metadata { current_page, last_page, per_page, total, from, to }
 *   sort         (Object)  — { column, direction: 'asc'|'desc' }
 *   filters      (Object)  — { [colKey]: value }
 *   search       (String)  — global search string
 *   routeUrl     (String)  — base URL Inertia.get() targets when state changes (default: window.location.pathname)
 *   selectable   (Boolean)
 *   bulkActions  (Array)   — [{ key, label, tone?: 'danger'|'accent'|'primary', confirm?: string }]
 *   exportable   (Boolean) — show export menu
 *   exportFormats(Array)   — ['csv','xlsx']
 *   density      (String)  — 'comfortable'|'compact'|'dense'
 *   tableId      (String)  — used to persist user prefs in localStorage
 *   title        (String)
 *   emptyText    (String)
 *
 * Column shape:
 *   { key, label, sortable, filterable, mono, align?: 'left'|'right'|'center',
 *     formatter?: (value, row) => string, headerClass?, cellClass?, width?, hideable?: boolean, hiddenByDefault?: boolean }
 *
 * Emits:
 *   bulk-action  (actionKey, selectedRows)
 *   row-click    (row)
 */

import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from '../UI/Button.vue';
import Badge from '../UI/Badge.vue';
import DataTablePagination from './DataTablePagination.vue';

const props = defineProps({
    rows:        { type: Array, required: true },
    columns:     { type: Array, required: true },
    pagination:  { type: Object, default: () => ({ current_page: 1, last_page: 1, per_page: 25, total: 0, from: 0, to: 0 }) },
    sort:        { type: Object, default: () => ({ column: null, direction: 'asc' }) },
    filters:     { type: Object, default: () => ({}) },
    search:      { type: String, default: '' },
    routeUrl:    { type: String, default: null },
    selectable:  { type: Boolean, default: false },
    bulkActions: { type: Array, default: () => [] },
    exportable:  { type: Boolean, default: true },
    exportFormats:{ type: Array, default: () => ['csv', 'xlsx'] },
    density:     { type: String, default: 'comfortable' },
    tableId:     { type: String, default: 'datatable' },
    title:       { type: String, default: null },
    rowKey:      { type: String, default: 'id' },
    emptyText:   { type: String, default: 'No records found.' },
    striped:     { type: Boolean, default: false },
    activeRowId: { type: [String, Number, null], default: null },
});
const emit = defineEmits(['bulk-action', 'row-click']);

const baseUrl = computed(() => props.routeUrl ?? window.location.pathname);

/* ---------- Local prefs (persisted) ---------- */
const localKey = `dt:${props.tableId}`;
const localState = ref({
    density: props.density,
    mono: false,
    hiddenColumns: props.columns.filter(c => c.hiddenByDefault).map(c => c.key),
});
onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem(localKey) ?? 'null');
        if (saved) localState.value = { ...localState.value, ...saved };
    } catch (_) { /* ignore */ }
});
watch(localState, (v) => localStorage.setItem(localKey, JSON.stringify(v)), { deep: true });

const visibleColumns = computed(() =>
    props.columns.filter(c => !localState.value.hiddenColumns.includes(c.key))
);

/* ---------- Filter row visibility ---------- */
const showFilters = ref(Object.keys(props.filters).length > 0);
const filterModel = ref({ ...props.filters });
watch(() => props.filters, (v) => { filterModel.value = { ...v }; });

/* ---------- Global search debounce ---------- */
const searchModel = ref(props.search);
let searchTimer = null;
watch(searchModel, (v) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => navigate({ search: v || undefined, page: 1 }), 300);
});

/* ---------- Selection ---------- */
const selectedIds = ref(new Set());
const allOnPageSelected = computed(() =>
    props.rows.length > 0 && props.rows.every(r => selectedIds.value.has(r[props.rowKey]))
);
const someOnPageSelected = computed(() =>
    props.rows.some(r => selectedIds.value.has(r[props.rowKey]))
);
function toggleAllOnPage(e) {
    if (e.target.checked) {
        props.rows.forEach(r => selectedIds.value.add(r[props.rowKey]));
    } else {
        props.rows.forEach(r => selectedIds.value.delete(r[props.rowKey]));
    }
    selectedIds.value = new Set(selectedIds.value);
}
function toggleRow(row) {
    const k = row[props.rowKey];
    selectedIds.value.has(k) ? selectedIds.value.delete(k) : selectedIds.value.add(k);
    selectedIds.value = new Set(selectedIds.value);
}
function isSelected(row) {
    return selectedIds.value.has(row[props.rowKey]);
}
function clearSelection() {
    selectedIds.value = new Set();
}
function runBulkAction(action) {
    const selectedRows = props.rows.filter(r => selectedIds.value.has(r[props.rowKey]));
    emit('bulk-action', action.key, selectedRows);
}

/* ---------- Inertia navigation ---------- */
function navigate(patch) {
    const params = {
        search:  searchModel.value || undefined,
        sort:    props.sort.column ?? undefined,
        dir:     props.sort.column ? props.sort.direction : undefined,
        page:    props.pagination.current_page,
        per:     props.pagination.per_page,
        filters: Object.keys(filterModel.value).length ? filterModel.value : undefined,
        ...patch,
    };
    Object.keys(params).forEach(k => params[k] === undefined && delete params[k]);

    router.get(baseUrl.value, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function sortBy(col) {
    if (!col.sortable) return;
    let dir = 'asc';
    if (props.sort.column === col.key) {
        dir = props.sort.direction === 'asc' ? 'desc' : 'asc';
    }
    navigate({ sort: col.key, dir, page: 1 });
}

function applyFilters() {
    const cleaned = {};
    for (const [k, v] of Object.entries(filterModel.value)) {
        if (v !== '' && v !== null && v !== undefined) cleaned[k] = v;
    }
    navigate({ filters: Object.keys(cleaned).length ? cleaned : undefined, page: 1 });
}
function clearFilters() {
    filterModel.value = {};
    navigate({ filters: undefined, page: 1 });
}

function changePage(p)    { navigate({ page: p }); }
function changePerPage(n) { navigate({ per: n, page: 1 }); }

/* ---------- Export ---------- */
function exportAs(format) {
    const params = new URLSearchParams({
        export: format,
        ...(searchModel.value ? { search: searchModel.value } : {}),
        ...(props.sort.column ? { sort: props.sort.column, dir: props.sort.direction } : {}),
    });
    Object.entries(filterModel.value).forEach(([k, v]) => v && params.append(`filters[${k}]`, v));
    if (selectedIds.value.size > 0) {
        Array.from(selectedIds.value).forEach(id => params.append('ids[]', id));
    }
    window.location.href = `${baseUrl.value}?${params.toString()}`;
}

/* ---------- Menu state ---------- */
const openMenu = ref(null); // 'cols' | 'density' | 'export' | null
function toggleMenu(name) { openMenu.value = openMenu.value === name ? null : name; }
function closeMenus() { openMenu.value = null; }

/* ---------- Cell renderer helpers ---------- */
function cellValue(row, col) {
    const raw = row[col.key];
    return col.formatter ? col.formatter(raw, row) : raw;
}
function cellAlign(col) {
    if (col.align) return `text-${col.align}`;
    return col.mono ? 'text-right' : 'text-left';
}
function sortIcon(col) {
    if (props.sort.column !== col.key) return 'idle';
    return props.sort.direction === 'asc' ? 'asc' : 'desc';
}

const densityOptions = [
    { value: 'comfortable', label: 'Comfortable' },
    { value: 'compact',     label: 'Compact' },
    { value: 'dense',       label: 'Dense' },
];
</script>

<template>
    <div
        class="bg-[color:var(--color-card)] border border-[color:var(--color-rule)] rounded-[var(--radius-md)] overflow-hidden shadow-[var(--shadow-paper)]"
        @click="closeMenus"
    >
        <!-- ============ Toolbar ============ -->
        <div class="px-4 py-3 border-b border-[color:var(--color-rule)] flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px] flex items-center gap-3">
                <h3 v-if="title" class="text-sm font-semibold text-[color:var(--color-ink)] mr-2">{{ title }}</h3>

                <!-- Global search -->
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-brand-400)] pointer-events-none" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="7" cy="7" r="5"/><path d="m13 13-2.5-2.5" stroke-linecap="round"/>
                    </svg>
                    <input
                        v-model="searchModel"
                        type="search"
                        placeholder="Search…"
                        class="w-full pl-8 pr-3 h-8 text-xs border border-[color:var(--color-brand-300)] rounded-[var(--radius-sm)] bg-white focus:outline-none focus:border-[color:var(--color-accent-500)] focus:shadow-[0_0_0_3px_var(--color-accent-100)]"
                    />
                </div>

                <Badge v-if="pagination.total != null" tone="neutral" variant="soft" size="sm">
                    <span class="font-mono tabular-nums">{{ pagination.total.toLocaleString() }}</span>&nbsp;rows
                </Badge>
            </div>

            <div class="flex items-center gap-1.5" @click.stop>
                <!-- Filters toggle -->
                <Button
                    v-if="columns.some(c => c.filterable)"
                    variant="ghost"
                    size="sm"
                    :tone="showFilters ? 'accent' : 'neutral'"
                    @click="showFilters = !showFilters"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M2 3h12M4 8h8M6 13h4" stroke-linecap="round"/>
                    </svg>
                    Filters
                    <span v-if="Object.values(filterModel).filter(v => v !== '' && v != null).length" class="text-[10px] font-mono bg-[color:var(--color-accent-200)] text-[color:var(--color-accent-800)] px-1.5 rounded">
                        {{ Object.values(filterModel).filter(v => v !== '' && v != null).length }}
                    </span>
                </Button>

                <!-- Columns menu -->
                <div class="relative">
                    <Button variant="ghost" size="sm" @click="toggleMenu('cols')">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="3" width="4" height="10"/><rect x="7" y="3" width="2" height="10"/><rect x="10" y="3" width="4" height="10"/>
                        </svg>
                        Columns
                    </Button>
                    <div
                        v-if="openMenu === 'cols'"
                        class="absolute right-0 mt-1 w-56 bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] py-1 z-20"
                    >
                        <p class="px-3 py-1.5 text-[10px] uppercase font-semibold tracking-wider text-[color:var(--color-brand-500)] border-b border-[color:var(--color-rule)]">Toggle columns</p>
                        <label
                            v-for="col in columns"
                            :key="col.key"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-[color:var(--color-brand-50)] cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="!localState.hiddenColumns.includes(col.key)"
                                @change="(e) => {
                                    if (e.target.checked) localState.hiddenColumns = localState.hiddenColumns.filter(k => k !== col.key);
                                    else localState.hiddenColumns = [...localState.hiddenColumns, col.key];
                                }"
                                class="accent-[color:var(--color-accent-500)]"
                            />
                            <span>{{ col.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Density menu -->
                <div class="relative">
                    <Button variant="ghost" size="sm" @click="toggleMenu('density')">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M2 4h12M2 8h12M2 12h12" stroke-linecap="round"/>
                        </svg>
                        Density
                    </Button>
                    <div
                        v-if="openMenu === 'density'"
                        class="absolute right-0 mt-1 w-48 bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] py-1 z-20"
                    >
                        <button
                            v-for="opt in densityOptions"
                            :key="opt.value"
                            @click="localState.density = opt.value; closeMenus()"
                            class="w-full text-left px-3 py-1.5 text-sm hover:bg-[color:var(--color-brand-50)] flex items-center justify-between"
                            :class="{ 'text-[color:var(--color-accent-700)] font-medium': localState.density === opt.value }"
                        >
                            {{ opt.label }}
                            <span v-if="localState.density === opt.value">✓</span>
                        </button>
                        <div class="border-t border-[color:var(--color-rule)] mt-1 pt-1">
                            <label class="flex items-center justify-between px-3 py-1.5 text-sm cursor-pointer hover:bg-[color:var(--color-brand-50)]">
                                <span>Mono mode</span>
                                <input v-model="localState.mono" type="checkbox" class="accent-[color:var(--color-accent-500)]"/>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Export menu -->
                <div v-if="exportable" class="relative">
                    <Button variant="ghost" size="sm" @click="toggleMenu('export')">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M8 2v8m-3-3 3 3 3-3M3 13h10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export
                    </Button>
                    <div
                        v-if="openMenu === 'export'"
                        class="absolute right-0 mt-1 w-44 bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] py-1 z-20"
                    >
                        <button
                            v-for="fmt in exportFormats"
                            :key="fmt"
                            @click="exportAs(fmt); closeMenus()"
                            class="w-full text-left px-3 py-1.5 text-sm hover:bg-[color:var(--color-brand-50)] uppercase tracking-wider text-xs"
                        >
                            {{ fmt }}{{ selectedIds.size ? ` (${selectedIds.size} selected)` : '' }}
                        </button>
                    </div>
                </div>

                <slot name="actions" />
            </div>
        </div>

        <!-- ============ Filter row ============ -->
        <transition
            enter-active-class="transition-all duration-200"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-40 opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="max-h-40 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div
                v-if="showFilters"
                class="px-4 py-3 bg-[color:var(--color-brand-50)] border-b border-[color:var(--color-rule)] flex flex-wrap items-end gap-3 overflow-hidden"
            >
                <div
                    v-for="col in columns.filter(c => c.filterable)"
                    :key="col.key"
                    class="min-w-[140px]"
                >
                    <label class="block text-[10px] uppercase tracking-wider font-medium text-[color:var(--color-brand-600)] mb-1">{{ col.label }}</label>
                    <input
                        v-model="filterModel[col.key]"
                        :placeholder="`Filter…`"
                        class="w-full h-7 text-xs px-2 border border-[color:var(--color-brand-300)] rounded-[var(--radius-xs)] bg-white focus:outline-none focus:border-[color:var(--color-accent-500)]"
                    />
                </div>
                <div class="flex gap-2 ml-auto">
                    <Button size="sm" variant="outline" @click="clearFilters">Clear</Button>
                    <Button size="sm" variant="solid" tone="accent" @click="applyFilters">Apply</Button>
                </div>
            </div>
        </transition>

        <!-- ============ Bulk actions bar ============ -->
        <transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition-all duration-150"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="selectable && selectedIds.size > 0"
                class="px-4 py-2 bg-[color:var(--color-accent-100)] border-b border-[color:var(--color-accent-300)] flex items-center justify-between"
            >
                <p class="text-xs font-medium text-[color:var(--color-accent-900)]">
                    <span class="font-mono tabular-nums">{{ selectedIds.size }}</span> selected
                    <button class="ml-2 text-[color:var(--color-accent-700)] hover:underline" @click="clearSelection">Clear</button>
                </p>
                <div class="flex gap-2">
                    <Button
                        v-for="action in bulkActions"
                        :key="action.key"
                        size="sm"
                        :variant="action.tone === 'danger' ? 'danger' : 'solid'"
                        :tone="action.tone === 'danger' ? null : (action.tone ?? 'accent')"
                        @click="runBulkAction(action)"
                    >
                        {{ action.label }}
                    </Button>
                </div>
            </div>
        </transition>

        <!-- ============ Table ============ -->
        <div
            class="overflow-x-auto"
            :data-density="localState.density"
            :data-mono="localState.mono ? '1' : '0'"
        >
            <table class="w-full border-collapse">
                <thead class="bg-[color:var(--color-brand-50)] sticky top-0 z-10">
                    <tr class="border-b border-[color:var(--color-rule)]">
                        <th
                            v-if="selectable"
                            class="w-10 text-center"
                            :style="{ paddingTop: 'var(--row-py)', paddingBottom: 'var(--row-py)' }"
                        >
                            <input
                                type="checkbox"
                                :checked="allOnPageSelected"
                                :indeterminate.prop="!allOnPageSelected && someOnPageSelected"
                                @change="toggleAllOnPage"
                                class="accent-[color:var(--color-accent-500)]"
                            />
                        </th>
                        <th
                            v-for="col in visibleColumns"
                            :key="col.key"
                            class="text-[10px] uppercase tracking-wider font-semibold text-[color:var(--color-brand-700)] px-3 whitespace-nowrap"
                            :class="[cellAlign(col), col.headerClass, col.sortable ? 'cursor-pointer hover:text-[color:var(--color-ink)]' : '']"
                            :style="{ paddingTop: 'var(--row-py)', paddingBottom: 'var(--row-py)', width: col.width }"
                            @click="sortBy(col)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <svg v-if="col.sortable" class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path v-if="sortIcon(col) === 'asc'"  d="M3 7l3-3 3 3" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path v-else-if="sortIcon(col) === 'desc'" d="M3 5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path v-else d="M3 5l3-3 3 3M3 7l3 3 3-3" stroke-linecap="round" stroke-linejoin="round" opacity=".3"/>
                                </svg>
                            </span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(row, i) in rows"
                        :key="row[rowKey] ?? i"
                        class="border-b border-[color:var(--color-rule)] last:border-b-0 hover:bg-[color:var(--color-brand-50)]/60 transition-colors group relative"
                        :class="[
                            striped && i % 2 === 1 ? 'bg-[color:var(--color-brand-50)]/30' : '',
                            (isSelected(row) || activeRowId === row[rowKey]) ? 'bg-[color:var(--color-accent-50)]' : '',
                        ]"
                        @click="emit('row-click', row)"
                    >
                        <!-- Active/selected left bar -->
                        <td
                            v-if="isSelected(row) || activeRowId === row[rowKey]"
                            class="absolute left-0 top-0 bottom-0 w-1 bg-[color:var(--color-accent-500)] pointer-events-none"
                            aria-hidden="true"
                        ></td>

                        <td
                            v-if="selectable"
                            class="w-10 text-center"
                            :style="{ paddingTop: 'var(--row-py)', paddingBottom: 'var(--row-py)' }"
                            @click.stop
                        >
                            <input
                                type="checkbox"
                                :checked="isSelected(row)"
                                @change="toggleRow(row)"
                                class="accent-[color:var(--color-accent-500)]"
                            />
                        </td>

                        <td
                            v-for="col in visibleColumns"
                            :key="col.key"
                            class="px-3 text-[color:var(--color-ink)]"
                            :class="[
                                cellAlign(col),
                                col.cellClass,
                                col.mono ? 'font-mono' : '',
                            ]"
                            :style="{
                                paddingTop: 'var(--row-py)',
                                paddingBottom: 'var(--row-py)',
                                fontSize: 'var(--row-fs)',
                            }"
                        >
                            <slot
                                :name="`cell-${col.key}`"
                                :row="row"
                                :value="row[col.key]"
                                :formatted="cellValue(row, col)"
                            >
                                {{ cellValue(row, col) }}
                            </slot>
                        </td>
                    </tr>

                    <tr v-if="rows.length === 0">
                        <td
                            :colspan="visibleColumns.length + (selectable ? 1 : 0)"
                            class="py-16 text-center text-sm text-[color:var(--color-brand-500)]"
                        >
                            <div class="inline-flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-[color:var(--color-brand-300)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>{{ emptyText }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ============ Pagination ============ -->
        <DataTablePagination
            :pagination="pagination"
            @change-page="changePage"
            @change-per-page="changePerPage"
        />
    </div>
</template>
