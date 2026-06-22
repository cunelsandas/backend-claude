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
    { key: 'id',            label: 'ID',       sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'code',          label: 'Code',     sortable: true, filterable: true, mono: true, width: '120px' },
    { key: 'name',          label: 'Product',  sortable: true, filterable: true },
    { key: 'second_name',   label: 'Alt. Name', hiddenByDefault: true },
    { key: 'brand_name',    label: 'Brand',    filterable: true },
    { key: 'category_name', label: 'Category', filterable: true },
    {
        key: 'price',
        label: 'Price',
        sortable: true,
        mono: true,
        align: 'right',
        formatter: (v) => v != null ? Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2 }) : '—',
    },
    {
        key: 'cost',
        label: 'Cost',
        sortable: true,
        mono: true,
        align: 'right',
        hiddenByDefault: true,
        formatter: (v) => v != null ? Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2 }) : '—',
    },
    { key: 'status', label: 'Status', sortable: true, width: '90px', align: 'center' },
    { key: 'tester', label: 'Tester', hiddenByDefault: true, width: '80px', align: 'center' },
];
</script>

<template>
    <AppLayout>
        <template #header>Products</template>

        <DataTable
            table-id="master-products"
            title="Products"
            route-url="/master-data/products"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        >
            <template #cell-status="{ value }">
                <Badge :tone="value === 1 ? 'success' : 'default'" size="sm">
                    {{ value === 1 ? 'Active' : 'Inactive' }}
                </Badge>
            </template>
            <template #cell-tester="{ value }">
                <Badge v-if="value === 1" tone="warning" size="sm">Tester</Badge>
                <span v-else class="text-[color:var(--color-brand-300)]">—</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
