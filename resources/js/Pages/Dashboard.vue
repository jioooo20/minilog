<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OperatorDashboard from '@/Pages/Dashboard/Operator.vue';
import EngineerDashboard from '@/Pages/Dashboard/Engineer.vue';
import SupervisorDashboard from '@/Pages/Dashboard/Supervisor.vue';

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.role ?? null);
const userRoleLabel = computed(() => userRole.value ?? 'unknown');

const currentComponent = computed(() => {
    switch ((userRole.value || '').toLowerCase()) {
        case 'operator':
            return OperatorDashboard;
        case 'engineer':
            return EngineerDashboard;
        case 'supervisor':
            return SupervisorDashboard;
        default:
            return null;
    }
});
</script>

<template>
    <component v-if="currentComponent" :is="currentComponent" />
    <div v-else class="text-gray-700">
        Dashboard for role ({{ userRoleLabel }}) is not available yet. Coming soon.
    </div>
</template>
