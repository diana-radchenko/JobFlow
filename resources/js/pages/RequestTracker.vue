<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { requestTracker } from '@/routes';
import { computed } from 'vue';
import {
    Search,
    Filter,
    SlidersHorizontal,
    Monitor,
    Code,
    Shield,
    BrainCircuit,
    FileCode,
    Cloud,
    RefreshCw,
    Bell,
    X,
    MoreVertical,
    Clock,
} from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { UserWorkJobApplication } from '@/types/laravel-models';

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
}>();

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

// Mock applications
const mockApplications = [
    {
        id: 'm1',
        title: 'Software Engineer (Backend)',
        company: 'TechNova Solutions',
        dateType: 'Submission Date',
        dateValue: 'March 5, 2025',
        status: 'Viewed',
        percentage: 15,
        icon: Monitor,
        actionIcon: RefreshCw,
    },
    {
        id: 'm2',
        title: 'Frontend Developer',
        company: 'PixelCraft Studios',
        dateType: 'Interview Date',
        dateValue: 'March 10, 2025',
        status: 'Interview Scheduled',
        percentage: 94,
        icon: Code,
        actionIcon: Bell,
    },
    {
        id: 'm3',
        title: 'Full-Stack Developer',
        company: 'CodeSphere Inc.',
        dateType: 'Submission Date',
        dateValue: 'February 28, 2025',
        status: 'Rejected',
        percentage: 0,
        icon: BrainCircuit,
        actionIcon: RefreshCw,
    },
    {
        id: 'm4',
        title: 'DevOps Engineer',
        company: 'CloudSync Technologies',
        dateType: 'Submission Date',
        dateValue: 'March 3, 2025',
        status: 'Viewed',
        percentage: 15,
        icon: RefreshCw,
        actionIcon: RefreshCw,
    },
    {
        id: 'm5',
        title: 'Cybersecurity Analyst',
        company: 'SecureNet Solutions',
        dateType: 'Interview Date',
        dateValue: 'March 12, 2025',
        status: 'Interview Scheduled',
        percentage: 94,
        icon: Shield,
        actionIcon: RefreshCw,
    },
    {
        id: 'm6',
        title: 'AI/ML Engineer',
        company: 'NeuralTech AI',
        dateType: 'Submission Date',
        dateValue: 'March 6, 2025',
        status: 'Pending Review',
        percentage: 76,
        icon: BrainCircuit,
        actionIcon: RefreshCw,
    },
    {
        id: 'm7',
        title: 'Web Developer (PHP Specialization)',
        company: 'WebFlex Digital',
        dateType: 'Submission Date',
        dateValue: 'February 27, 2025',
        status: 'Rejected',
        percentage: 0,
        icon: FileCode, // Using file code to represent php in this mock context
        actionIcon: RefreshCw,
    },
    {
        id: 'm8',
        title: 'Cloud Engineer',
        company: 'SkyTech Cloud Services',
        dateType: 'Submission Date',
        dateValue: 'March 4, 2025',
        status: 'Shortlisted',
        percentage: 87,
        icon: Cloud,
        actionIcon: RefreshCw,
    },
];

// Combine real and mock
const allApplications = computed(() => {
    const realApps = (props.applications || []).map((app) => {
        // Map real application to same structure
        let statusText = 'Applied';
        if (app.status === 'interview_scheduled')
            statusText = 'Interview Scheduled';
        else if (app.status === 'rejected') statusText = 'Rejected';
        else if (app.status === 'offer') statusText = 'Offer';
        else if (app.status === 'hired') statusText = 'Hired';

        return {
            id: app.id,
            title: app.work_job?.title || 'Unknown Job',
            company: app.work_job?.company || 'Unknown Company',
            dateType: 'Submission Date',
            dateValue: new Date(app.created_at).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            }),
            status: statusText,
            percentage: 50, // default dummy
            icon: Code,
            actionIcon: RefreshCw,
        };
    });

    return [...realApps, ...mockApplications];
});
</script>

