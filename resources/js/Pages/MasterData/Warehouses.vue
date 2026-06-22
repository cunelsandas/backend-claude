<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable/DataTable.vue';

const props = defineProps({
    rows:       { type: Array,  required: true },
    pagination: { type: Object, required: true },
    sort:       { type: Object, required: true },
    search:     { type: String, default: '' },
    filters:    { type: Object, default: () => ({}) },
});

const columns = [
    { key: 'id',      label: 'ID',      sortable: true,  mono: true,  align: 'right', width: '70px' },
    { key: 'code',    label: 'Code',    sortable: true,  filterable: true, mono: true },
    { key: 'name',    label: 'Branch / Warehouse', sortable: true, filterable: true },
    { key: 'name_second', label: 'Alt. Name', hiddenByDefault: true },
    { key: 'address', label: 'Address', filterable: true },
    { key: 'phone',   label: 'Phone',   sortable: true,  mono: true },
    { key: 'email',   label: 'Email',   hiddenByDefault: true },
];
</script>

<template>
    <AppLayout>
        <template #header>Branches &amp; Warehouses</template>

        <DataTable
            table-id="master-warehouses"
            title="Branches & Warehouses"
            route-url="/master-data/warehouses"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        />
    </AppLayout>
</template>
