<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    boxed: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-slate-100 pt-6 sm:justify-center sm:pt-0"
    >
        <!-- <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-slate-500" />
            </Link>
        </div> -->

        <div
            :class="boxed ? 'mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg' : 'mt-6 w-full max-w-6xl px-4 sm:px-6 lg:px-8'"
        >
            <slot />
        </div>
    </div>
</template>
