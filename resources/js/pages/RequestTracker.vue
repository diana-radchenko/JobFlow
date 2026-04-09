<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { requestTracker } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Request Tracker',
                href: requestTracker(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Request Tracker" />

    
</template>
