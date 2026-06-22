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
    { key: 'id',               label: 'ID',         sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'code',             label: 'Code',       sortable: true, filterable: true, mono: true, width: '110px' },
    { key: 'name',             label: 'Unit',       sortable: true, filterable: true },
    { key: 'base_unit_name',   label: 'Base Unit',  filterable: true },
    { key: 'operator',         label: 'Operator',   mono: true, align: 'center', width: '90px' },
    { key: 'operation_value',  label: 'Factor',     mono: true, align: 'right', width: '90px' },
];
</script>

<template>
    <AppLayout>
        <template #header>Units</template>

        <DataTable
            table-id="master-units"
            title="Units"
            route-url="/master-data/units"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        >
            <template #cell-base_unit_name="{ value }">
                <Badge v-if="!value" tone="info" size="sm">Base</Badge>
                <span v-else class="text-[color:var(--color-ink-soft)]">{{ value }}</span>
            </template>
            <template #cell-operation_value="{ value }">
                <span v-if="value" class="font-mono text-sm">{{ value }}</span>
                <span v-else class="text-[color:var(--color-brand-300)]">—</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
