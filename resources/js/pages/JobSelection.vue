<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { jobSelection as jobSelectionRoute } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Job Selection',
                href: jobSelectionRoute(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Job Selection" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div
            class="relative min-h-[40vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