<template>
    <Head title="Request Tracker" />

    <div
        class="container mx-auto px-5 py-8 font-sans overflow-x-auto"
    >
        <!-- Header Controls -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <h1
                class="mr-2 shrink-0 text-xl font-bold tracking-tight text-slate-900"
            >
                Request tracker
            </h1>

            <div class="relative w-64 max-w-full">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                />
                <Input
                    type="search"
                    placeholder="Search..."
                    class="h-10 rounded-full border-slate-200 bg-slate-50 pl-9 text-sm shadow-sm"
                />
            </div>

            <Button
                variant="outline"
                size="icon"
                class="h-10 w-10 shrink-0 rounded-full border-0 bg-primary text-white shadow-sm hover:bg-primary/90 hover:text-white"
            >
                <Filter class="h-4 w-4" />
            </Button>

            <Button
                variant="outline"
                size="icon"
                class="h-10 w-10 shrink-0 rounded-full border-0 bg-primary text-white shadow-sm hover:bg-primary/90 hover:text-white"
            >
                <SlidersHorizontal class="h-4 w-4" />
            </Button>

            <Button
                class="ml-2 h-10 shrink-0 rounded-full bg-primary px-6 font-semibold text-white shadow-sm hover:bg-primary/90"
            >
                AI INTERVIEW PREP
            </Button>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <!-- Left Column: Tracker List -->
            <div class="flex-1 space-y-3">
                <Card
                    v-for="app in allApplications"
                    :key="app.id"
                    class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm"
                >
                    <CardContent
                        class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <!-- Icon -->
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-700 shadow-sm"
                            >
                                <component :is="app.icon" class="h-6 w-6" />
                            </div>

                            <!-- Job Info -->
                            <div class="min-w-0 flex-1">
                                <div
                                    class="truncate text-[15px] font-bold text-slate-900"
                                >
                                    {{ app.title }}
                                    <span
                                        class="font-normal text-slate-600 italic"
                                        >– {{ app.company }}</span
                                    >
                                </div>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    {{ app.dateType }}: {{ app.dateValue }}
                                </div>
                            </div>
                        </div>

                        <!-- Status & Actions -->
                        <div class="flex w-full shrink-0 items-center gap-3 sm:w-auto sm:pl-4">
                            <!-- Badge -->
                            <Badge
                                class="flex flex-1 justify-center rounded-full bg-primary px-4 py-1 text-xs font-semibold text-white shadow-sm hover:bg-primary/90 sm:flex-none sm:w-[160px]"
                            >
                                {{ app.status }}
                            </Badge>

                            <!-- Progress Circle -->
                            <div
                                class="relative flex h-[42px] w-[42px] shrink-0 items-center justify-center"
                            >
                                <svg
                                    class="h-full w-full -rotate-90 transform"
                                    viewBox="0 0 36 36"
                                >
                                    <path
                                        class="text-slate-100"
                                        stroke-width="3"
                                        stroke="currentColor"
                                        fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path
                                        class="text-slate-900"
                                        stroke-width="3"
                                        :stroke-dasharray="`${app.percentage}, 100`"
                                        stroke-linecap="round"
                                        stroke="currentColor"
                                        fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                </svg>
                                <span
                                    class="absolute text-[11px] font-bold text-slate-900"
                                    >{{ app.percentage }}%</span
                                >
                            </div>

                            <!-- Action Buttons -->
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-[42px] w-[42px] shrink-0 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90 hover:text-white"
                            >
                                <component
                                    :is="app.actionIcon"
                                    class="h-4 w-4"
                                />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-[42px] w-[42px] shrink-0 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90 hover:text-white"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Charts -->
            <div class="w-full shrink-0 space-y-4 lg:w-[380px]">
                <!-- Chart 1: Bar Chart -->
                <Card
                    class="relative overflow-hidden rounded-[24px] border border-slate-200/60 bg-[#f1f5f9] shadow-sm"
                >
                    <CardContent class="p-6">
                        <div class="mb-8 flex items-center justify-between">
                            <h3
                                class="max-w-[200px] text-[15px] leading-snug font-bold text-slate-900"
                            >
                                Chart 1: "Application Outcomes Overview"
                            </h3>
                        </div>

                        <div
                            class="absolute top-5 right-5 flex items-center gap-1.5"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90"
                            >
                                <Clock class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90"
                            >
                                <MoreVertical class="h-3.5 w-3.5" />
                            </Button>
                        </div>

                        <!-- Mock Bar Chart Wrapper -->
                        <div
                            class="relative mt-12 h-44 rounded-xl border border-slate-100 bg-white p-4 shadow-sm"
                        >
                            <!-- Y-axis labels -->
                            <div
                                class="absolute top-4 bottom-10 left-4 flex flex-col justify-between text-[10px] font-medium text-slate-400"
                            >
                                <span>100%</span>
                                <span>80%</span>
                                <span>60%</span>
                                <span>40%</span>
                                <span>20%</span>
                                <span>0%</span>
                            </div>

                            <!-- Grid lines -->
                            <div
                                class="pointer-events-none absolute top-6 right-4 bottom-10 left-12 flex flex-col justify-between"
                            >
                                <div
                                    class="w-full border-t border-dashed border-slate-100"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100"
                                ></div>
                                <div
                                    class="w-full border-t border-slate-200"
                                ></div>
                            </div>

                            <!-- Bars Area -->
                            <div
                                class="absolute top-6 right-8 bottom-8 left-16 flex items-end justify-between"
                            >
                                <!-- Bar 1 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 h-[40%] w-8 rounded-t-md bg-slate-200"
                                    ></div>
                                    <span
                                        class="absolute -bottom-8 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600"
                                        >Interview<br />Scheduled</span
                                    >
                                </div>
                                <!-- Bar 2 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 h-[75%] w-8 rounded-t-md bg-primary shadow-sm"
                                    ></div>
                                    <span
                                        class="absolute -bottom-6 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600"
                                        >Rejected</span
                                    >
                                </div>
                                <!-- Bar 3 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 h-[20%] w-8 rounded-t-md bg-slate-200"
                                    ></div>
                                    <span
                                        class="absolute -bottom-6 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600"
                                        >Shortlisted</span
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Chart 2: Pie Chart -->
                <Card
                    class="relative overflow-hidden rounded-[24px] border border-slate-200/60 bg-[#f1f5f9] shadow-sm"
                >
                    <CardContent class="p-6">
                        <div class="mb-8 flex items-center justify-between">
                            <h3
                                class="max-w-[200px] text-[15px] leading-snug font-bold text-slate-900"
                            >
                                Chart 2: "Percentage of Viewed Applications"
                            </h3>
                        </div>

                        <div
                            class="absolute top-5 right-5 flex items-center gap-1.5"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90"
                            >
                                <Clock class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-white shadow-sm hover:bg-primary/90"
                            >
                                <MoreVertical class="h-3.5 w-3.5" />
                            </Button>
                        </div>

                        <!-- Mock Pie Chart -->
                        <div
                            class="mt-4 flex flex-col items-center rounded-xl border border-slate-100 bg-white p-6 shadow-sm"
                        >
                            <!-- Pie Chart Circle -->
                            <div
                                class="relative flex h-48 w-48 items-center justify-center overflow-hidden rounded-full shadow-inner"
                                style="
                                    background: conic-gradient(
                                        from 0deg,
                                        var(--primary) 0% 70%,
                                        white 70% 100%
                                    );
                                "
                            >
                                <!-- Inner shadow overlay for smooth edges -->
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-full ring-1 ring-slate-900/10 ring-inset"
                                ></div>

                                <!-- Labels inside pie -->
                                <div
                                    class="absolute top-[35%] left-[25%] z-10 text-[15px] font-bold text-slate-800"
                                >
                                    30%
                                </div>
                                <div
                                    class="absolute right-[30%] bottom-[30%] z-10 text-[15px] font-bold text-white"
                                >
                                    70%
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="mt-8 w-full max-w-[220px] space-y-3">
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-4 w-8 rounded-[4px] border border-slate-200 bg-white"
                                        ></div>
                                        <span class="font-bold text-slate-900"
                                            >Viewed</span
                                        >
                                    </div>
                                    <span class="text-xs text-slate-500"
                                        >3 applications</span
                                    >
                                </div>
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-4 w-8 rounded-[4px] bg-primary"
                                        ></div>
                                        <span class="font-bold text-slate-900"
                                            >Other</span
                                        >
                                    </div>
                                    <span class="text-xs text-slate-500"
                                        >7 applications</span
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
