<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, KanbanSquare, List, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type {
    InterviewSession,
    UserWorkJobApplication,
} from '@/types/laravel-models';
import { requestTracker } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';

type Stage =
    | 'Applied'
    | 'Viewed'
    | 'Shortlisted'
    | 'Interview'
    | 'Offer'
    | 'Rejected';
type TrackerApplication = UserWorkJobApplication & { tracker_stage: Stage };

const props = defineProps<{
    applications: TrackerApplication[];
    funnel: {
        applied: number;
        viewed: number;
        interview: number;
        offer: number;
    };
}>();

const view = ref<'list' | 'board'>('list');
const stages: Stage[] = [
    'Applied',
    'Viewed',
    'Shortlisted',
    'Interview',
    'Offer',
    'Rejected',
];
const board = computed(
    () =>
        Object.fromEntries(
            stages.map((stage) => [
                stage,
                props.applications.filter(
                    (application) => application.tracker_stage === stage,
                ),
            ]),
        ) as Record<Stage, TrackerApplication[]>,
);

const interviewFor = (
    application: TrackerApplication,
): InterviewSession | null => {
    const interview = application.interview_session;

    return interview?.status === 'scheduled' &&
        interview.scheduled_at &&
        !interview.cancelled_at
        ? interview
        : null;
};

const interviewDate = (interview: InterviewSession) =>
    new Intl.DateTimeFormat('en-US', {
        timeZone: interview.timezone || 'UTC',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(interview.scheduled_at!));

const interviewTime = (interview: InterviewSession) =>
    new Intl.DateTimeFormat('en-US', {
        timeZone: interview.timezone || 'UTC',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(interview.scheduled_at!));

const submissionDate = (application: TrackerApplication) =>
    new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(application.created_at!));

const statusTone: Record<Stage, string> = {
    Applied: 'bg-slate-100 text-slate-800',
    Viewed: 'bg-blue-100 text-blue-800',
    Shortlisted: 'bg-violet-100 text-violet-800',
    Interview: 'bg-amber-100 text-amber-900',
    Offer: 'bg-emerald-100 text-emerald-800',
    Rejected: 'bg-rose-100 text-rose-800',
};

const openJob = (application: TrackerApplication) =>
    router.visit(jobSelectionShow.url(application.work_job_id));
const openInterview = (interview: InterviewSession) =>
    router.visit(`/interview-sessions/${interview.id}`);
const prepare = () => router.visit('/interview-preparation');

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Application Tracker', href: requestTracker() }],
    },
});
</script>

<template>
    <Head title="Application Tracker" />

    <div class="mx-auto w-full max-w-7xl space-y-6 px-5 py-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-950 dark:text-white">
                    Application Tracker
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Real applications and employer-scheduled interviews.
                </p>
            </div>
            <div
                class="flex rounded-lg border border-slate-200 p-1 dark:border-slate-700"
            >
                <button
                    type="button"
                    class="view-button"
                    :class="view === 'list' && 'view-button-active'"
                    @click="view = 'list'"
                >
                    <List class="h-4 w-4" />List
                </button>
                <button
                    type="button"
                    class="view-button"
                    :class="view === 'board' && 'view-button-active'"
                    @click="view = 'board'"
                >
                    <KanbanSquare class="h-4 w-4" />Board
                </button>
            </div>
        </header>

        <section
            class="grid gap-3 sm:grid-cols-4"
            aria-label="Application Funnel"
        >
            <Card
                v-for="item in [
                    { label: 'Applied', count: funnel.applied },
                    { label: 'Viewed', count: funnel.viewed },
                    { label: 'Interview', count: funnel.interview },
                    { label: 'Offer', count: funnel.offer },
                ]"
                :key="item.label"
                class="border-slate-200/70 shadow-sm dark:border-slate-800"
            >
                <CardContent class="p-4">
                    <p class="text-sm font-semibold text-slate-500">
                        {{ item.label }}
                    </p>
                    <p class="mt-1 text-2xl font-black">{{ item.count }}</p>
                </CardContent>
            </Card>
        </section>

        <div
            v-if="applications.length === 0"
            class="rounded-2xl border border-dashed py-16 text-center text-slate-500"
        >
            Your real job applications will appear here.
        </div>

        <section v-else-if="view === 'list'" class="space-y-4">
            <Card
                v-for="application in applications"
                :key="application.id"
                class="border-slate-200/70 shadow-sm dark:border-slate-800"
            >
                <CardContent class="p-5">
                    <div
                        class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start"
                    >
                        <div>
                            <button
                                type="button"
                                class="text-left text-lg font-bold hover:text-primary"
                                @click="openJob(application)"
                            >
                                {{ application.work_job?.title || 'Vacancy' }}
                            </button>
                            <p
                                class="font-medium text-slate-600 dark:text-slate-300"
                            >
                                {{ application.work_job?.company || 'Company' }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Submission Date:
                                {{ submissionDate(application) }}
                            </p>
                        </div>
                        <Badge
                            class="border-0 px-3 py-1.5"
                            :class="statusTone[application.tracker_stage]"
                        >
                            {{
                                application.tracker_stage === 'Interview'
                                    ? 'Interview Scheduled'
                                    : application.tracker_stage
                            }}
                        </Badge>
                    </div>

                    <div
                        v-if="interviewFor(application)"
                        class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20"
                    >
                        <div class="grid gap-3 text-sm sm:grid-cols-3">
                            <div>
                                <span
                                    class="block text-xs font-bold text-slate-500"
                                    >Interview Date</span
                                >{{ interviewDate(interviewFor(application)!) }}
                            </div>
                            <div>
                                <span
                                    class="block text-xs font-bold text-slate-500"
                                    >Interview Time</span
                                >{{ interviewTime(interviewFor(application)!) }}
                            </div>
                            <div>
                                <span
                                    class="block text-xs font-bold text-slate-500"
                                    >Timezone</span
                                >{{
                                    interviewFor(application)!.timezone || 'UTC'
                                }}
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="
                                    openInterview(interviewFor(application)!)
                                "
                            >
                                <CalendarDays class="mr-2 h-4 w-4" />Interview
                                Details
                            </Button>
                            <Button size="sm" @click="prepare"
                                ><Sparkles class="mr-2 h-4 w-4" />Prepare with
                                AI</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section
            v-else
            class="grid min-w-max grid-cols-6 gap-4 overflow-x-auto pb-4"
        >
            <div
                v-for="stage in stages"
                :key="stage"
                class="w-64 rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold">{{ stage }}</h2>
                    <Badge variant="secondary">{{ board[stage].length }}</Badge>
                </div>
                <div class="space-y-3">
                    <Card
                        v-for="application in board[stage]"
                        :key="application.id"
                        class="cursor-pointer"
                        @click="openJob(application)"
                    >
                        <CardContent class="p-4">
                            <h3 class="font-bold">
                                {{ application.work_job?.title || 'Vacancy' }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ application.work_job?.company || 'Company' }}
                            </p>
                            <p
                                v-if="interviewFor(application)"
                                class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300"
                            >
                                {{
                                    interviewDate(interviewFor(application)!)
                                }}
                                ·
                                {{ interviewTime(interviewFor(application)!) }}
                            </p>
                        </CardContent>
                    </Card>
                    <p
                        v-if="board[stage].length === 0"
                        class="py-5 text-center text-xs text-slate-400"
                    >
                        No applications
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.view-button {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgb(71 85 105);
}

.view-button-active {
    background: var(--primary);
    color: var(--primary-foreground);
}
</style>

