<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ToastContainer from '@/Components/Toast/ToastContainer.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const permissions = computed(() => page.props.auth?.permissions ?? []);

const can = (perm) =>
    permissions.value.includes(perm) || permissions.value.includes('*') || user.value?.is_root;

const nav = [
    { type: 'link', label: 'Dashboard',     href: '/dashboard', icon: 'M3 12 12 3l9 9M5 10v10a1 1 0 0 0 1 1h4v-7h4v7h4a1 1 0 0 0 1-1V10', perm: null },
    { type: 'link', label: 'Dashboard II',  href: '/dashboard-two', icon: 'M3 3v18h18M7 14l3-3 4 4 5-6', perm: null },
    { type: 'link', label: 'Dashboard III', href: '#',          icon: 'M21 21H4.6A1.6 1.6 0 0 1 3 19.4V3M7 14l3 3 4-7 4 3', perm: null },
    { type: 'link', label: 'Sale',          href: '#',          icon: 'M3 9h18l-1.5 11.4a1 1 0 0 1-1 .6H5.5a1 1 0 0 1-1-.6L3 9Zm3 0V6a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v3', perm: null },
    {
        type: 'group',
        label: 'Master Data',
        icon: 'M4 6h16M4 10h16M4 14h16M4 18h16',
        perm: null,
        children: [
            { label: 'Branches',   href: '/master-data/warehouses' },
            { label: 'Products',   href: '/master-data/products' },
            { label: 'Customers',  href: '/master-data/customers' },
            { label: 'Employees',  href: '/master-data/employees' },
            { label: 'Categories', href: '/master-data/categories' },
            { label: 'Brands',     href: '/master-data/brands' },
            { label: 'Units',      href: '/master-data/units' },
        ],
    },
    { type: 'link', label: 'UI Kit', href: '/ui-kit', icon: 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z', perm: null },
];

const visibleNav = computed(() => nav.filter((i) => i.perm === null || can(i.perm)));

const currentPath = computed(() => page.url.split('?')[0]);
const isActive = (href) => href !== '#' && (currentPath.value === href || currentPath.value.startsWith(href + '/'));
const isGroupActive = (group) => group.children?.some((c) => isActive(c.href));

// Track which groups are open; auto-open if a child is active
const openGroups = ref(new Set());
onMounted(() => {
    nav.forEach((item) => {
        if (item.type === 'group' && isGroupActive(item)) {
            openGroups.value.add(item.label);
        }
    });
});
const toggleGroup = (label) => {
    if (openGroups.value.has(label)) {
        openGroups.value.delete(label);
    } else {
        openGroups.value.add(label);
    }
};

const userMenu = ref(false);
const logout = () => router.post(route('logout'));

const dropdownRef = ref(null);
const onDocClick = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) userMenu.value = false;
};
onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <div class="min-h-screen flex bg-[color:var(--color-surface)]">
        <!-- Sidebar -->
        <aside class="w-60 bg-[color:var(--color-brand-900)] text-[color:var(--color-brand-100)] flex flex-col shrink-0">
            <div class="h-16 flex items-center px-5 border-b border-[color:var(--color-brand-800)]">
                <span class="font-display text-xl font-medium tracking-tight text-white">Bluemocha</span>
                <span class="ml-2 text-[10px] uppercase tracking-[0.18em] text-[color:var(--color-accent-300)] font-semibold mt-2">ERP</span>
            </div>

            <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">
                <template v-for="item in visibleNav" :key="item.label">

                    <!-- Regular link -->
                    <Link
                        v-if="item.type === 'link'"
                        :href="item.href"
                        class="group flex items-center gap-3 px-3 py-2 rounded-[var(--radius-sm)] text-[13px] font-medium transition-colors relative"
                        :class="isActive(item.href)
                            ? 'bg-[color:var(--color-brand-800)] text-white'
                            : 'text-[color:var(--color-brand-300)] hover:bg-[color:var(--color-brand-800)]/60 hover:text-white'"
                    >
                        <span
                            v-if="isActive(item.href)"
                            class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-[color:var(--color-accent-400)] rounded-r"
                        />
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path :d="item.icon"/>
                        </svg>
                        <span>{{ item.label }}</span>
                    </Link>

                    <!-- Collapsible group -->
                    <div v-else-if="item.type === 'group'">
                        <button
                            @click="toggleGroup(item.label)"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-[var(--radius-sm)] text-[13px] font-medium transition-colors relative"
                            :class="isGroupActive(item)
                                ? 'text-white'
                                : 'text-[color:var(--color-brand-300)] hover:bg-[color:var(--color-brand-800)]/60 hover:text-white'"
                        >
                            <span
                                v-if="isGroupActive(item)"
                                class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-[color:var(--color-accent-400)] rounded-r"
                            />
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path :d="item.icon"/>
                            </svg>
                            <span class="flex-1 text-left">{{ item.label }}</span>
                            <svg
                                class="w-3 h-3 transition-transform duration-150"
                                :class="openGroups.has(item.label) ? 'rotate-180' : ''"
                                viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"
                            >
                                <path d="m3 5 3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>

                        <div v-if="openGroups.has(item.label)" class="mt-0.5 ml-3 pl-4 border-l border-[color:var(--color-brand-700)] space-y-0.5">
                            <Link
                                v-for="child in item.children"
                                :key="child.href"
                                :href="child.href"
                                class="block px-2 py-1.5 rounded-[var(--radius-sm)] text-[12.5px] font-medium transition-colors relative"
                                :class="isActive(child.href)
                                    ? 'bg-[color:var(--color-brand-800)] text-white'
                                    : 'text-[color:var(--color-brand-400)] hover:text-white hover:bg-[color:var(--color-brand-800)]/50'"
                            >
                                <span
                                    v-if="isActive(child.href)"
                                    class="absolute left-0 top-1 bottom-1 w-0.5 bg-[color:var(--color-accent-400)] rounded-r"
                                />
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>

                </template>
            </nav>

            <div class="p-4 border-t border-[color:var(--color-brand-800)] text-[10px] uppercase tracking-[0.18em] text-[color:var(--color-brand-400)] font-medium">
                v0.1 · Sprint 4
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-[color:var(--color-surface)] border-b border-[color:var(--color-rule)] flex items-center justify-between px-6 shrink-0">
                <h1 class="font-display text-lg font-medium text-[color:var(--color-ink)] tracking-tight">
                    <slot name="header">Bluemocha ERP</slot>
                </h1>

                <div class="relative" ref="dropdownRef" @click.stop>
                    <button
                        v-if="user"
                        @click="userMenu = !userMenu"
                        class="flex items-center gap-2.5 px-2 py-1 rounded-[var(--radius-sm)] hover:bg-white transition-colors"
                    >
                        <span class="w-7 h-7 rounded-full bg-[color:var(--color-accent-100)] flex items-center justify-center text-[11px] font-semibold text-[color:var(--color-accent-800)]">
                            {{ user.display_name?.charAt(0)?.toUpperCase() ?? '?' }}
                        </span>
                        <span class="text-sm font-medium text-[color:var(--color-ink)]">{{ user.display_name }}</span>
                        <svg class="w-3.5 h-3.5 text-[color:var(--color-brand-400)]" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="m3 5 3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <span v-else class="text-sm text-[color:var(--color-brand-600)]">Guest</span>

                    <transition
                        enter-active-class="transition-all duration-150 ease-out"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition-all duration-100"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="userMenu"
                            class="absolute right-0 mt-1 w-56 bg-white border border-[color:var(--color-rule)] rounded-[var(--radius-sm)] shadow-[var(--shadow-pop)] py-1 z-30"
                        >
                            <div class="px-3 py-2 border-b border-[color:var(--color-rule)]">
                                <p class="text-[10px] uppercase tracking-wider text-[color:var(--color-brand-500)] mb-0.5">SMA ID</p>
                                <p class="text-sm font-mono text-[color:var(--color-ink)]">{{ user?.sma_id ?? '—' }}</p>
                            </div>
                            <button
                                @click="logout"
                                class="w-full text-left px-3 py-2 text-sm text-rose-700 hover:bg-rose-50 transition-colors"
                            >
                                Sign out
                            </button>
                        </div>
                    </transition>
                </div>
            </header>

            <main class="flex-1 p-8 overflow-auto">
                <slot />
            </main>
        </div>

        <ToastContainer />
    </div>
</template>
