<script setup>
import { computed } from 'vue';

const props = defineProps({
    pagination: { type: Object, required: true },
    perPageOptions: { type: Array, default: () => [10, 25, 50, 100, 250] },
});
const emit = defineEmits(['change-page', 'change-per-page']);

const totalPages = computed(() => props.pagination.last_page ?? 1);
const currentPage = computed(() => props.pagination.current_page ?? 1);

// Render at most 7 page buttons with ellipses.
const pageButtons = computed(() => {
    const last = totalPages.value;
    const curr = currentPage.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);

    const pages = new Set([1, last, curr - 1, curr, curr + 1]);
    if (curr <= 3) [2, 3, 4, 5].forEach(p => pages.add(p));
    if (curr >= last - 2) [last - 4, last - 3, last - 2, last - 1].forEach(p => pages.add(p));

    const sorted = [...pages].filter(p => p >= 1 && p <= last).sort((a, b) => a - b);
    const out = [];
    for (let i = 0; i < sorted.length; i++) {
        out.push(sorted[i]);
        if (sorted[i + 1] && sorted[i + 1] - sorted[i] > 1) out.push('…');
    }
    return out;
});
</script>

<template>
    <div class="px-4 py-3 border-t border-[color:var(--color-rule)] bg-[color:var(--color-brand-50)] flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3 text-[color:var(--color-brand-600)]">
            <span>
                Showing <span class="font-mono tabular-nums text-[color:var(--color-ink)]">{{ pagination.from?.toLocaleString() ?? 0 }}</span>
                – <span class="font-mono tabular-nums text-[color:var(--color-ink)]">{{ pagination.to?.toLocaleString() ?? 0 }}</span>
                of <span class="font-mono tabular-nums text-[color:var(--color-ink)]">{{ pagination.total?.toLocaleString() ?? 0 }}</span>
            </span>

            <span class="text-[color:var(--color-brand-300)]">·</span>

            <label class="inline-flex items-center gap-2">
                <span>Per page</span>
                <select
                    :value="pagination.per_page"
                    @change="emit('change-per-page', Number($event.target.value))"
                    class="h-7 pl-2 pr-7 text-xs border border-[color:var(--color-brand-300)] rounded-[var(--radius-xs)] bg-white focus:outline-none focus:border-[color:var(--color-accent-500)]"
                >
                    <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                </select>
            </label>
        </div>

        <div class="flex items-center gap-1">
            <button
                :disabled="currentPage <= 1"
                @click="emit('change-page', currentPage - 1)"
                class="h-7 px-2 text-xs rounded-[var(--radius-xs)] hover:bg-white disabled:opacity-30 disabled:cursor-not-allowed"
            >‹ Prev</button>

            <button
                v-for="(p, i) in pageButtons"
                :key="i"
                :disabled="p === '…'"
                @click="p !== '…' && emit('change-page', p)"
                class="h-7 min-w-[28px] px-1 text-xs font-mono tabular-nums rounded-[var(--radius-xs)] transition-colors"
                :class="p === currentPage
                    ? 'bg-[color:var(--color-brand-900)] text-white'
                    : p === '…' ? 'cursor-default text-[color:var(--color-brand-400)]' : 'hover:bg-white text-[color:var(--color-brand-700)]'"
            >{{ p }}</button>

            <button
                :disabled="currentPage >= totalPages"
                @click="emit('change-page', currentPage + 1)"
                class="h-7 px-2 text-xs rounded-[var(--radius-xs)] hover:bg-white disabled:opacity-30 disabled:cursor-not-allowed"
            >Next ›</button>
        </div>
    </div>
</template>
