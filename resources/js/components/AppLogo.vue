<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar/utils';

const { state } = useSidebar();
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div
        class="ml-1 grid flex-1 text-left text-sm"
        :class="state === 'collapsed' && 'hidden'"
    >
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >JobFlow</span
        >
    </div>
</template>
