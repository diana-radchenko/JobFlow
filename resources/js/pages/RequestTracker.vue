<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Clock,
    Code,
    Filter,
    MoreVertical,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type {
    UserWorkJobApplication,
    ApplicationStatus,
    InterviewSession,
} from '@/types/laravel-models';
import { destroy as destroyApplication } from '@/actions/App/Http/Controllers/RequestTrackerController';
import { requestTracker, interviewPreparation } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';

const visitJob = (app: any) => {
    if (app.jobId) {
        router.visit(jobSelectionShow(app.jobId).url);
    }
};

type TrackerRow = {
    id: number | string;
    title: string;
    company: string;
    dateType: string;
    dateValue: string;
    status: string;
    rawStatus: ApplicationStatus;
    icon: Component;
    interview: InterviewSession | null;
};

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
}>();

const formatInterview = (date: string, timezone: string | null) =>
    new Intl.DateTimeFormat(undefined, {
        timeZone: timezone ?? 'UTC',
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));

const removingApplicationId = ref<number | null>(null);
const deleteDialogOpen = ref(false);
const applicationPendingDelete = ref<TrackerRow | null>(null);

function openDeleteDialog(app: TrackerRow): void {
    if (typeof app.id !== 'number') {
        return;
    }

    applicationPendingDelete.value = app;
    deleteDialogOpen.value = true;
}

function onDeleteDialogOpen(open: boolean): void {
    deleteDialogOpen.value = open;

    if (!open) {
        applicationPendingDelete.value = null;
    }
}

function confirmDelete(): void {
    const app = applicationPendingDelete.value;

    if (!app || typeof app.id !== 'number') {
        deleteDialogOpen.value = false;

        return;
    }

    deleteDialogOpen.value = false;
    applicationPendingDelete.value = null;
    removingApplicationId.value = app.id;
    router.delete(destroyApplication.url(app.id), {
        preserveScroll: true,
        onFinish: () => {
            removingApplicationId.value = null;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Request Tracker (Update)',
                href: requestTracker(),
            },
        ],
    },
});

const allApplications = computed(() =>
    (props.applications ?? []).map((app) => ({
        id: app.id,
        jobId: app.work_job_id,
        title: app.work_job?.title || 'Unknown Job',
        company: app.work_job?.company || 'Unknown Company',
        dateType: 'Submission Date',
        dateValue: new Date(app.created_at || '').toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        }),
        status: stringForHuman(app.status),
        rawStatus: app.status,
        icon: Code,
        interview: app.interview_session ?? null,
    })),
);

const share = (count: number, total: number): number =>
    total === 0 ? 0 : Math.round((count / total) * 100);

/** Chart 1: share of applications that reached each outcome. */
const outcomes = computed(() => {
    const apps = props.applications ?? [];
    const percentageWithStatus = (status: string) =>
        share(apps.filter((app) => app.status === status).length, apps.length);

    return {
        interviewScheduled: percentageWithStatus('interview_scheduled'),
        rejected: percentageWithStatus('rejected'),
        offer: percentageWithStatus('offer'),
    };
});

/** Chart 2: how many applications an employer has actually opened. */
const viewedStats = computed(() => {
    const apps = props.applications ?? [];
    const viewed = apps.filter((app) => app.viewed_at !== null).length;

    return {
        viewed,
        notViewed: apps.length - viewed,
        viewedPercentage: share(viewed, apps.length),
        notViewedPercentage: share(apps.length - viewed, apps.length),
    };
});
</script>

