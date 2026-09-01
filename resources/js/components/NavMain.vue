<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
    candidate?: boolean;
}>();

const { isCurrentUrl } = useCurrentUrl();
const { state, isMobile, setOpenMobile } = useSidebar();

const hasDefaultActive = computed(() =>
    props.items.some((item) => item.isActive === true),
);

function isMenuItemActive(item: NavItem): boolean {
    // If no real route pages exist yet, we can still show a default active
    // item for the sidebar UI.
    return hasDefaultActive.value
        ? item.isActive === true
        : isCurrentUrl(item.href);
}

function handleNavigation() {
    // Close mobile sidebar when a navigation item is clicked
    if (isMobile.value) {
        setOpenMobile(false);
    }
}
</script>

<template>
    <SidebarGroup class="px-2 py-0 text-slate-200">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    size="lg"
                    :is-active="isMenuItemActive(item)"
                    :tooltip="item.title"
                    class="text-slate-200 transition-colors duration-200 hover:bg-[#08263D] hover:text-white data-[active=true]:bg-[#0A2E48] data-[active=true]:font-semibold data-[active=true]:text-white"
                    :class="props.candidate ? 'text-[15px]' : 'text-[13.5px]'"
                >
                    <Link :href="item.href" @click="handleNavigation">
                        <component :is="item.icon" />
                        <span
                            class="truncate font-medium"
                            :class="[
                                props.candidate
                                    ? 'text-[15px]'
                                    : 'text-[13.5px]',
                                state === 'collapsed' && 'hidden',
                            ]"
                        >
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
