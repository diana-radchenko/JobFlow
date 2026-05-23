<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bot,
    SlidersHorizontal,
    ArrowDownUp,
    Maximize2,
    Sparkles,
    Heart,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { stringForHuman } from '@/helpers/strings';
import { dashboard } from '@/routes';
import type { UserWorkJobApplication } from '@/types/laravel-models';

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
    profileFirstName: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// Mock data
const mockApplications = [
    {
        id: 'm1',
        company: 'TechCorp',
        title: 'Software Developer',
        salary: '85,000',
        status: 'Interview Scheduled',
        statusClass: 'status-green',
    },
    {
        id: 'm3',
        company: 'DataSoft',
        title: 'Data Analyst',
        salary: '75,000',
        status: 'Rejected',
        statusClass: 'status-red',
    },
];

const tableApplications = computed(() => {
    const realApps = (props.applications || []).map((app) => {
        const statusText = stringForHuman(app.status);
        let statusClass = 'status-grey';

        if (statusText.toLowerCase().includes('interview')) {
            statusClass = 'status-green';
        }

        if (statusText.toLowerCase().includes('reject')) {
            statusClass = 'status-red';
        }

        return {
            id: app.id,
            title: app.work_job?.title || 'Unknown Job',
            company: app.work_job?.company || 'Unknown Company',
            salary: app.work_job?.salary_start
                ? Number(app.work_job.salary_start).toLocaleString()
                : '-',
            status: statusText,
            statusClass,
        };
    });

    // Only take real + 2 mock as requested or all mock?
    // "Notice that Application tracker should be like this @resources/js/pages/RequestTracker.vue:176-219 (real + 2 mock test applications)"
    // The prompt means real + some mock test applications.
    return [...realApps, ...mockApplications.slice(0, 5)];
});

const scheduleDays = [
    { day: 'Sun', date: '16', active: false },
    { day: 'Mon', date: '17', active: true },
    { day: 'Tue', date: '18', active: false },
    { day: 'Wed', date: '19', active: false },
    { day: 'Thu', date: '20', active: false },
    { day: 'Fri', date: '21', active: false },
];

const timelineEvents = [
    {
        time: '09.00',
        hasEvent: true,
        title: 'Daily Sync',
        duration: '09.00am-9.30am',
    },
    { time: '10.00', hasEvent: false },
    {
        time: '11.00',
        hasEvent: true,
        title: 'Interview',
        duration: '11.00am-11.30am',
    },
    {
        time: '12.00',
        hasEvent: true,
        title: 'Interview',
        duration: '12.00am-12.30am',
    },
    { time: '13.00', hasEvent: false },
    { time: '14.00', hasEvent: false },
];

const aiJobsMock = [
    {
        id: 1,
        company: 'InnovateTech',
        logoText: 'IT',
        title: 'InnovateTech is looking for a Software Engineer to join our team!',
        salary: '$95,000',
        tags: ['Python', 'React'],
        score: '72/100',
    },
    {
        id: 2,
        company: 'DataWise',
        logoText: 'DW',
        title: 'DataWise is hiring a Data Scientist to drive data-driven decision-making.',
        salary: '$105,000',
        tags: ['Machine learning'],
        score: '83/100',
    },
    {
        id: 3,
        company: 'SecureNet',
        logoText: 'SN',
        title: 'SecureNet is looking for a Cybersecurity Specialist!',
        salary: '$98,000',
        tags: ['SOC', 'IDS/IPS'],
        score: '77/100',
    },
];

