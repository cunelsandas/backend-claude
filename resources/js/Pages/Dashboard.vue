<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>Dashboard</template>

        <div class="space-y-8">
            <!-- Hero -->
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-[color:var(--color-accent-700)] font-medium mb-1">
                    Welcome back
                </p>
                <h1 class="font-display text-4xl font-medium text-[color:var(--color-ink)] tracking-tight">
                    {{ auth.user?.display_name ?? 'User' }}
                </h1>
                <p class="mt-2 text-sm text-[color:var(--color-brand-600)]">
                    SMA <span class="font-mono">{{ auth.user?.sma_id ?? '—' }}</span> · {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>
            </div>

            <!-- Stat strip -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card padding="p-5" flat>
                    <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] font-medium">Roles</p>
                    <p class="font-display text-3xl mt-2">{{ auth.roles?.length ?? 0 }}</p>
                    <div class="mt-3 flex flex-wrap gap-1">
                        <Badge v-for="r in auth.roles" :key="r" tone="accent" variant="soft" size="xs">{{ r }}</Badge>
                    </div>
                </Card>

                <Card padding="p-5" flat>
                    <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] font-medium">Permissions</p>
                    <p class="font-display text-3xl mt-2 font-mono tabular-nums">{{ auth.permissions?.length ?? 0 }}</p>
                    <p class="mt-1 text-xs text-[color:var(--color-brand-500)]">routes accessible</p>
                </Card>

                <Card padding="p-5" flat>
                    <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] font-medium">Sprint progress</p>
                    <p class="font-display text-3xl mt-2 font-mono tabular-nums">3 <span class="text-[color:var(--color-brand-400)]">/ 13</span></p>
                    <p class="mt-1 text-xs text-[color:var(--color-brand-500)]">UI foundation complete</p>
                </Card>

                <Card padding="p-5" flat>
                    <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] font-medium">Status</p>
                    <Badge tone="success" variant="dot" size="md" class="mt-2">All systems normal</Badge>
                </Card>
            </div>

            <Card title="Foundation status" subtitle="Components and infrastructure ready for module migration.">
                <ul class="space-y-2.5 text-sm text-[color:var(--color-brand-800)]">
                    <li class="flex items-start gap-2">
                        <span class="text-[color:var(--color-success)] mt-0.5">✓</span>
                        Login + rehash-on-first-login (Sprint 2)
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-[color:var(--color-success)] mt-0.5">✓</span>
                        Spatie roles &amp; permissions (Sprint 2)
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-[color:var(--color-success)] mt-0.5">✓</span>
                        Shared UI Kit — Buttons, Forms, DataTable, Modals, Drawers, Toasts, DatePickers (Sprint 3)
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-[color:var(--color-accent-600)] mt-0.5">→</span>
                        Sprint 4 — Master data modules (Branches, Products, Customers, Employees)
                    </li>
                </ul>

                <template #footer>
                    <div class="flex items-center justify-between w-full">
                        <span class="text-xs text-[color:var(--color-brand-500)]">Explore the UI Kit to see every component.</span>
                        <Button variant="outline" tone="accent" size="sm" href="/ui-kit">Open UI Kit →</Button>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
