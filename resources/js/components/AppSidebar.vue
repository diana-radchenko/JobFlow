<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
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
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import { SidebarHeader } from '@/components/ui/sidebar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import {
    dashboard,
    development,
    interviewPreparation as interviewPreparationRoute,
    jobSelection as jobSelectionRoute,
    logout,
    requestTracker as requestTrackerRoute,
    salary,
} from '@/routes';
import employerJobs from '@/routes/employer/jobs';
import resumes from '@/routes/resumes';
import type { NavItem } from '@/types';

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const dashboardUrl = dashboard();
const requestTrackerUrl = requestTrackerRoute();
const resumesUrl = resumes.index();
const settingsUrl = '/settings';

const page = usePage();

const employerNavItems = computed<NavItem[]>(() => [
    {
        title: 'My Jobs',
        href: employerJobs.index(),
        icon: BriefcaseBusiness,
        isActive: isCurrentOrParentUrl(employerJobs.index()),
    },
    {
        title: 'Settings',
        href: settingsUrl,
        icon: Settings,
        isActive: isCurrentOrParentUrl(settingsUrl),
    },
]);

const candidateNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl,
        icon: LayoutGrid,
        isActive: isCurrentUrl(dashboardUrl),
    },
    {
        title: 'Resumes',
        href: resumesUrl,
        icon: FileText,
        isActive: isCurrentOrParentUrl(resumesUrl),
    },
    {
        title: 'Job Selection',
        href: jobSelectionRoute(),
        icon: BriefcaseBusiness,
        isActive: isCurrentUrl(jobSelectionRoute()),
    },
    {
        title: 'Request Tracker',
        href: requestTrackerUrl,
        icon: ClipboardList,
        isActive: isCurrentUrl(requestTrackerUrl),
    },
    {
        title: 'Interview',
        href: interviewPreparationRoute(),
        icon: BrainCircuit,
        isActive: isCurrentUrl(interviewPreparationRoute()),
    },
    {
        title: 'Salary',
        href: salary(),
        icon: DollarSign,
        isActive: isCurrentUrl(salary()),
    },
    {
        title: 'Development',
        href: development(),
        icon: Code,
        isActive: isCurrentUrl(development()),
    },
    {
        title: 'Settings',
        href: settingsUrl,
        icon: Settings,
        isActive: isCurrentOrParentUrl(settingsUrl),
    },
]);

const isEmployer = computed(() => page.props.auth?.role === 'employer');

const mainNavItems = computed<NavItem[]>(() =>
    isEmployer.value ? employerNavItems.value : candidateNavItems.value,
);

const logoUrl = computed(() =>
    isEmployer.value ? employerJobs.index() : dashboardUrl,
);

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
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-transparent hover:text-inherit active:bg-transparent active:text-inherit"
                    >
                        <Link :href="logoUrl" @click="handleNavigation">
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
                            <span :class="state === 'collapsed' && 'hidden'"
                                >Support</span
                            >
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
                            <span :class="state === 'collapsed' && 'hidden'"
                                >Log out</span
                            >
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
