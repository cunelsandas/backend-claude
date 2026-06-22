<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import Button       from '@/Components/UI/Button.vue';
import Badge        from '@/Components/UI/Badge.vue';
import Card         from '@/Components/UI/Card.vue';
import Tabs         from '@/Components/UI/Tabs.vue';
import Kbd          from '@/Components/UI/Kbd.vue';

import FormInput    from '@/Components/Form/FormInput.vue';
import FormSelect   from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormCheckbox from '@/Components/Form/FormCheckbox.vue';
import FormRadio    from '@/Components/Form/FormRadio.vue';
import FormSwitch   from '@/Components/Form/FormSwitch.vue';

import Modal        from '@/Components/Overlay/Modal.vue';
import Drawer       from '@/Components/Overlay/Drawer.vue';
import ConfirmDialog from '@/Components/Overlay/ConfirmDialog.vue';

import DataTable    from '@/Components/DataTable/DataTable.vue';
import DatePicker   from '@/Components/DatePicker.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';

import { useToast } from '@/Composables/useToast';

const { toast } = useToast();

const tab = ref('typography');
const tabs = [
    { key: 'typography', label: 'Typography' },
    { key: 'primitives', label: 'Primitives', badge: 5 },
    { key: 'forms',      label: 'Forms',      badge: 7 },
    { key: 'overlays',   label: 'Overlays' },
    { key: 'data',       label: 'DataTable' },
    { key: 'dates',      label: 'Date pickers' },
];

// Form demo (Inertia useForm so validation hooks work for free)
const form = useForm({
    name: '',
    email: '',
    plan: 'starter',
    bio: '',
    accept: false,
    contact: 'email',
    notify: true,
});
function submitDemo() {
    // No route exists yet for /ui-kit/demo-submit, this just demonstrates the form mechanics
    form.errors = {
        name:  !form.name  ? 'Name is required.' : null,
        email: !form.email ? 'Email is required.' : null,
    };
    if (!form.name || !form.email) {
        toast.error('Please correct the highlighted fields.');
    } else {
        toast.success(`Saved! Welcome, ${form.name}.`);
        form.reset();
    }
}

// Modal / Drawer state
const showModal = ref(false);
const showDrawer = ref(false);
const showConfirm = ref(false);
const confirmLoading = ref(false);
function doConfirm() {
    confirmLoading.value = true;
    setTimeout(() => {
        confirmLoading.value = false;
        showConfirm.value = false;
        toast.success('Record deleted.');
    }, 700);
}

