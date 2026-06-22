<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <template #header>Welcome</template>

        <div class="max-w-3xl">
            <div class="bg-white rounded-lg shadow-sm border border-brand-200 p-8">
                <h2 class="text-2xl font-bold text-brand-900">
                    🎉 Foundation is alive.
                </h2>
                <p class="mt-2 text-brand-600">
                    Laravel 12 · Inertia.js · Vue 3 Composition API · Tailwind CSS 4
                </p>
                <ul class="mt-6 space-y-2 text-sm text-brand-700">
                    <li>✅ Inertia handshake working</li>
                    <li>✅ Vite hot-reload active</li>
                    <li>✅ Tailwind utilities resolving</li>
                    <li>⏭️ Next: Sprint 2 — Spatie auth &amp; permissions</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
