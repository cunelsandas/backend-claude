<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable/DataTable.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    rows:       { type: Array,  required: true },
    pagination: { type: Object, required: true },
    sort:       { type: Object, required: true },
    search:     { type: String, default: '' },
    filters:    { type: Object, default: () => ({}) },
});

const columns = [
    { key: 'id',     label: 'ID',     sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'code',   label: 'Code',   sortable: true, filterable: true, mono: true, width: '110px' },
    { key: 'name',   label: 'Brand',  sortable: true, filterable: true },
    { key: 'color',  label: 'Color',  align: 'center', width: '90px' },
    { key: 'status', label: 'Status', sortable: true,  align: 'center', width: '90px' },
];
</script>

<template>
    <AppLayout>
        <template #header>Brands</template>

        <DataTable
            table-id="master-brands"
            title="Brands"
            route-url="/master-data/brands"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        >
            <template #cell-color="{ value }">
                <span v-if="value" class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block w-3.5 h-3.5 rounded-sm border border-black/10"
                        :style="{ background: value }"
                    />
                    <span class="font-mono text-xs text-[color:var(--color-ink-soft)]">{{ value }}</span>
                </span>
                <span v-else class="text-[color:var(--color-brand-300)]">—</span>
            </template>
            <template #cell-status="{ value }">
                <Badge :tone="value === 1 ? 'success' : 'default'" size="sm">
                    {{ value === 1 ? 'Active' : 'Inactive' }}
                </Badge>
            </template>
        </DataTable>
    </AppLayout>
</template>
