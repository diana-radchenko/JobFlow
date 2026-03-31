<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { SidebarHeader } from '@/components/ui/sidebar';
import { Link, router } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FileText,
    BriefcaseBusiness,
    ClipboardList,
    DollarSign,
    BrainCircuit,
    Code,
    Settings,
    Globe,
    LogOut,
} from 'lucide-vue-next';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { computed } from 'vue';

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const dashboardUrl = dashboard();
const settingsUrl = '/settings';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl,
        icon: LayoutGrid,
        isActive: isCurrentUrl(dashboardUrl),
    },
    {
        title: 'Resume Editor',
        href: '#',
        icon: FileText,
    },
    {
        title: 'Job Selection',
        href: '#',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Request Tracker',
        href: '#',
        icon: ClipboardList,
    },
    {
        title: 'Salary',
        href: '#',
        icon: DollarSign,
    },
    {
        title: 'Interview Preparing',
        href: '#',
        icon: BrainCircuit,
    },
    {
        title: 'Development',
        href: '#',
        icon: Code,
    },
    {
        title: 'Settings',
        href: settingsUrl,
        icon: Settings,
        isActive: isCurrentOrParentUrl(settingsUrl),
    },
]);

const { state, isMobile, setOpenMobile } = useSidebar();

const handleLogout = () => {
    router.flushAll();
};

function handleNavigation() {
    // Close mobile sidebar when logo or footer links are clicked
    if (isMobile.value) {
        setOpenMobile(false);
    }
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">

        <SidebarHeader class="no-hover-header">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()" @click="handleNavigation">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a href="#" @click.prevent>
                            <Globe />
                            <span :class="state === 'collapsed' && 'hidden'">Support</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link
                            :href="logout()"
                            as="button"
                            @click="handleLogout"
                        >
                            <LogOut />
                            <span :class="state === 'collapsed' && 'hidden'">Log out</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style scoped>
.no-hover-header :hover {
    background-color: inherit;
    color: inherit;
}
</style>