const articlesMock = [
    {
        id: 1,
        image: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=400&h=150&auto=format&fit=crop',
        title: 'Part-Time Jobs for Retirees: Finding Flexible Work That Fits Your Lifestyle',
    },
    {
        id: 2,
        image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=400&h=150&auto=format&fit=crop',
        title: 'How to Become a Financial Planner After 50: A Late-Career Pivot',
    },
    {
        id: 3,
        image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=400&h=150&auto=format&fit=crop',
        title: 'Internship After Graduation vs. After 50: A Tale of Two Journeys',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="container mx-auto max-w-[1400px] px-5 py-8 font-sans">
        <div
            class="grid grid-cols-1 gap-8 lg:grid-cols-[320px_1fr] xl:grid-cols-[380px_1fr]"
        >
            <!-- Left Column: Schedule -->
            <div class="space-y-6">
                <div class="mb-8 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800"
                    >
                        <Bot
                            class="h-6 w-6 text-slate-700 dark:text-slate-300"
                        />
                    </div>
                    <h1
                        class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50"
                    >
                        Welcome back, {{ props.profileFirstName }}!
                    </h1>
                </div>

                <h2
                    class="text-xl font-bold text-slate-900 dark:text-slate-100"
                >
                    Schedule
                </h2>

                <Card
                    class="overflow-hidden rounded-[24px] border-0 bg-slate-50 shadow-sm dark:bg-slate-900"
                >
                    <CardContent class="p-0">
                        <!-- Calendar Header -->
                        <div
                            class="flex justify-between border-b border-slate-200/60 px-4 py-6 dark:border-slate-800"
                        >
                            <div
                                v-for="day in scheduleDays"
                                :key="day.date"
                                class="flex flex-col items-center gap-1"
                            >
                                <span
                                    class="text-xs font-medium text-slate-500 dark:text-slate-400"
                                    :class="{
                                        'font-bold text-slate-900 dark:text-slate-100':
                                            day.active,
                                    }"
                                >
                                    {{ day.day }}
                                </span>
                                <span
                                    class="text-lg font-bold"
                                    :class="
                                        day.active
                                            ? 'text-slate-900 dark:text-slate-100'
                                            : 'text-slate-400 dark:text-slate-500'
                                    "
                                >
                                    {{ day.date }}
                                </span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="relative p-6">
                            <!-- Continuous line -->
                            <div
                                class="absolute top-10 bottom-6 left-[4.5rem] w-px border-l-2 border-dashed border-slate-300 dark:border-slate-700"
                            ></div>

                            <div
                                v-for="(event, index) in timelineEvents"
                                :key="index"
                                class="relative mb-8 flex min-h-[3rem] gap-6 last:mb-0"
                            >
                                <!-- Time Badge -->
                                <div
                                    class="relative z-10 flex w-16 shrink-0 justify-end"
                                >
                                    <div
                                        class="flex h-8 items-center justify-center rounded-full bg-primary px-3 text-sm font-bold text-primary-foreground shadow-sm"
                                    >
                                        {{ event.time }}
                                    </div>
                                </div>

                                <!-- Event Card -->
                                <div v-if="event.hasEvent" class="mt-6 flex-1">
                                    <div
                                        class="relative rounded-[16px] bg-primary p-4 text-primary-foreground shadow-sm"
                                    >
                                        <div class="text-[15px] font-bold">
                                            {{ event.title }}
                                        </div>
                                        <div
                                            class="mt-1 text-sm text-primary-foreground/80"
                                        >
                                            {{ event.duration }}
                                        </div>
                                        <div
                                            class="absolute top-4 right-4 h-2 w-2 rounded-full bg-white"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Application Tracker -->
                <div>
                    <div class="mt-2 mb-4 flex items-center justify-between">
                        <h2
                            class="text-xl font-bold text-slate-900 dark:text-slate-100"
                        >
                            Application Tracker
                        </h2>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-9 w-9 rounded-full border-0 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground"
                            >
                                <SlidersHorizontal class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-9 w-9 rounded-full border-0 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground"
                            >
                                <ArrowDownUp class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-9 w-9 rounded-full border-0 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground"
                            >
                                <Maximize2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <Card
                        class="overflow-hidden rounded-[24px] border border-slate-200/60 bg-slate-50 shadow-sm dark:bg-slate-900"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-900/50">
                                    <tr>
                                        <th
                                            class="px-6 py-4 font-bold whitespace-nowrap text-slate-900 dark:text-slate-100"
                                        >
                                            Company Name
                                        </th>
                                        <th
                                            class="px-6 py-4 font-bold whitespace-nowrap text-slate-900 dark:text-slate-100"
                                        >
                                            Job Title
                                        </th>
                                        <th
                                            class="px-6 py-4 font-bold whitespace-nowrap text-slate-900 dark:text-slate-100"
                                        >
                                            Salary (USD)
                                        </th>
                                        <th
                                            class="px-6 py-4 text-right font-bold whitespace-nowrap text-slate-900 dark:text-slate-100"
                                        >
                                            Application Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-white dark:divide-slate-800"
                                >
                                    <tr
                                        v-for="app in tableApplications"
                                        :key="app.id"
                                        class="bg-white dark:bg-slate-950"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400"
                                        >
                                            {{ app.company }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400"
                                        >
                                            {{ app.title }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400"
                                        >
                                            {{ app.salary }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="inline-flex w-full min-w-[160px] items-center justify-end gap-3"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-slate-400"
                                                    >{{ app.status }}</span
                                                >
                                                <!-- Custom colored box according to status -->
                                                <div
                                                    class="h-4 w-4 rounded-sm shadow-sm"
                                                    :class="app.statusClass"
                                                ></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Bottom Row: AI Jobs & Articles -->
                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- AI Recommended Jobs -->
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <h2
                                class="text-lg font-bold text-slate-900 dark:text-slate-100"
                            >
                                AI-Recommended Jobs for You
                            </h2>
                            <Sparkles class="h-5 w-5 text-primary" />
                        </div>
                        <div class="space-y-4">
                            <Card
                                v-for="job in aiJobsMock"
                                :key="job.id"
                                class="rounded-[24px] border border-slate-200/60 bg-slate-50 py-2 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                            >
                                <CardContent class="p-5">
                                    <div class="mb-4 flex items-start gap-4">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground shadow-sm"
                                        >
                                            {{ job.logoText }}
                                        </div>
                                        <div class="flex-1">
                                            <p
                                                class="text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
                                            >
                                                {{ job.title }}
                                            </p>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="-mt-1 -mr-1 h-8 w-8 shrink-0 rounded-full text-slate-400 hover:text-primary"
                                        >
                                            <Heart class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <div
                                        class="mb-5 flex items-center gap-3 pl-14"
                                    >
                                        <Badge>
                                            Salary: {{ job.salary }}
                                        </Badge>
                                        <Badge
                                            v-for="tag in job.tags"
                                            :key="tag"
                                            variant="outline"
                                            class="border-slate-200 bg-white text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-950"
                                        >
                                            {{ tag }}
                                        </Badge>
                                    </div>

                                    <div
                                        class="flex items-center justify-between rounded-xl border border-slate-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950"
                                    >
                                        <div
                                            class="px-4 text-sm font-bold text-slate-700 dark:text-slate-300"
                                        >
                                            AI SCORE: {{ job.score }}
                                        </div>
                                        <Button
                                            variant="ghost"
                                            class="h-9 rounded-lg border border-slate-100 bg-white text-xs font-bold text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                        >
                                            OPTIMIZE RESUME
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Interesting Articles -->
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <h2
                                class="text-lg font-bold text-slate-900 dark:text-slate-100"
                            >
                                Interesting Articles
                            </h2>
                            <Sparkles class="h-5 w-5 text-primary" />
                        </div>
                        <div class="space-y-4">
                            <Card
                                v-for="article in articlesMock"
                                :key="article.id"
                                class="flex flex-col gap-3 overflow-hidden rounded-[24px] border border-slate-200/60 bg-white py-0 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                            >
                                <img
                                    :src="article.image"
                                    alt="Article cover"
                                    class="h-28 w-full rounded-t-[16px] object-cover"
                                />
                                <p
                                    class="px-4 pb-4 text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
                                >
                                    {{ article.title }}
                                </p>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
