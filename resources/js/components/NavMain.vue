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
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    size="lg"
                    :is-active="isMenuItemActive(item)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href" @click="handleNavigation">
                        <component :is="item.icon" />
                        <span
                            class="truncate text-sm font-medium"
                            :class="state === 'collapsed' && 'hidden'"
                        >
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

