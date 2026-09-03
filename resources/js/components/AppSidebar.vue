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
    LifeBuoy,
    LogOut,
    MessageSquare,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { index as jobChatIndex } from '@/actions/App/Http/Controllers/JobChatController';
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
const supportUrl = '/support';
const jobChatUrl = jobChatIndex();
const page = usePage();

const employerNavItems = computed<NavItem[]>(() => [
    { title: 'My Jobs', href: employerJobs.index(), icon: BriefcaseBusiness, isActive: isCurrentOrParentUrl(employerJobs.index()) },
    { title: 'Chat', href: jobChatUrl, icon: MessageSquare, isActive: isCurrentOrParentUrl(jobChatUrl) },
    { title: 'Settings', href: settingsUrl, icon: Settings, isActive: isCurrentOrParentUrl(settingsUrl) },
]);

const candidateNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', href: dashboardUrl, icon: LayoutGrid, isActive: isCurrentUrl(dashboardUrl) },
    { title: 'Resumes', href: resumesUrl, icon: FileText, isActive: isCurrentOrParentUrl(resumesUrl) },
    { title: 'Job Selection', href: jobSelectionRoute(), icon: BriefcaseBusiness, isActive: isCurrentUrl(jobSelectionRoute()) },
    { title: 'Application Tracker', href: requestTrackerUrl, icon: ClipboardList, isActive: isCurrentUrl(requestTrackerUrl) },
    { title: 'Chat', href: jobChatUrl, icon: MessageSquare, isActive: isCurrentOrParentUrl(jobChatUrl) },
    { title: 'Interview', href: interviewPreparationRoute(), icon: BrainCircuit, isActive: isCurrentUrl(interviewPreparationRoute()) },
    { title: 'Salary', href: salary(), icon: DollarSign, isActive: isCurrentUrl(salary()) },
    { title: 'Development', href: development(), icon: Code, isActive: isCurrentUrl(development()) },
    { title: 'Settings', href: settingsUrl, icon: Settings, isActive: isCurrentOrParentUrl(settingsUrl) },
]);

const isEmployer = computed(() => page.props.auth?.role === 'employer');
const mainNavItems = computed<NavItem[]>(() => isEmployer.value ? employerNavItems.value : candidateNavItems.value);
const logoUrl = computed(() => isEmployer.value ? employerJobs.index() : dashboardUrl);
const { state, isMobile, setOpenMobile } = useSidebar();
const handleLogout = () => { router.flushAll(); };
function handleNavigation() { if (isMobile.value) setOpenMobile(false); }
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-white/10 bg-[#051C2E] text-slate-200 [--sidebar-accent-foreground:#FFFFFF] [--sidebar-accent:#08263D] [--sidebar-border:rgba(255,255,255,0.10)] [--sidebar-foreground:#DCE5F4] [--sidebar-primary-foreground:#FFFFFF] [--sidebar-primary:#0A2E48] [--sidebar:#051C2E]">
        <SidebarHeader class="bg-[#051C2E] text-white">
            <SidebarMenu><SidebarMenuItem><SidebarMenuButton size="lg" as-child class="h-auto py-2 hover:bg-transparent hover:text-inherit active:bg-transparent active:text-inherit"><Link :href="logoUrl" @click="handleNavigation"><AppLogo /></Link></SidebarMenuButton></SidebarMenuItem></SidebarMenu>
        </SidebarHeader>
        <SidebarContent class="bg-[#051C2E]"><NavMain :items="mainNavItems" :candidate="!isEmployer" /></SidebarContent>
        <SidebarFooter class="bg-[#051C2E] text-slate-200">
            <SidebarMenu>
                <SidebarMenuItem><SidebarMenuButton size="lg" as-child><Link :href="supportUrl" @click="handleNavigation"><LifeBuoy /><span class="font-medium" :class="[isEmployer ? 'text-sm' : 'text-[15px]', state === 'collapsed' && 'hidden']">Support</span></Link></SidebarMenuButton></SidebarMenuItem>
                <SidebarMenuItem><SidebarMenuButton size="lg" as-child><Link :href="logout()" as="button" @click="handleLogout"><LogOut /><span class="font-medium" :class="[isEmployer ? 'text-sm' : 'text-[15px]', state === 'collapsed' && 'hidden']">Log out</span></Link></SidebarMenuButton></SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
