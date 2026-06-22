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
    { key: 'id',                label: 'ID',         sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'sma_user',          label: 'SMA ID',     sortable: true, mono: true, align: 'right', width: '90px' },
    { key: 'employee_username', label: 'Username',   sortable: true, filterable: true, mono: true },
    { key: 'name_th',           label: 'Thai Name',  filterable: true },
    { key: 'name_en',           label: 'Eng. Name',  filterable: true, hiddenByDefault: true },
    { key: 'employee_position', label: 'Position',   sortable: true, mono: true, align: 'right', width: '90px' },
    { key: 'employee_team',     label: 'Team',       mono: true, align: 'right', width: '80px', hiddenByDefault: true },
    { key: 'employee_type',     label: 'Type',       width: '90px', hiddenByDefault: true },
    { key: 'status',            label: 'Status',     sortable: true, align: 'center', width: '90px' },
];
</script>

<template>
    <AppLayout>
        <template #header>Employees</template>

        <DataTable
            table-id="master-employees"
            title="Employees"
            route-url="/master-data/employees"
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
        </DataTable>
    </AppLayout>
</template>