<template>
    <Head title="Request Tracker (Update)" />

    <Dialog :open="deleteDialogOpen" @update:open="onDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Are you sure you want to delete?</DialogTitle>
                <DialogDescription>
                    <span v-if="applicationPendingDelete">
                        This will remove
                        <span class="font-medium text-foreground">
                            {{ applicationPendingDelete.title }}
                        </span>
                        at
                        <span class="font-medium text-foreground">
                            {{ applicationPendingDelete.company }}
                        </span>
                        from your tracker. This cannot be undone.
                    </span>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:justify-end">
                <DialogClose as-child>
                    <Button variant="outline" type="button">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    type="button"
                    :disabled="removingApplicationId !== null"
                    @click="confirmDelete"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <div class="container mx-auto overflow-x-auto px-5 py-8 font-sans">
        <!-- Header Controls -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <h1
                class="mr-2 shrink-0 text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50"
            >
                Request tracker (Update)
            </h1>

            <div class="relative w-64 max-w-full">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
                />
                <Input
                    type="search"
                    placeholder="Search..."
                    class="h-10 rounded-full border-slate-200 bg-slate-50 pl-9 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
            </div>

            <Button
                variant="outline"
                size="icon"
                class="h-10 w-10 rounded-full shadow-sm"
            >
                <Filter class="h-4 w-4" />
            </Button>

            <Button
                variant="outline"
                size="icon"
                class="h-10 w-10 rounded-full shadow-sm"
            >
                <SlidersHorizontal class="h-4 w-4" />
            </Button>

            <Button
                class="ml-2 h-10 shrink-0 rounded-full bg-primary px-6 font-semibold text-primary-foreground shadow-sm hover:bg-primary/90"
                @click="router.visit(interviewPreparation().url)"
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
                    @click="visitJob(app)"
                    :class="
                        app.jobId
                            ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800'
                            : ''
                    "
                    class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900"
                >
                    <CardContent
                        class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <!-- Icon -->
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                <component :is="app.icon" class="h-6 w-6" />
                            </div>

                            <!-- Job Info -->
                            <div class="min-w-0 flex-1">
                                <div
                                    class="truncate text-[15px] font-bold text-slate-900 dark:text-slate-100"
                                >
                                    {{ app.title }}
                                    <span
                                        class="font-normal text-slate-600 italic dark:text-slate-400"
                                        >– {{ app.company }}</span
                                    >
                                </div>
                                <div
                                    class="mt-0.5 text-sm text-slate-500 dark:text-slate-400"
                                >
                                    {{ app.dateType }}: {{ app.dateValue }}
                                </div>
                                <div
                                    v-if="app.interview?.scheduled_at"
                                    class="mt-2 rounded-lg bg-emerald-50 p-2 text-xs font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                                >
                                    Interview:
                                    {{
                                        formatInterview(
                                            app.interview.scheduled_at,
                                            app.interview.timezone,
                                        )
                                    }}
                                    · {{ app.interview.timezone }} ·
                                    {{ app.interview.status }}
                                </div>
                            </div>
                        </div>

                        <!-- Status & Actions -->
                        <div
                            class="flex w-full shrink-0 items-center gap-3 sm:w-auto sm:pl-4"
                        >
                            <!-- Badge -->
                            <Badge
                                :class="[
                                    'flex flex-1 justify-center rounded-full px-4 py-1 text-xs font-semibold shadow-sm sm:w-[160px] sm:flex-none',
                                    getApplicationStatusColor(app.rawStatus),
                                ]"
                            >
                                {{ app.status }}
                            </Badge>

                            <!-- Progress Circle -->
                            <!--         <div
                                class="relative flex h-[42px] w-[42px] shrink-0 items-center justify-center"
                            >
                                <svg
                                    class="h-full w-full -rotate-90 transform"
                                    viewBox="0 0 36 36"
                                >
                                    <path
                                        class="text-slate-100 dark:text-slate-800"
                                        stroke-width="3"
                                        stroke="currentColor"
                                        fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path
                                        class="text-slate-900 dark:text-slate-100"
                                        stroke-width="3"
                                        :stroke-dasharray="`${app.percentage}, 100`"
                                        stroke-linecap="round"
                                        stroke="currentColor"
                                        fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                </svg>
                                <span
                                    class="absolute text-[11px] font-bold text-slate-900 dark:text-slate-100"
                                    >{{ app.percentage }}%</span
                                >
                            </div> -->

                            <!-- Action Buttons -->
                            <!-- <Button
                                variant="ghost"
                                size="icon"
                                class="h-[42px] w-[42px] shrink-0 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground dark:hover:bg-primary/20 dark:hover:text-primary"
                            >
                                <component
                                    :is="app.actionIcon"
                                    class="h-4 w-4"
                                />
                            </Button>-->
                            <Button
                                v-if="typeof app.id === 'number'"
                                variant="ghost"
                                size="icon"
                                type="button"
                                :disabled="removingApplicationId === app.id"
                                class="h-[42px] w-[42px] shrink-0 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground disabled:opacity-50 dark:hover:bg-primary/20 dark:hover:text-primary"
                                @click.stop="openDeleteDialog(app)"
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
                    class="relative overflow-hidden rounded-[24px] border border-slate-200/60 bg-[#f1f5f9] shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <CardContent class="p-6">
                        <div class="mb-8 flex items-center justify-between">
                            <h3
                                class="max-w-[200px] text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
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
                                class="h-8 w-8 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground dark:hover:bg-primary/20 dark:hover:text-primary"
                            >
                                <Clock class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground dark:hover:bg-primary/20 dark:hover:text-primary"
                            >
                                <MoreVertical class="h-3.5 w-3.5" />
                            </Button>
                        </div>

                        <!-- Mock Bar Chart Wrapper -->
                        <div
                            class="relative mt-12 h-44 rounded-xl border border-slate-100 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <!-- Y-axis labels -->
                            <div
                                class="absolute top-4 bottom-10 left-4 flex flex-col justify-between text-[10px] font-medium text-slate-400 dark:text-slate-500"
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
                                    class="w-full border-t border-dashed border-slate-100 dark:border-slate-800"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100 dark:border-slate-800"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100 dark:border-slate-800"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100 dark:border-slate-800"
                                ></div>
                                <div
                                    class="w-full border-t border-dashed border-slate-100 dark:border-slate-800"
                                ></div>
                                <div
                                    class="w-full border-t border-slate-200 dark:border-slate-700"
                                ></div>
                            </div>

                            <!-- Bars Area -->
                            <div
                                class="absolute top-6 right-8 bottom-10 left-16 flex items-end justify-between"
                            >
                                <!-- Bar 1 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 w-8 rounded-t-md bg-slate-200 dark:bg-slate-700"
                                        :style="{
                                            height: `${outcomes.interviewScheduled}%`,
                                        }"
                                    ></div>
                                    <span
                                        class="absolute -bottom-8 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600 dark:text-slate-400"
                                        >Interview<br />Scheduled</span
                                    >
                                </div>
                                <!-- Bar 2 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 w-8 rounded-t-md bg-primary shadow-sm"
                                        :style="{
                                            height: `${outcomes.rejected}%`,
                                        }"
                                    ></div>
                                    <span
                                        class="absolute -bottom-6 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600 dark:text-slate-400"
                                        >Rejected</span
                                    >
                                </div>
                                <!-- Bar 3 -->
                                <div
                                    class="relative flex h-full w-12 flex-col items-center justify-end"
                                >
                                    <div
                                        class="relative z-10 w-8 rounded-t-md bg-slate-200 dark:bg-slate-700"
                                        :style="{
                                            height: `${outcomes.offer}%`,
                                        }"
                                    ></div>
                                    <span
                                        class="absolute -bottom-6 mt-2 w-16 text-center text-[10px] leading-tight text-slate-600 dark:text-slate-400"
                                        >Offer</span
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Chart 2: Pie Chart -->
                <Card
                    class="relative overflow-hidden rounded-[24px] border border-slate-200/60 bg-[#f1f5f9] shadow-sm dark:border-slate-800 dark:bg-slate-900"
                >
                    <CardContent class="p-6">
                        <div class="mb-8 flex items-center justify-between">
                            <h3
                                class="max-w-[200px] text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
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
                                class="h-8 w-8 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground dark:hover:bg-primary/20 dark:hover:text-primary"
                            >
                                <Clock class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground dark:hover:bg-primary/20 dark:hover:text-primary"
                            >
                                <MoreVertical class="h-3.5 w-3.5" />
                            </Button>
                        </div>

                        <div
                            class="mt-4 flex flex-col items-center rounded-xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                        >
                            <!-- Pie Chart Circle -->
                            <div
                                class="relative flex h-48 w-48 items-center justify-center overflow-hidden rounded-full bg-slate-100 shadow-inner dark:bg-slate-800"
                                :style="{
                                    backgroundImage: `conic-gradient(from 0deg, var(--primary) 0% ${viewedStats.viewedPercentage}%, transparent ${viewedStats.viewedPercentage}% 100%)`,
                                }"
                            >
                                <!-- Inner shadow overlay for smooth edges -->
                                <div
                                    class="pointer-events-none absolute inset-0 rounded-full ring-1 ring-slate-900/10 ring-inset dark:ring-white/10"
                                ></div>

                                <!-- Labels inside pie -->
                                <div
                                    class="absolute top-[35%] left-[25%] z-10 text-[15px] font-bold text-slate-800 dark:text-slate-200"
                                >
                                    {{ viewedStats.notViewedPercentage }}%
                                </div>
                                <div
                                    class="absolute right-[30%] bottom-[30%] z-10 text-[15px] font-bold text-primary-foreground"
                                >
                                    {{ viewedStats.viewedPercentage }}%
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="mt-8 w-full max-w-[220px] space-y-3">
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-4 w-8 rounded-[4px] bg-primary"
                                        ></div>
                                        <span
                                            class="font-bold text-slate-900 dark:text-slate-100"
                                            >Viewed</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                        >{{
                                            viewedStats.viewed
                                        }}
                                        applications</span
                                    >
                                </div>
                                <div
                                    class="flex items-center justify-between text-sm"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-4 w-8 rounded-[4px] border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                                        ></div>
                                        <span
                                            class="font-bold text-slate-900 dark:text-slate-100"
                                            >Not viewed</span
                                        >
                                    </div>
                                    <span
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                        >{{
                                            viewedStats.notViewed
                                        }}
                                        applications</span
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
