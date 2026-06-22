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
    { key: 'id',           label: 'ID',          sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'name',         label: 'Customer',    sortable: true, filterable: true },
    { key: 'phone',        label: 'Phone',       sortable: true, filterable: true, mono: true },
    { key: 'brand_name',   label: 'Brand',       filterable: true, hiddenByDefault: true },
    { key: 'vip',          label: 'VIP',         sortable: true, align: 'center', width: '70px' },
    {
        key: 'award_points',
        label: 'Points',
        sortable: true,
        mono: true,
        align: 'right',
        formatter: (v) => v != null ? Number(v).toLocaleString() : '—',
    },
    {
        key: 'sum_total',
        label: 'Lifetime Total',
        sortable: true,
        mono: true,
        align: 'right',
        formatter: (v) => v != null ? Number(v).toLocaleString('th-TH', { minimumFractionDigits: 2 }) : '—',
    },
    { key: 'last_buy',    label: 'Last Purchase', sortable: true, mono: true },
    { key: 'fade_delete', label: 'Status',        sortable: true, align: 'center', width: '90px' },
];
</script>

<template>
    <AppLayout>
        <template #header>Customers</template>

        <DataTable
            table-id="master-customers"
            title="Customers"
            route-url="/master-data/customers"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        >
            <template #cell-vip="{ row }">
                <Badge v-if="row.vvip" tone="accent" size="sm">VVIP</Badge>
                <Badge v-else-if="row.vip" tone="warning" size="sm">VIP</Badge>
                <span v-else class="text-[color:var(--color-brand-300)]">—</span>
            </template>
            <template #cell-fade_delete="{ value }">
                <Badge :tone="value === 1 ? 'danger' : 'success'" size="sm">
                    {{ value === 1 ? 'Closed' : 'Active' }}
                </Badge>
            </template>
        </DataTable>
    </AppLayout>
</template>
