<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
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
    <GuestLayout :boxed="false">
        <Head title="Log in" />

        <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-8 py-10 text-white shadow-2xl shadow-slate-950/30 ring-1 ring-white/10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,113,113,0.22),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(59,130,246,0.18),transparent_30%)]"></div>
                <div class="relative">
                    <p class="inline-flex items-center rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-200">
                        Audit & Incident Log
                    </p>

                    <h1 class="mt-6 text-3xl font-semibold leading-tight sm:text-4xl">
                        Secure access for incident handling and audit review.
                    </h1>

                    <p class="mt-4 max-w-md text-sm leading-6 text-slate-300">
                        Sign in to monitor incidents, inspect timelines, review audit trails, and manage workflow actions in one place.
                    </p>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Incident flow</div>
                            <div class="mt-2 text-sm font-medium text-white">Track states</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Audit trail</div>
                            <div class="mt-2 text-sm font-medium text-white">Trace actions</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Role access</div>
                            <div class="mt-2 text-sm font-medium text-white">Operator / Engineer / Supervisor</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white px-8 py-10 shadow-xl shadow-slate-200/70 ring-1 ring-slate-200">
                <div class="mb-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Welcome back</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Log in to continue</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Access the incident management workspace and keep your audit records in sync.
                    </p>
                </div>

                <div v-if="status" class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Email" class="text-slate-700" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-700" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-slate-600">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-950"
                        >
                            Forgot your password?
                        </Link>
                    </div>

                    <PrimaryButton
                        class="flex w-full justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Log in
                    </PrimaryButton>
                </form>
            </section>
        </div>
    </GuestLayout>
</template>
