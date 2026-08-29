<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar/utils';

const { state } = useSidebar();
</script>

<template>
    <div
        class="flex aspect-square size-14 items-center justify-center rounded-md text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-10 fill-current text-white dark:text-black" />
    </div>
    <div
        class="ml-2 grid flex-1 text-left text-base"
        :class="state === 'collapsed' && 'hidden'"
    >
        <span class="mb-0.5 truncate leading-tight font-bold">JobFlow</span>
    </div>
</template>

