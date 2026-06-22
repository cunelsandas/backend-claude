<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormInput from '@/Components/Form/FormInput.vue';
import FormCheckbox from '@/Components/Form/FormCheckbox.vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    status:   { type: String, default: null },
    canReset: { type: Boolean, default: false },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign in" />

    <div class="min-h-screen flex bg-[color:var(--color-surface)]">
        <!-- Left: editorial brand panel -->
        <aside class="hidden lg:flex w-1/2 bg-[color:var(--color-brand-900)] text-white p-12 flex-col justify-between relative overflow-hidden">
            <!-- Decorative grid pattern -->
            <div
                class="absolute inset-0 opacity-[0.06] pointer-events-none"
                style="background-image: linear-gradient(currentColor 1px, transparent 1px), linear-gradient(90deg, currentColor 1px, transparent 1px); background-size: 32px 32px;"
            />
            <!-- Accent corner mark -->
            <div class="absolute top-12 right-12 w-32 h-32 border-2 border-[color:var(--color-accent-400)]/40 rounded-full" />
            <div class="absolute top-20 right-20 w-16 h-16 bg-[color:var(--color-accent-500)]/20 rounded-full blur-2xl" />

            <div class="relative">
                <p class="text-[11px] uppercase tracking-[0.25em] text-[color:var(--color-accent-300)] font-semibold">Operations Console</p>
                <h1 class="font-display text-6xl font-medium tracking-tight mt-3 leading-[0.95]">Bluemocha</h1>
                <p class="font-display text-2xl italic text-[color:var(--color-accent-300)] mt-1">Enterprise Resource Planning</p>
            </div>

            <div class="relative max-w-md">
                <p class="font-display text-2xl font-light leading-snug text-[color:var(--color-brand-200)]">
                    “A tool is only worth using when it disappears into the work.”
                </p>
                <p class="mt-4 text-xs uppercase tracking-[0.18em] text-[color:var(--color-brand-400)]">v0.1 — Sprint 3 · UI Foundation</p>
            </div>
        </aside>

        <!-- Right: login form -->
        <main class="flex-1 flex items-center justify-center px-6">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8 lg:hidden">
                    <h1 class="font-display text-3xl font-medium text-[color:var(--color-ink)] tracking-tight">Bluemocha ERP</h1>
                </div>

                <h2 class="font-display text-3xl font-medium text-[color:var(--color-ink)] tracking-tight">Welcome back.</h2>
                <p class="mt-2 text-sm text-[color:var(--color-brand-600)]">Sign in with your employee credentials to continue.</p>

                <div
                    v-if="status"
                    class="mt-6 rounded-[var(--radius-sm)] bg-[color:var(--color-accent-100)] border border-[color:var(--color-accent-300)] px-3 py-2 text-sm text-[color:var(--color-accent-900)]"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4 mt-8">
                    <FormInput
                        v-model="form.username"
                        label="Username"
                        autocomplete="username"
                        :error="form.errors.username"
                        autofocus
                        required
                    />

                    <FormInput
                        v-model="form.password"
                        label="Password"
                        type="password"
                        autocomplete="current-password"
                        :error="form.errors.password"
                        required
                    />

                    <FormCheckbox v-model="form.remember" label="Remember me on this device" />

                    <Button
                        type="submit"
                        variant="solid"
                        tone="primary"
                        size="lg"
                        class="w-full"
                        :loading="form.processing"
                    >
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </Button>
                </form>

                <p class="mt-12 text-[10px] uppercase tracking-[0.18em] text-[color:var(--color-brand-400)] text-center">
                    Bluemocha ERP — v0.1
                </p>
            </div>
        </main>
    </div>
</template>
