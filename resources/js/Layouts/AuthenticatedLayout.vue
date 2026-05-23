<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role ?? '');
import axios from 'axios';

const unreadCount = ref(0);
const isSidebarOpen = ref(false);

const fetchUnread = async () => {
    try {
        const url = route('notifications.index');
        const res = await axios.get(url);
        unreadCount.value = res.data?.stats?.unread_count ?? 0;
    } catch (e) {
        // ignore
    }
};

onMounted(() => {
    fetchUnread();
});

const onNotificationsUpdated = (e) => {
    try {
        const val = e?.detail?.unread_count ?? null;
        if (typeof val === 'number') unreadCount.value = val;
        else fetchUnread();
    } catch {
        fetchUnread();
    }
};

onMounted(() => window.addEventListener('notifications-updated', onNotificationsUpdated));
onUnmounted(() => window.removeEventListener('notifications-updated', onNotificationsUpdated));
const sidebarLinks = computed(() => {
    const role = userRole.value;

    const commonLinks = [
        { label: 'Dashboard', href: route('dashboard'), name: 'dashboard' },
        // { label: 'Profile', href: route('profile.edit'), name: 'profile.edit' },
    ];

    if (role === 'supervisor') {
        return [
            ...commonLinks,
            { label: 'Audit', href: route('audit.index'), name: 'audit.index' },
            { label: 'Incidents', href: route('incidents.index'), name: 'incidents.index' },
            { label: 'Notifications', href: route('notifications.index'), name: 'notifications.index' },
            { label: 'Master Data', href: route('master-data.index'), name: 'master-data.index' },
        ];
    }

    if (role === 'engineer') {
        return [
            ...commonLinks,
            { label: 'Incidents', href: route('incidents.index'), name: 'incidents.index' },
            { label: 'Notifications', href: route('notifications.index'), name: 'notifications.index' },
            { label: 'Master Data', href: route('master-data.index'), name: 'master-data.index' },
        ];
    }

    return [
        ...commonLinks,
        { label: 'Incidents', href: route('incidents.index'), name: 'incidents.index' },
        { label: 'Notifications', href: route('notifications.index'), name: 'notifications.index' },
    ];
});
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="hidden lg:block border-b border-slate-200 bg-slate-950 text-white lg:w-72 lg:border-b-0 lg:border-r lg:border-slate-800">
                <div class="flex h-16 items-center gap-3 px-6">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <ApplicationLogo class="block h-9 w-auto fill-current text-white" />
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-300">Minilog</div>
                            <div class="text-xs text-slate-400">Incident Dashboard</div>
                        </div>
                    </Link>
                </div>

                <nav class="px-4 py-4 flex flex-col">
                    <div class="flex flex-col space-y-1">
                        <div v-for="item in sidebarLinks" :key="item.name" class="w-full">
                            <NavLink
                                :href="item.href"
                                :active="route().current(item.name)"
                                class="w-full text-left block rounded-lg px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white flex items-center justify-between"
                            >
                                <span>{{ item.label }}</span>
                                <span v-if="item.name === 'notifications.index' && unreadCount > 0" class="ml-2 inline-flex items-center rounded-full bg-rose-600 px-2 py-0.5 text-[11px] font-semibold text-white">{{ unreadCount }}</span>
                            </NavLink>
                        </div>
                    </div>
                </nav>
            </aside>

            <!-- Mobile sidebar (off-canvas) -->
            <div v-if="isSidebarOpen" class="fixed inset-0 z-40 flex lg:hidden">
                <div class="fixed inset-0 bg-black/40" @click="isSidebarOpen = false" aria-hidden="true"></div>

                <aside class="relative z-50 w-72 border-r border-slate-800 bg-slate-950 text-white">
                    <div class="flex h-16 items-center gap-3 px-6">
                        <Link :href="route('dashboard')" class="flex items-center gap-3">
                            <ApplicationLogo class="block h-9 w-auto fill-current text-white" />
                            <div>
                                <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-300">Minilog</div>
                                <div class="text-xs text-slate-400">Incident Dashboard</div>
                            </div>
                        </Link>
                        <button class="ml-auto text-slate-300" @click="isSidebarOpen = false" aria-label="Close sidebar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 011.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <nav class="px-4 py-4">
                        <div class="flex flex-col space-y-1">
                            <div v-for="item in sidebarLinks" :key="item.name" class="w-full">
                                <NavLink
                                    :href="item.href"
                                    :active="route().current(item.name)"
                                    @click="isSidebarOpen = false"
                                    class="w-full text-left block rounded-lg px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 hover:text-white flex items-center justify-between"
                                >
                                    <span>{{ item.label }}</span>
                                    <span v-if="item.name === 'notifications.index' && unreadCount > 0" class="ml-2 inline-flex items-center rounded-full bg-rose-600 px-2 py-0.5 text-[11px] font-semibold text-white">{{ unreadCount }}</span>
                                </NavLink>
                            </div>
                        </div>
                    </nav>
                </aside>
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="border-b border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 px-3 py-3 sm:px-6 lg:px-8 lg:min-h-16 lg:py-0">
                        <div class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
                            <button class="inline-flex shrink-0 items-center rounded-md bg-white p-2 text-slate-700 hover:bg-slate-50 lg:hidden" @click="isSidebarOpen = true" aria-label="Open sidebar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 5h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2zm0 4h14a1 1 0 010 2H3a1 1 0 110-2z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div v-if="$slots.header" class="min-w-0 flex-1 overflow-hidden">
                                <slot name="header" />
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center justify-end gap-3">
                            <div class="hidden text-right sm:block">
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>

                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                                            >
                                                Profile

                                                <svg
                                                    class="ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="p-4">
                    <FlashMessages />
                </div>

                <main class="flex-1 px-2 sm:px-4 lg:px-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
