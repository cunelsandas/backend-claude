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
    { key: 'id',          label: 'ID',       sortable: true, mono: true, align: 'right', width: '70px' },
    { key: 'code',        label: 'Code',     sortable: true, filterable: true, mono: true, width: '110px' },
    { key: 'name',        label: 'Category', sortable: true, filterable: true },
    { key: 'parent_name', label: 'Parent',   filterable: true },
    { key: 'parent_id',   label: 'Level',    align: 'center', width: '90px' },
];
</script>

<template>
    <AppLayout>
        <template #header>Categories</template>

        <DataTable
            table-id="master-categories"
            title="Categories"
            route-url="/master-data/categories"
            :rows="rows"
            :columns="columns"
            :pagination="pagination"
            :sort="sort"
            :search="search"
            :filters="filters"
            :exportable="true"
            density="compact"
        >
            <template #cell-parent_id="{ value }">
                <Badge :tone="(!value || value === 0) ? 'info' : 'default'" size="sm">
                    {{ (!value || value === 0) ? 'Root' : 'Sub' }}
                </Badge>
            </template>
            <template #cell-parent_name="{ value }">
                <span v-if="value" class="text-[color:var(--color-ink-soft)]">{{ value }}</span>
                <span v-else class="text-[color:var(--color-brand-300)]">—</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