// Sample DataTable data
const sampleRows = ref([
    { id: 'INV-00231', customer: 'Bluemocha Bangkok',  status: 'paid',    total: 12450.00, date: '2026-06-12', sales: 'P. Thongkuea' },
    { id: 'INV-00232', customer: 'Café Aurora',        status: 'pending', total:  3275.50, date: '2026-06-12', sales: 'N. Saetang' },
    { id: 'INV-00233', customer: 'Mocha Lab',          status: 'paid',    total: 28940.75, date: '2026-06-13', sales: 'P. Thongkuea' },
    { id: 'INV-00234', customer: 'Tea Society HQ',     status: 'overdue', total:  8120.00, date: '2026-06-13', sales: 'V. Jirat' },
    { id: 'INV-00235', customer: 'Roast & Co.',        status: 'paid',    total: 15600.25, date: '2026-06-14', sales: 'P. Thongkuea' },
    { id: 'INV-00236', customer: 'Hua Hin Outlet',     status: 'cancelled',total:  4400.00, date: '2026-06-14', sales: 'V. Jirat' },
    { id: 'INV-00237', customer: 'Bluemocha Chiang Mai',status: 'pending',total: 19800.00, date: '2026-06-15', sales: 'N. Saetang' },
    { id: 'INV-00238', customer: 'The Velvet Cup',     status: 'paid',    total: 33100.50, date: '2026-06-15', sales: 'P. Thongkuea' },
]);
const sampleColumns = [
    { key: 'id',       label: 'Invoice',   sortable: true, mono: true, width: '120px' },
    { key: 'customer', label: 'Customer',  sortable: true, filterable: true },
    { key: 'status',   label: 'Status',    sortable: true, filterable: true, align: 'center', width: '120px' },
    { key: 'total',    label: 'Total THB', sortable: true, mono: true, align: 'right', width: '140px',
        formatter: (v) => v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    { key: 'date',     label: 'Date',      sortable: true, mono: true, align: 'right', width: '110px' },
    { key: 'sales',    label: 'Sales rep', sortable: true, hideable: true },
];
const fakePagination = {
    current_page: 1, last_page: 14, per_page: 25, total: 347, from: 1, to: 8,
};

// Date picker state
const singleDate = ref(null);
const dateRange = ref([]);

// Demo bulk action handler
function handleBulk(actionKey, rows) {
    toast.warn(`Bulk action "${actionKey}" on ${rows.length} row(s)`, { duration: 3500 });
}

// Toast demos
function fireToast(tone) {
    const samples = {
        success: 'Settings saved successfully.',
        danger:  'Failed to save — please retry.',
        warning: 'You have unsaved changes.',
        info:    'A new release is available.',
        accent:  'Sprint 3 complete. 🎉',
    };
    if (tone === 'accent') {
        toast.show({ tone: 'accent', message: samples.accent, action: { label: 'View notes', onClick: () => alert('Sprint 3 notes…') } });
    } else if (tone === 'danger') {
        toast.error(samples.danger);
    } else if (tone === 'warning') {
        toast.warn(samples.warning);
    } else if (tone === 'info') {
        toast.info(samples.info);
    } else {
        toast.success(samples.success);
    }
}

const statusBadge = (s) => {
    const map = {
        paid:      { tone: 'success', variant: 'dot' },
        pending:   { tone: 'warning', variant: 'dot' },
        overdue:   { tone: 'danger',  variant: 'dot' },
        cancelled: { tone: 'neutral', variant: 'dot' },
    };
    return map[s] ?? { tone: 'neutral', variant: 'soft' };
};
</script>

<template>
    <Head title="UI Kit" />

    <AppLayout>
        <template #header>UI Kit · Bluemocha Console</template>

        <div class="space-y-8">
            <!-- Hero -->
            <Card padding="p-8" flat>
                <p class="text-[11px] uppercase tracking-[0.2em] text-[color:var(--color-accent-700)] font-medium mb-2">Sprint 3 · Foundation</p>
                <h1 class="font-display text-5xl font-medium text-[color:var(--color-ink)] leading-[1.05] tracking-tight">
                    Bluemocha <span class="italic">Console</span>
                </h1>
                <p class="mt-4 text-[color:var(--color-brand-600)] max-w-2xl leading-relaxed">
                    A shared component library for the ERP. Editorial serifs meet industrial monospace.
                    Warm cream surfaces meet slate chrome. Every primitive is here — feature-rich, AJAX-validated,
                    keyboard-friendly.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <Badge tone="accent" variant="soft" size="sm">Fraunces · Display</Badge>
                    <Badge tone="brand"  variant="soft" size="sm">DM Sans · Body</Badge>
                    <Badge tone="neutral" variant="outline" size="sm">JetBrains Mono · Data</Badge>
                </div>
            </Card>

            <Tabs v-model="tab" :tabs="tabs" />

            <!-- ============================== TYPOGRAPHY ============================== -->
            <div v-show="tab === 'typography'" class="space-y-6">
                <Card title="Display · Fraunces" subtitle="Used only in page H1 and dramatic numbers — gives each screen a magazine moment.">
                    <h1 class="font-display text-6xl font-medium tracking-tight leading-none">A serif with soul.</h1>
                    <h2 class="font-display text-4xl font-normal italic mt-2 text-[color:var(--color-accent-700)]">Editorial × Industrial.</h2>
                    <p class="font-display text-2xl font-light mt-4 text-[color:var(--color-brand-700)]">Variable axis: weight 100–900, soft 0–100, optical sizing on.</p>
                </Card>

                <Card title="Body · DM Sans" subtitle="Default sans for every label, paragraph, control.">
                    <p class="text-base leading-relaxed text-[color:var(--color-ink)]">
                        The quick brown fox jumps over the lazy dog. ปลาทูใต้สะพานทอดยาว. 1234567890.
                    </p>
                    <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                        <div><span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] block">Regular 400</span><span class="font-normal">Sample text</span></div>
                        <div><span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] block">Medium 500</span><span class="font-medium">Sample text</span></div>
                        <div><span class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] block">Semibold 600</span><span class="font-semibold">Sample text</span></div>
                    </div>
                </Card>

                <Card title="Data · JetBrains Mono" subtitle="For numbers, IDs, code, and tables in mono mode.">
                    <div class="font-mono text-sm grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[color:var(--color-brand-500)] text-[10px] uppercase tracking-wider mb-1">Tabular numerals</p>
                            <p>1,243,500.00 THB</p>
                            <p>0,001,234.56 THB</p>
                        </div>
                        <div>
                            <p class="text-[color:var(--color-brand-500)] text-[10px] uppercase tracking-wider mb-1">Identifiers</p>
                            <p>INV-00231-A</p>
                            <p>SKU-08772-XL</p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- ============================== PRIMITIVES ============================== -->
            <div v-show="tab === 'primitives'" class="space-y-6">
                <Card title="Buttons" subtitle="5 variants × 3 tones × 4 sizes.">
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-2 items-center">
                            <Button variant="solid" tone="primary">Primary solid</Button>
                            <Button variant="solid" tone="accent">Accent solid</Button>
                            <Button variant="solid" tone="neutral">Neutral solid</Button>
                            <Button variant="outline">Outline</Button>
                            <Button variant="outline" tone="accent">Outline accent</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="link">Link →</Button>
                            <Button variant="danger">Delete</Button>
                            <Button loading>Saving…</Button>
                        </div>
                        <div class="flex flex-wrap gap-2 items-center">
                            <Button size="xs">XS</Button>
                            <Button size="sm">Small</Button>
                            <Button size="md">Medium</Button>
                            <Button size="lg">Large</Button>
                            <Button icon size="md">★</Button>
                        </div>
                    </div>
                </Card>

                <Card title="Badges">
                    <div class="flex flex-wrap gap-2">
                        <Badge tone="neutral">Neutral</Badge>
                        <Badge tone="brand">Brand</Badge>
                        <Badge tone="accent">Accent</Badge>
                        <Badge tone="success">Paid</Badge>
                        <Badge tone="warning">Pending</Badge>
                        <Badge tone="danger">Overdue</Badge>
                        <Badge tone="info">Info</Badge>
                        <Badge tone="success" variant="dot">Active</Badge>
                        <Badge tone="danger"  variant="dot">Offline</Badge>
                        <Badge tone="accent"  variant="outline">Outline</Badge>
                        <Badge tone="brand"   variant="solid">Solid</Badge>
                    </div>
                </Card>

                <Card title="Keyboard shortcuts">
                    <p class="text-sm text-[color:var(--color-brand-700)]">Press <Kbd :keys="['⌘', 'K']" /> to open search · <Kbd :keys="'Esc'" /> to close · <Kbd :keys="['⇧','/']" /> for help</p>
                </Card>

                <Card title="Tabs">
                    <p class="text-xs uppercase tracking-wider text-[color:var(--color-brand-500)] mb-2">Underline</p>
                    <Tabs :model-value="'two'" :tabs="[
                        { key: 'one', label: 'Overview' },
                        { key: 'two', label: 'Activity', badge: 12 },
                        { key: 'three', label: 'Settings' },
                        { key: 'four', label: 'Disabled', disabled: true },
                    ]" />
                    <p class="text-xs uppercase tracking-wider text-[color:var(--color-brand-500)] mt-6 mb-2">Pill</p>
                    <Tabs variant="pill" :model-value="'one'" :tabs="[
                        { key: 'one', label: 'Day' },
                        { key: 'two', label: 'Week' },
                        { key: 'three', label: 'Month' },
                    ]" />
                </Card>
            </div>

            <!-- ============================== FORMS ============================== -->
            <div v-show="tab === 'forms'" class="space-y-6">
                <Card title="Form primitives" subtitle="All validation via Inertia useForm — errors render inline.">
                    <form @submit.prevent="submitDemo" class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-3xl">
                        <FormInput v-model="form.name"  label="Full name" required :error="form.errors.name" placeholder="Phasin Thongkuea" />
                        <FormInput v-model="form.email" label="Email" type="email" required :error="form.errors.email" placeholder="you@bluemocha.co" trailing-icon>
                            <template #trailing>
                                <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2" y="3" width="12" height="10" rx="1"/><path d="m2 5 6 4 6-4" stroke-linecap="round"/>
                                </svg>
                            </template>
                        </FormInput>

                        <FormSelect
                            v-model="form.plan"
                            label="Plan"
                            :options="[
                                { value: 'starter', label: 'Starter — 5 users' },
                                { value: 'growth',  label: 'Growth — 25 users' },
                                { value: 'scale',   label: 'Scale — unlimited' },
                            ]"
                        />
                        <FormInput v-model="form.invoice" label="Invoice ID" optional mono placeholder="INV-00231" hint="Tabular monospace input — for codes & IDs." />

                        <div class="md:col-span-2">
                            <FormTextarea
                                v-model="form.bio"
                                label="Notes"
                                hint="Markdown allowed."
                                :rows="4"
                                show-count
                                :maxlength="280"
                            />
                        </div>

                        <div>
                            <p class="text-xs uppercase font-medium tracking-wider text-[color:var(--color-brand-800)] mb-2">Preferred contact</p>
                            <div class="space-y-2">
                                <FormRadio v-model="form.contact" value="email" label="Email" name="contact" />
                                <FormRadio v-model="form.contact" value="phone" label="Phone" name="contact" />
                                <FormRadio v-model="form.contact" value="line"  label="LINE"  name="contact" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <FormSwitch  v-model="form.notify"  label="Send weekly digest"  hint="Every Monday, 9am ICT." />
                            <FormCheckbox v-model="form.accept" label="I accept the terms" />
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-2 pt-2 border-t border-[color:var(--color-rule)]">
                            <Button variant="ghost" type="button" @click="form.reset()">Reset</Button>
                            <Button variant="solid" tone="accent" type="submit" :loading="form.processing">Save</Button>
                        </div>
                    </form>
                </Card>
            </div>

            <!-- ============================== OVERLAYS ============================== -->
            <div v-show="tab === 'overlays'" class="space-y-6">
                <Card title="Overlays" subtitle="Modals, drawers, and confirm dialogs — all keyboard-friendly (Esc to close, click outside to dismiss).">
                    <div class="flex flex-wrap gap-2">
                        <Button @click="showModal = true">Open modal</Button>
                        <Button variant="outline" @click="showDrawer = true">Open drawer</Button>
                        <Button variant="danger" @click="showConfirm = true">Delete record (confirm)</Button>
                    </div>
                </Card>

                <Card title="Toasts" subtitle="Auto-dismiss in 4.5s, action-capable, and auto-piped from Laravel session flash.">
                    <div class="flex flex-wrap gap-2">
                        <Button variant="solid" tone="neutral" size="sm" @click="fireToast('success')">Success</Button>
                        <Button variant="solid" tone="neutral" size="sm" @click="fireToast('warning')">Warning</Button>
                        <Button variant="solid" tone="neutral" size="sm" @click="fireToast('danger')">Error</Button>
                        <Button variant="solid" tone="neutral" size="sm" @click="fireToast('info')">Info</Button>
                        <Button variant="solid" tone="accent"  size="sm" @click="fireToast('accent')">With action</Button>
                    </div>
                </Card>
            </div>

            <!-- ============================== DATATABLE ============================== -->
            <div v-show="tab === 'data'" class="space-y-6">
                <Card flat padding="p-0">
                    <DataTable
                        title="Invoices"
                        table-id="demo-invoices"
                        :rows="sampleRows"
                        :columns="sampleColumns"
                        :pagination="fakePagination"
                        :sort="{ column: 'date', direction: 'desc' }"
                        selectable
                        :bulk-actions="[
                            { key: 'mark-paid', label: 'Mark paid', tone: 'accent' },
                            { key: 'export',    label: 'Export selection' },
                            { key: 'delete',    label: 'Delete', tone: 'danger' },
                        ]"
                        @bulk-action="handleBulk"
                    >
                        <template #cell-status="{ value }">
                            <Badge v-bind="statusBadge(value)" size="sm">{{ value }}</Badge>
                        </template>
                    </DataTable>
                </Card>

                <div class="text-xs text-[color:var(--color-brand-500)] leading-relaxed max-w-2xl">
                    <p class="font-semibold text-[color:var(--color-brand-700)] mb-1 uppercase tracking-wider">Features wired</p>
                    <p>Sticky header · Sortable headers · Per-column filters (toggle in toolbar) · Global search · Column show/hide · Density toggle (Comfortable / Compact / Dense) · <strong>Mono mode</strong> (try it — toggles entire table to JetBrains Mono) · Multi-row select with bulk-action bar · CSV/Excel export · Server-driven pagination via Inertia · localStorage-persisted user prefs · Custom cell slots (see Status column) · Empty state · Tabular numerals · Active-row left accent bar.</p>
                </div>
            </div>

            <!-- ============================== DATE PICKERS ============================== -->
            <div v-show="tab === 'dates'" class="space-y-6">
                <Card title="Date pickers" subtitle="Powered by flatpickr — themed to match Bluemocha Console.">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl">
                        <DatePicker v-model="singleDate" label="Single date" hint="Click the field — picker opens." />
                        <DateRangePicker v-model="dateRange" label="Date range" hint="Select start, then end." />
                        <DatePicker v-model="singleDate" label="With time" enable-time format="Y-m-d H:i" alt-format="M j, Y · H:i" />
                        <DatePicker v-model="singleDate" label="Disabled" disabled placeholder="Cannot select" />
                    </div>
                </Card>
            </div>
        </div>

        <!-- Modals / drawers / confirms (mounted at root, controlled by state) -->
        <Modal :show="showModal" title="Edit invoice" subtitle="INV-00231 · Bluemocha Bangkok" size="lg" @close="showModal = false">
            <div class="space-y-4">
                <FormInput v-model="form.email" label="Customer email" />
                <FormSelect v-model="form.plan" label="Status" :options="['paid', 'pending', 'overdue', 'cancelled']" />
                <FormTextarea v-model="form.bio" label="Internal note" :rows="3" />
            </div>
            <template #footer>
                <Button variant="ghost" @click="showModal = false">Cancel</Button>
                <Button variant="solid" tone="accent" @click="showModal = false; toast.success('Invoice updated.')">Save changes</Button>
            </template>
        </Modal>

        <Drawer :show="showDrawer" title="Filter advanced" subtitle="Refine the invoice list" @close="showDrawer = false">
            <div class="space-y-5">
                <DatePicker v-model="singleDate" label="Issued on or after" />
                <DateRangePicker v-model="dateRange" label="Due date range" />
                <FormSelect v-model="form.plan" label="Status" :options="['paid', 'pending', 'overdue']" placeholder="Any status" />
                <FormCheckbox v-model="form.accept" label="Show only flagged" />
            </div>
            <template #footer>
                <Button variant="ghost" @click="showDrawer = false">Cancel</Button>
                <Button variant="solid" tone="accent" @click="showDrawer = false; toast.info('Filters applied.')">Apply</Button>
            </template>
        </Drawer>

        <ConfirmDialog
            :show="showConfirm"
            title="Delete this invoice?"
            message="INV-00231 will be removed from the ledger. This action cannot be undone."
            confirm-text="Yes, delete"
            tone="danger"
            :loading="confirmLoading"
            @close="!confirmLoading && (showConfirm = false)"
            @confirm="doConfirm"
        />
    </AppLayout>
</template>
