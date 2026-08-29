<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Bot,
    Sparkles,
    Heart,
    ChevronLeft,
    ChevronRight,
    BriefcaseBusiness,
    CalendarDays,
    Clock3,
    FileCheck2,
    Target,
    Video,
    ArrowRight,
    Activity,
    CheckCircle2,
    Circle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import { stringForHuman } from '@/helpers/strings';
import type {
    InterviewSession,
    UserWorkJobApplication,
    WorkJob,
} from '@/types/laravel-models';
import { show as interviewSessionShow } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { dashboard } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';

interface DashboardResume {
    id: number;
    title: string;
}

interface DashboardSummary {
    applications: number;
    interviews: number;
    offers: number;
    resumeCompleteness: number | null;
    recommendedMatches: number;
    jobSearchProgress: number;
}

interface ResumeChecklistItem {
    label: string;
    complete: boolean;
}

interface SelectedResumeSummary {
    title: string;
    completeness: number;
    checklist: ResumeChecklistItem[];
    href: string;
}

interface JobSearchMilestone {
    label: string;
    weight: number;
    complete: boolean;
}

interface DashboardArticle {
    id: string;
    image: string;
    fallback_image: string;
    category: string;
    title: string;
    description?: string;
    reading_time: string;
    url: string;
    source: string;
}

interface RecommendationCriterion {
    label: string;
    score: number;
    matches?: string[];
}

interface RecommendationPayload {
    job: WorkJob;
    score: number;
    criteria: RecommendationCriterion[];
    strong_matches: string[];
    gaps: string[];
    applied: boolean;
    saved: boolean;
}

interface NextStep {
    title: string;
    description: string;
    href: string;
    action: string;
}

interface RecentActivity {
    event: string;
    company: string;
    vacancy: string;
    occurred_at: string;
}

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
    interviewSessions: InterviewSession[] | null;
    nextInterview: InterviewSession | null;
    dashboardSummary: DashboardSummary;
    profileFirstName: string;
    resumes: DashboardResume[];
    selectedResumeId: number | null;
    selectedResumeSummary: SelectedResumeSummary | null;
    jobSearchMilestones: JobSearchMilestone[];
    recommendedJobs: RecommendationPayload[];
    nextSteps: NextStep[];
    recentActivity: RecentActivity[];
    articles: DashboardArticle[];
}>();

const visitJob = (app: any) => {
    if (app.jobId) {
        router.visit(jobSelectionShow(app.jobId).url);
    }
};

const visitSession = (session: InterviewSession) => {
    router.visit(interviewSessionShow(session.id).url);
};

const visitRequestTracker = () => {
    router.visit('/request-tracker');
};

const prepareForInterview = () => {
    router.visit('/interview-preparation');
};

const activityTime = (value: string) =>
    new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));

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

const applicationStatus = (app: UserWorkJobApplication) => {
    const raw = String(app.status).toLowerCase();

    if (raw === 'applied' && app.viewed_at) {
        return {
            label: 'Viewed',
            tone: 'bg-blue-100 text-blue-800',
            icon: 'Seen',
        };
    }

    const statuses: Record<
        string,
        { label: string; tone: string; icon: string }
    > = {
        applied: {
            label: 'Applied',
            tone: 'bg-slate-100 text-slate-800',
            icon: 'Sent',
        },
        shortlisted: {
            label: 'Shortlisted',
            tone: 'bg-violet-100 text-violet-800',
            icon: 'Selected',
        },
        interview_scheduled: {
            label: 'Interview Scheduled',
            tone: 'bg-amber-100 text-amber-900',
            icon: 'Calendar',
        },
        rejected: {
            label: 'Rejected',
            tone: 'bg-rose-100 text-rose-800',
            icon: 'Closed',
        },
        offer: {
            label: 'Offer',
            tone: 'bg-emerald-100 text-emerald-800',
            icon: 'Offer',
        },
        hired: {
            label: 'Offer',
            tone: 'bg-emerald-100 text-emerald-800',
            icon: 'Offer',
        },
    };

    return (
        statuses[raw] ?? {
            label: stringForHuman(app.status),
            tone: 'bg-slate-100 text-slate-800',
            icon: 'Status',
        }
    );
};

const tableApplications = computed(() => {
    const realApps = (props.applications || []).map((app) => {
        const status = applicationStatus(app);

        return {
            id: app.id,
            jobId: app.work_job_id,
            title: app.work_job?.title || 'Unknown Job',
            company: app.work_job?.company || 'Unknown Company',
            salary: formatApplicationSalary(app.work_job),
            status: status.label,
            statusTone: status.tone,
            statusIcon: status.icon,
        };
    });

    return realApps.slice(0, 5);
});

const formatApplicationSalary = (job?: WorkJob | null) => {
    if (!job?.salary_start && !job?.salary_end) {
        return 'Not specified';
    }

    const currency = job.salary_currency || 'USD';
    const range = [job.salary_start, job.salary_end]
        .filter(Boolean)
        .map((value) => Number(value).toLocaleString())
        .join(' – ');

    return `${currency} ${range}${job.salary_period ? ` / ${stringForHuman(job.salary_period)}` : ''}`;
};

const DAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const startOfDay = (date: Date) => {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);

    return d;
};

const startOfWeek = (date: Date) => {
    const d = startOfDay(date);
    d.setDate(d.getDate() - d.getDay());

    return d;
};

const isSameDay = (a: Date, b: Date) =>
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate();

const localDateKey = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const sessionDateKey = (session: InterviewSession) => {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: session.timezone ?? 'UTC',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date(session.scheduled_at!));
    const value = Object.fromEntries(
        parts.map((part) => [part.type, part.value]),
    );

    return `${value.year}-${value.month}-${value.day}`;
};
const calendarDateForSession = (session: InterviewSession) => {
    const [year, month, day] = sessionDateKey(session).split('-').map(Number);

    return new Date(year, month - 1, day, 12);
};

const interviewDate = (session: InterviewSession) =>
    new Intl.DateTimeFormat('en-US', {
        timeZone: session.timezone ?? 'UTC',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(session.scheduled_at!));

const interviewTime = (session: InterviewSession) =>
    new Intl.DateTimeFormat('en-US', {
        timeZone: session.timezone ?? 'UTC',
        hour: 'numeric',
        minute: '2-digit',
        timeZoneName: 'short',
    }).format(new Date(session.scheduled_at!));

const interviewFormat = (session: InterviewSession) =>
    session.interview_format
        ? `${stringForHuman(session.interview_format)} interview`
        : 'Interview';

const isPastInterview = (session: InterviewSession) =>
    new Date(session.scheduled_at!).getTime() < Date.now();

const today = startOfDay(new Date());
const scheduledInterviews = [...(props.interviewSessions ?? [])].filter(
    (session) => session.scheduled_at,
);
const firstScheduledInterview =
    scheduledInterviews
        .filter(
            (session) =>
                new Date(session.scheduled_at!).getTime() >= Date.now(),
        )
        .sort(
            (a, b) =>
                new Date(a.scheduled_at!).getTime() -
                new Date(b.scheduled_at!).getTime(),
        )[0] ??
    scheduledInterviews.sort(
        (a, b) =>
            new Date(b.scheduled_at!).getTime() -
            new Date(a.scheduled_at!).getTime(),
    )[0];
const initialCalendarDate = firstScheduledInterview
    ? calendarDateForSession(firstScheduledInterview)
    : today;
const selectedDate = ref(initialCalendarDate);

type CalendarViewMode = 'week' | 'month' | 'year';
const calendarViewModes: CalendarViewMode[] = ['week', 'month', 'year'];
const viewMode = ref<CalendarViewMode>('week');
const anchorDate = ref(initialCalendarDate);

const weekStart = computed(() => startOfWeek(anchorDate.value));

const weekDays = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const date = new Date(weekStart.value);
        date.setDate(date.getDate() + i);

        return {
            date,
            day: DAY_LABELS[date.getDay()],
            dateLabel: String(date.getDate()),
            active: isSameDay(date, selectedDate.value),
            hasEvent: !!sessionsByDay.value.get(localDateKey(date))?.length,
        };
    }),
);

const monthStart = computed(() => {
    const d = new Date(anchorDate.value);
    d.setDate(1);
    d.setHours(0, 0, 0, 0);

    return d;
});

const monthDays = computed(() => {
    const start = monthStart.value;
    const lastDayOfMonth = new Date(
        start.getFullYear(),
        start.getMonth() + 1,
        0,
    );
    const gridStart = startOfWeek(start);
    const gridEnd = startOfWeek(lastDayOfMonth);
    gridEnd.setDate(gridEnd.getDate() + 6);

    const days = [];

    for (
        let date = new Date(gridStart);
        date <= gridEnd;
        date.setDate(date.getDate() + 1)
    ) {
        const day = new Date(date);

        days.push({
            date: day,
            dateLabel: String(day.getDate()),
            inCurrentMonth: day.getMonth() === start.getMonth(),
            active: isSameDay(day, selectedDate.value),
            hasEvent: !!sessionsByDay.value.get(localDateKey(day))?.length,
        });
    }

    return days;
});

const yearMonths = computed(() => {
    const year = anchorDate.value.getFullYear();

    return Array.from({ length: 12 }, (_, month) => {
        const date = new Date(year, month, 1);
        const hasEvent = Array.from(sessionsByDay.value.keys()).some((key) => {
            const eventDate = new Date(key);

            return (
                eventDate.getFullYear() === year &&
                eventDate.getMonth() === month
            );
        });

        return {
            date,
            label: date.toLocaleDateString('en-US', { month: 'short' }),
            active: today.getFullYear() === year && today.getMonth() === month,
            hasEvent,
        };
    });
});

const periodLabel = computed(() => {
    if (viewMode.value === 'month') {
        return monthStart.value.toLocaleDateString('en-US', {
            month: 'long',
            year: 'numeric',
        });
    }

    if (viewMode.value === 'year') {
        return String(anchorDate.value.getFullYear());
    }

    const start = weekDays.value[0].date;
    const end = weekDays.value[6].date;
    const startLabel = start.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
    const endLabel = end.toLocaleDateString(
        'en-US',
        start.getMonth() === end.getMonth()
            ? { day: 'numeric', year: 'numeric' }
            : { month: 'short', day: 'numeric', year: 'numeric' },
    );

    return `${startLabel} - ${endLabel}`;
});

const goToPrevious = () => {
    const date = new Date(anchorDate.value);

    if (viewMode.value === 'week') {
        date.setDate(date.getDate() - 7);
    } else if (viewMode.value === 'month') {
        date.setMonth(date.getMonth() - 1);
    } else {
        date.setFullYear(date.getFullYear() - 1);
    }

    anchorDate.value = date;
};

const goToNext = () => {
    const date = new Date(anchorDate.value);

    if (viewMode.value === 'week') {
        date.setDate(date.getDate() + 7);
    } else if (viewMode.value === 'month') {
        date.setMonth(date.getMonth() + 1);
    } else {
        date.setFullYear(date.getFullYear() + 1);
    }

    anchorDate.value = date;
};

const selectDay = (date: Date) => {
    selectedDate.value = date;
    anchorDate.value = date;
};

const selectMonth = (date: Date) => {
    anchorDate.value = date;
    viewMode.value = 'month';
};

const sessionsByDay = computed(() => {
    const map = new Map<string, InterviewSession[]>();

    for (const session of props.interviewSessions || []) {
        if (!session.scheduled_at) {
            continue;
        }

        const key = sessionDateKey(session);
        const bucket = map.get(key) ?? [];
        bucket.push(session);
        map.set(key, bucket);
    }

    for (const bucket of map.values()) {
        bucket.sort(
            (a, b) =>
                new Date(a.scheduled_at!).getTime() -
                new Date(b.scheduled_at!).getTime(),
        );
    }

    return map;
});

const timelineEvents = computed(() => {
    const sessions =
        sessionsByDay.value.get(localDateKey(selectedDate.value)) || [];

    return sessions.map((session) => {
        return {
            time: interviewTime(session),
            title: `${session.work_job?.company ?? 'Employer'} · ${session.work_job?.title ?? 'Interview'}`,
            duration: `${session.duration_minutes ?? 30} minutes · ${interviewFormat(session)}`,
            isPast: isPastInterview(session),
            session,
        };
    });
});

const interviewTooltip = (date: Date) =>
    (sessionsByDay.value.get(localDateKey(date)) ?? [])
        .map((session) => {
            return `Interview\n${interviewTime(session)}\n${session.work_job?.company ?? 'Employer'}\n${session.work_job?.title ?? 'Interview'}`;
        })
        .join('\n\n');

const recommendedJobs = computed(() =>
    props.recommendedJobs
        .slice(0, 2)
        .map(
            ({
                job,
                score,
                criteria,
                strong_matches,
                gaps,
                applied,
                saved,
            }) => ({
                id: job.id,
                url: jobSelectionShow(job.id).url,
                company: job.company,
                logoText: job.company.slice(0, 2).toUpperCase(),
                title: job.title,
                salary: job.salary_start
                    ? `$${Number(job.salary_start).toLocaleString()}`
                    : 'Salary not specified',
                tags: (job.technologies ?? []).map(String).slice(0, 3),
                recommendationScore: score,
                criteria,
                strongMatches: strong_matches,
                gaps,
                applied,
                saved,
                location: job.location,
                workplaceType: job.workplace_type,
            }),
        ),
);

type RecommendedJob = (typeof recommendedJobs.value)[number];

const selectRecommendationResume = (event: Event) => {
    const resumeId = Number((event.target as HTMLSelectElement).value);
    router.get(
        dashboard().url,
        { resume_id: resumeId },
        { preserveState: true, preserveScroll: true },
    );
};

const applyToRecommendedJob = (job: RecommendedJob) => {
    if (!props.selectedResumeId || job.applied) {
        return;
    }

    router.post(
        `/job-selection/${job.id}/apply`,
        { resume_id: props.selectedResumeId },
        { preserveScroll: true },
    );
};

const toggleSavedJob = (job: RecommendedJob) => {
    const options = { preserveScroll: true };

    if (job.saved) {
        router.delete(`/saved-jobs/${job.id}`, options);
    } else {
        router.post(`/saved-jobs/${job.id}`, {}, options);
    }
};

const recommendationsOpen = ref(false);
const recommendationsJob = ref<RecommendedJob | null>(null);
const showRecommendationExplanation = (job: RecommendedJob) => {
    recommendationsJob.value = job;
    recommendationsOpen.value = true;
};

const useArticleFallback = (event: Event, fallback: string) => {
    const image = event.currentTarget as HTMLImageElement;

    if (image.dataset.fallbackApplied) {
        return;
    }

    image.dataset.fallbackApplied = 'true';
    image.src = fallback;
};
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="min-h-full bg-slate-50/80 px-5 py-8 font-sans dark:bg-slate-950"
    >
        <div class="container mx-auto max-w-[1400px]">
            <div class="mb-4 flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800"
                >
                    <Bot class="h-6 w-6 text-slate-700 dark:text-slate-300" />
                </div>
                <h1
                    class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-slate-50"
                >
                    Welcome back, {{ props.profileFirstName }}!
                </h1>
            </div>

            <Card
                class="mb-6 border-[#0B315B] bg-[#061E3A] text-white shadow-sm"
            >
                <CardContent class="p-5">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-200">
                                Job Search Progress
                            </p>
                            <p class="mt-1 text-2xl font-extrabold">
                                {{ dashboardSummary.jobSearchProgress }}%
                            </p>
                        </div>
                        <a
                            href="#next-steps"
                            class="inline-flex items-center gap-1 text-sm font-bold text-sky-200 hover:text-white hover:underline"
                        >
                            View next steps <ArrowRight class="h-4 w-4" />
                        </a>
                    </div>
                    <div
                        class="mt-4 h-2 overflow-hidden rounded-full bg-white/15"
                        role="progressbar"
                        aria-label="Job search progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-valuenow="dashboardSummary.jobSearchProgress"
                    >
                        <div
                            class="h-full rounded-full bg-blue-400 transition-[width]"
                            :style="{
                                width: `${dashboardSummary.jobSearchProgress}%`,
                            }"
                        ></div>
                    </div>
                    <p class="mt-2 text-sm text-slate-300">
                        Based on
                        {{
                            jobSearchMilestones.filter((item) => item.complete)
                                .length
                        }}
                        of {{ jobSearchMilestones.length }} real job-search
                        milestones completed.
                    </p>
                </CardContent>
            </Card>

            <section
                aria-label="Job search summary"
                class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4"
            >
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="flex items-center gap-3 p-4">
                        <BriefcaseBusiness class="h-5 w-5 text-blue-600" />
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Applications
                            </p>
                            <p class="text-2xl font-extrabold">
                                {{ dashboardSummary.applications }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="flex items-center gap-3 p-4">
                        <CalendarDays class="h-5 w-5 text-amber-600" />
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Interviews
                            </p>
                            <p class="text-2xl font-extrabold">
                                {{ dashboardSummary.interviews }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="flex items-center gap-3 p-4">
                        <FileCheck2 class="h-5 w-5 text-emerald-600" />
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Resume completeness
                            </p>
                            <p class="text-2xl font-extrabold">
                                {{
                                    dashboardSummary.resumeCompleteness === null
                                        ? '—'
                                        : `${dashboardSummary.resumeCompleteness}%`
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="flex items-center gap-3 p-4">
                        <Target class="h-5 w-5 text-emerald-600" />
                        <div>
                            <p class="text-sm font-medium text-slate-500">
                                Offers
                            </p>
                            <p class="text-2xl font-extrabold">
                                {{ dashboardSummary.offers }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Card
                v-if="nextInterview"
                class="mb-6 overflow-hidden border-amber-200 bg-gradient-to-r from-amber-50 to-white shadow-sm dark:border-amber-900/60 dark:from-amber-950/30 dark:to-slate-950"
            >
                <CardContent
                    class="flex flex-col gap-5 p-5 xl:flex-row xl:items-center xl:justify-between"
                >
                    <div class="flex min-w-0 gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white"
                        >
                            <CalendarDays class="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-sm font-bold text-amber-800 dark:text-amber-300"
                            >
                                Next Interview
                            </p>
                            <h2
                                class="mt-1 truncate text-xl font-extrabold text-slate-950 dark:text-white"
                            >
                                {{
                                    nextInterview.work_job?.title ?? 'Interview'
                                }}
                            </h2>
                            <p
                                class="font-medium text-slate-600 dark:text-slate-300"
                            >
                                {{
                                    nextInterview.work_job?.company ??
                                    'Employer'
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="grid gap-2 text-sm text-slate-700 sm:grid-cols-3 dark:text-slate-200"
                    >
                        <span class="flex items-center gap-2"
                            ><CalendarDays class="h-4 w-4 text-amber-600" />{{
                                interviewDate(nextInterview)
                            }}</span
                        >
                        <span class="flex items-center gap-2"
                            ><Clock3 class="h-4 w-4 text-amber-600" />{{
                                interviewTime(nextInterview)
                            }}</span
                        >
                        <span class="flex items-center gap-2"
                            ><Video class="h-4 w-4 text-amber-600" />{{
                                interviewFormat(nextInterview)
                            }}</span
                        >
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <Button
                            variant="outline"
                            @click="visitSession(nextInterview)"
                            >View Details</Button
                        >
                        <Button @click="prepareForInterview"
                            ><Sparkles class="mr-2 h-4 w-4" />Prepare with
                            AI</Button
                        >
                    </div>
                </CardContent>
            </Card>

            <Card
                v-else
                class="mb-6 border-dashed border-amber-200 shadow-none dark:border-amber-900/60"
            >
                <CardContent
                    class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <CalendarDays class="h-8 w-8 text-amber-600" />
                        <div>
                            <h2 class="font-bold">No upcoming interviews</h2>
                            <p class="text-sm text-slate-500">
                                When an employer schedules an interview, it will
                                appear here automatically.
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" @click="prepareForInterview"
                        ><Sparkles class="mr-2 h-4 w-4" />Practice with
                        AI</Button
                    >
                </CardContent>
            </Card>

            <section class="mb-6 grid gap-6 lg:grid-cols-3">
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent id="next-steps" class="scroll-mt-6 p-5">
                        <h2 class="mb-4 text-lg font-bold">Your Next Steps</h2>
                        <div class="space-y-3">
                            <button
                                v-for="step in nextSteps"
                                :key="step.title"
                                type="button"
                                class="flex w-full items-center justify-between gap-4 rounded-xl border p-3 text-left transition hover:border-primary/50 hover:bg-slate-50 dark:hover:bg-slate-900"
                                @click="router.visit(step.href)"
                            >
                                <span
                                    ><strong class="block text-sm">{{
                                        step.title
                                    }}</strong
                                    ><span class="text-sm text-slate-500">{{
                                        step.description
                                    }}</span></span
                                >
                                <span
                                    class="flex shrink-0 items-center gap-1 text-xs font-bold text-primary"
                                    >{{ step.action
                                    }}<ArrowRight class="h-3.5 w-3.5"
                                /></span>
                            </button>
                        </div>
                    </CardContent>
                </Card>
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="p-5">
                        <div class="mb-4 flex items-center gap-2">
                            <FileCheck2 class="h-5 w-5 text-emerald-600" />
                            <h2 class="text-lg font-bold">Resume Status</h2>
                        </div>
                        <div v-if="selectedResumeSummary">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold">
                                        {{ selectedResumeSummary.title }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Resume Completeness:
                                        <strong
                                            >{{
                                                selectedResumeSummary.completeness
                                            }}%</strong
                                        >
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="item in selectedResumeSummary.checklist"
                                    :key="item.label"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <CheckCircle2
                                        v-if="item.complete"
                                        class="h-4 w-4 text-emerald-600"
                                    />
                                    <Circle
                                        v-else
                                        class="h-4 w-4 text-amber-600"
                                    />
                                    <span
                                        :class="
                                            !item.complete && 'font-semibold'
                                        "
                                    >
                                        {{
                                            item.complete
                                                ? item.label
                                                : item.label.replace(
                                                      ' added',
                                                      ' — add this',
                                                  )
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="
                                        router.visit(selectedResumeSummary.href)
                                    "
                                    >Open Resume</Button
                                >
                                <Button
                                    size="sm"
                                    @click="
                                        router.visit(selectedResumeSummary.href)
                                    "
                                    >Improve Resume</Button
                                >
                            </div>
                        </div>
                        <div
                            v-else
                            class="rounded-xl border border-dashed p-4 text-center"
                        >
                            <p class="font-semibold">No resume yet</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Create a resume to track completeness and unlock
                                job matching.
                            </p>
                            <Button
                                class="mt-3"
                                size="sm"
                                @click="router.visit('/resumes')"
                            >
                                Create Resume
                            </Button>
                        </div>
                    </CardContent>
                </Card>
                <Card
                    class="border-slate-200/70 shadow-sm dark:border-slate-800"
                >
                    <CardContent class="p-5">
                        <div class="mb-4 flex items-center gap-2">
                            <Activity class="h-5 w-5 text-primary" />
                            <h2 class="text-lg font-bold">Recent Activity</h2>
                        </div>
                        <div
                            v-if="recentActivity.length"
                            class="divide-y dark:divide-slate-800"
                        >
                            <div
                                v-for="item in recentActivity"
                                :key="`${item.event}-${item.occurred_at}`"
                                class="py-3 first:pt-0 last:pb-0"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <strong class="text-sm">{{
                                        item.event
                                    }}</strong
                                    ><time
                                        class="shrink-0 text-sm text-slate-500"
                                        >{{
                                            activityTime(item.occurred_at)
                                        }}</time
                                    >
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ item.company }} · {{ item.vacancy }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-else
                            class="rounded-xl border border-dashed p-5 text-center"
                        >
                            <p class="font-semibold">No activity yet</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Applications and employer updates will appear
                                here.
                            </p>
                            <Button
                                class="mt-3"
                                size="sm"
                                variant="outline"
                                @click="router.visit('/job-selection')"
                                >Browse Jobs</Button
                            >
                        </div>
                    </CardContent>
                </Card>
            </section>

            <div
                class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(300px,360px)_minmax(0,1fr)]"
            >
                <!-- Left Column: Schedule -->
                <div class="space-y-6">
                    <h2
                        class="text-xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        Schedule
                    </h2>

                    <Card
                        v-if="scheduledInterviews.length"
                        class="overflow-hidden rounded-[24px] border-0 bg-slate-50 shadow-sm dark:bg-slate-900"
                    >
                        <CardContent class="p-0">
                            <!-- View Mode Switch -->
                            <div
                                class="flex items-center justify-center gap-1 border-b border-slate-200/60 px-4 pt-4 pb-2 dark:border-slate-800"
                            >
                                <Button
                                    v-for="mode in calendarViewModes"
                                    :key="mode"
                                    type="button"
                                    size="sm"
                                    :variant="
                                        viewMode === mode ? 'default' : 'ghost'
                                    "
                                    class="rounded-full px-3 capitalize"
                                    @click="viewMode = mode"
                                >
                                    {{ mode }}
                                </Button>
                            </div>

                            <!-- Calendar Header -->
                            <div
                                class="flex items-center justify-between border-b border-slate-200/60 px-4 pt-4 dark:border-slate-800"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 rounded-full text-slate-500 hover:text-primary"
                                    @click="goToPrevious"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <span
                                    class="text-xs font-bold text-slate-500 dark:text-slate-400"
                                >
                                    {{ periodLabel }}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 rounded-full text-slate-500 hover:text-primary"
                                    @click="goToNext"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>

                            <!-- Week View -->
                            <div
                                v-if="viewMode === 'week'"
                                class="flex justify-between border-b border-slate-200/60 px-4 py-6 dark:border-slate-800"
                            >
                                <button
                                    v-for="day in weekDays"
                                    :key="day.date.toISOString()"
                                    type="button"
                                    :title="interviewTooltip(day.date)"
                                    class="flex cursor-pointer flex-col items-center gap-1 rounded-lg px-1 py-1 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                    @click="selectDay(day.date)"
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
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold"
                                        :class="
                                            day.active
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-slate-400 dark:text-slate-500'
                                        "
                                    >
                                        {{ day.dateLabel }}
                                    </span>
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            day.hasEvent
                                                ? 'bg-primary'
                                                : 'bg-transparent'
                                        "
                                    ></span>
                                </button>
                            </div>

                            <!-- Month View -->
                            <div
                                v-else-if="viewMode === 'month'"
                                class="border-b border-slate-200/60 px-4 py-6 dark:border-slate-800"
                            >
                                <div
                                    class="mb-2 grid grid-cols-7 text-center text-xs font-medium text-slate-500 dark:text-slate-400"
                                >
                                    <span
                                        v-for="label in DAY_LABELS"
                                        :key="label"
                                        >{{ label }}</span
                                    >
                                </div>
                                <div class="grid grid-cols-7 gap-y-2">
                                    <button
                                        v-for="day in monthDays"
                                        :key="day.date.toISOString()"
                                        type="button"
                                        :title="interviewTooltip(day.date)"
                                        class="flex cursor-pointer flex-col items-center gap-1 rounded-lg py-1 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        :class="{
                                            'opacity-40': !day.inCurrentMonth,
                                        }"
                                        @click="selectDay(day.date)"
                                    >
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold"
                                            :class="
                                                day.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-slate-500 dark:text-slate-400'
                                            "
                                        >
                                            {{ day.dateLabel }}
                                        </span>
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                day.hasEvent
                                                    ? 'bg-primary'
                                                    : 'bg-transparent'
                                            "
                                        ></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Year View -->
                            <div
                                v-else
                                class="border-b border-slate-200/60 px-4 py-6 dark:border-slate-800"
                            >
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="month in yearMonths"
                                        :key="month.date.toISOString()"
                                        type="button"
                                        class="flex cursor-pointer flex-col items-center gap-1 rounded-lg py-3 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                                        @click="selectMonth(month.date)"
                                    >
                                        <span
                                            class="text-sm font-bold"
                                            :class="
                                                month.active
                                                    ? 'text-primary'
                                                    : 'text-slate-500 dark:text-slate-400'
                                            "
                                        >
                                            {{ month.label }}
                                        </span>
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                month.hasEvent
                                                    ? 'bg-primary'
                                                    : 'bg-transparent'
                                            "
                                        ></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div
                                v-if="timelineEvents.length"
                                class="relative p-6"
                            >
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
                                        class="relative z-10 flex w-20 shrink-0 justify-end"
                                    >
                                        <div
                                            class="flex h-10 items-center justify-center rounded-full px-4 text-xs font-bold shadow-sm"
                                            :class="
                                                event.isPast
                                                    ? 'bg-slate-300 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                                    : 'bg-primary text-primary-foreground'
                                            "
                                        >
                                            {{ event.time }}
                                        </div>
                                    </div>

                                    <!-- Event Card -->
                                    <div class="mt-6 flex-1">
                                        <button
                                            type="button"
                                            class="relative w-full cursor-pointer rounded-[16px] p-4 text-left shadow-sm transition-opacity hover:opacity-90"
                                            :class="
                                                event.isPast
                                                    ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
                                                    : 'bg-primary text-primary-foreground'
                                            "
                                            @click="visitSession(event.session)"
                                        >
                                            <div class="text-[15px] font-bold">
                                                {{ event.title }}
                                            </div>
                                            <div
                                                class="mt-1 text-sm opacity-80"
                                            >
                                                {{ event.duration }}
                                            </div>
                                            <span
                                                v-if="event.isPast"
                                                class="mt-2 inline-block text-xs font-bold tracking-wide uppercase"
                                                >Past interview</span
                                            >
                                            <div
                                                class="absolute top-4 right-4 h-2 w-2 rounded-full bg-white"
                                            ></div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400"
                            >
                                No interviews on this day.
                            </div>
                        </CardContent>
                    </Card>
                    <Card
                        v-else
                        class="rounded-[24px] border-dashed shadow-none"
                    >
                        <CardContent class="p-6 text-center">
                            <CalendarDays
                                class="mx-auto mb-3 h-8 w-8 text-slate-400"
                            />
                            <p class="font-semibold">Your calendar is clear</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Upcoming employer interviews will be added
                                automatically.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Application Tracker -->
                    <div>
                        <div
                            class="mt-2 mb-4 flex items-center justify-between gap-4"
                        >
                            <div>
                                <h2
                                    class="text-xl font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Application Tracker
                                </h2>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline"
                                @click="visitRequestTracker"
                            >
                                View all applications
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>

                        <Card
                            class="overflow-hidden rounded-[24px] border border-slate-200/60 bg-slate-50 shadow-sm dark:bg-slate-900"
                        >
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead
                                        class="bg-slate-50 dark:bg-slate-900/50"
                                    >
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
                                                Salary
                                            </th>
                                            <th
                                                class="px-6 py-4 text-right font-bold whitespace-nowrap text-slate-900 dark:text-slate-100"
                                            >
                                                <button
                                                    type="button"
                                                    @click="visitRequestTracker"
                                                    class="cursor-pointer transition-colors hover:text-primary"
                                                >
                                                    Application Status
                                                </button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-white dark:divide-slate-800"
                                    >
                                        <tr
                                            v-for="app in tableApplications"
                                            :key="app.id"
                                            @click="visitJob(app)"
                                            :class="
                                                app.jobId
                                                    ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800'
                                                    : ''
                                            "
                                            class="bg-white transition-colors duration-200 dark:bg-slate-950"
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
                                                    <Badge
                                                        :aria-label="`Application status: ${app.status}`"
                                                        class="gap-1.5 border-0 px-3 py-1 font-bold"
                                                        :class="app.statusTone"
                                                    >
                                                        <span
                                                            aria-hidden="true"
                                                            class="text-[10px]"
                                                            >●</span
                                                        >
                                                        {{ app.status }}
                                                        <span class="sr-only">{{
                                                            app.statusIcon
                                                        }}</span>
                                                    </Badge>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                tableApplications.length === 0
                                            "
                                        >
                                            <td
                                                colspan="4"
                                                class="px-6 py-8 text-center"
                                            >
                                                <p class="font-semibold">
                                                    No applications yet
                                                </p>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Applications you submit will
                                                    appear here.
                                                </p>
                                                <Button
                                                    class="mt-3"
                                                    size="sm"
                                                    variant="outline"
                                                    @click="
                                                        router.visit(
                                                            '/job-selection',
                                                        )
                                                    "
                                                    >Browse Jobs</Button
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    </div>

                    <!-- Bottom Row: AI Jobs & Articles -->
                    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <!-- AI Recommended Jobs -->
                        <div>
                            <div
                                class="mb-4 flex flex-wrap items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-2">
                                    <h2
                                        class="text-lg font-bold text-slate-900 dark:text-slate-100"
                                    >
                                        AI-Recommended Jobs for You
                                    </h2>
                                    <Sparkles class="h-5 w-5 text-primary" />
                                </div>
                                <select
                                    v-if="resumes.length"
                                    :value="selectedResumeId ?? ''"
                                    aria-label="Resume used for recommendations"
                                    class="rounded-lg border bg-background px-3 py-2 text-sm font-semibold"
                                    @change="selectRecommendationResume"
                                >
                                    <option
                                        v-for="resume in resumes"
                                        :key="resume.id"
                                        :value="resume.id"
                                    >
                                        {{ resume.title }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-4">
                                <div
                                    v-if="recommendedJobs.length === 0"
                                    class="rounded-2xl border border-dashed p-5 text-center text-sm text-slate-500"
                                >
                                    <p
                                        class="font-semibold text-slate-700 dark:text-slate-200"
                                    >
                                        No matching jobs yet
                                    </p>
                                    <p class="mt-1">
                                        Complete or update your resume to
                                        improve your job recommendations.
                                    </p>
                                    <Button
                                        class="mt-3"
                                        size="sm"
                                        variant="outline"
                                        @click="router.visit('/resumes')"
                                        >Update Resume</Button
                                    >
                                </div>
                                <Card
                                    v-for="job in recommendedJobs"
                                    :key="job.id"
                                    class="rounded-[24px] border border-blue-100 bg-blue-50/60 py-2 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                                >
                                    <CardContent class="p-5">
                                        <div
                                            class="mb-4 flex items-start gap-4"
                                        >
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground shadow-sm"
                                            >
                                                {{ job.logoText }}
                                            </div>
                                            <div class="flex-1">
                                                <a
                                                    :href="job.url"
                                                    class="block text-lg leading-snug font-bold text-slate-900 transition-colors hover:text-primary dark:text-slate-100"
                                                >
                                                    {{ job.title }}
                                                </a>
                                                <p
                                                    class="mt-1 text-sm font-medium text-slate-500"
                                                >
                                                    {{ job.company }}
                                                </p>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                :title="
                                                    job.saved
                                                        ? 'Remove saved job'
                                                        : 'Save job'
                                                "
                                                @click="toggleSavedJob(job)"
                                            >
                                                <Heart class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div
                                            class="mb-4 flex flex-wrap gap-2 text-xs"
                                        >
                                            <Badge
                                                >{{ job.recommendationScore }}%
                                                match</Badge
                                            >
                                            <Badge variant="outline">{{
                                                job.salary
                                            }}</Badge>
                                            <Badge
                                                v-if="job.location"
                                                variant="outline"
                                                >{{ job.location }}</Badge
                                            >
                                            <Badge
                                                v-if="job.workplaceType"
                                                variant="outline"
                                                >{{ job.workplaceType }}</Badge
                                            >
                                        </div>

                                        <div
                                            v-if="job.strongMatches.length"
                                            class="mb-3 text-xs text-emerald-700 dark:text-emerald-300"
                                        >
                                            <p
                                                v-for="match in job.strongMatches.slice(
                                                    0,
                                                    2,
                                                )"
                                                :key="match"
                                            >
                                                ✓ {{ match }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="job.gaps.length"
                                            class="mb-4 text-xs text-amber-700 dark:text-amber-300"
                                        >
                                            <p
                                                v-for="gap in job.gaps.slice(
                                                    0,
                                                    1,
                                                )"
                                                :key="gap"
                                            >
                                                △ {{ gap }}
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="
                                                    showRecommendationExplanation(
                                                        job,
                                                    )
                                                "
                                                >Why this matches</Button
                                            >
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="router.visit(job.url)"
                                                >View vacancy</Button
                                            >
                                            <Button
                                                size="sm"
                                                :disabled="
                                                    job.applied ||
                                                    !selectedResumeId
                                                "
                                                @click="
                                                    applyToRecommendedJob(job)
                                                "
                                            >
                                                {{
                                                    job.applied
                                                        ? 'Applied'
                                                        : 'Apply'
                                                }}
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="toggleSavedJob(job)"
                                            >
                                                {{
                                                    job.saved ? 'Saved' : 'Save'
                                                }}
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                                <Button
                                    variant="link"
                                    class="px-0 font-semibold"
                                    @click="router.visit('/job-selection')"
                                >
                                    Explore more jobs
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
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
                                <a
                                    v-for="article in articles"
                                    :key="article.id"
                                    :href="article.url"
                                    target="_blank"
                                    class="flex flex-col gap-3 overflow-hidden rounded-[24px] border border-slate-200/70 bg-white py-0 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800"
                                >
                                    <img
                                        :src="article.image"
                                        :alt="`${article.title} cover`"
                                        class="h-40 w-full rounded-t-[16px] object-cover"
                                        loading="lazy"
                                        @error="
                                            useArticleFallback(
                                                $event,
                                                article.fallback_image,
                                            )
                                        "
                                    />
                                    <div class="px-4 pb-4">
                                        <div
                                            class="mb-2 flex items-center justify-between gap-3 text-xs font-bold"
                                        >
                                            <span class="text-primary">{{
                                                article.category
                                            }}</span>
                                            <span class="text-slate-500">{{
                                                article.reading_time
                                            }}</span>
                                        </div>
                                        <p
                                            class="text-lg leading-snug font-bold text-slate-900 dark:text-slate-100"
                                        >
                                            {{ article.title }}
                                        </p>
                                        <p
                                            v-if="article.description"
                                            class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400"
                                        >
                                            {{ article.description }}
                                        </p>
                                        <div
                                            class="mt-3 flex items-center justify-between text-xs font-semibold text-slate-500"
                                        >
                                            <span>{{ article.source }}</span>
                                            <span class="text-primary"
                                                >Open / Read →</span
                                            >
                                        </div>
                                    </div>
                                </a>
                                <Button
                                    variant="link"
                                    class="px-0 font-semibold"
                                    @click="router.visit('/development')"
                                >
                                    View all resources
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Explainable recommendation details -->
        <Dialog
            :open="recommendationsOpen"
            @update:open="recommendationsOpen = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle
                        >{{ recommendationsJob?.recommendationScore }}% Overall
                        Match</DialogTitle
                    >
                    <DialogDescription>
                        Calculated from the selected resume and real vacancy
                        fields.
                    </DialogDescription>
                </DialogHeader>
                <div class="max-h-[60vh] space-y-4 overflow-y-auto">
                    <div
                        v-for="criterion in recommendationsJob?.criteria"
                        :key="criterion.label"
                        class="rounded-lg border p-3"
                    >
                        <div
                            class="flex justify-between gap-3 text-sm font-bold"
                        >
                            <span>{{ criterion.label }}</span
                            ><span>{{ criterion.score }}%</span>
                        </div>
                        <p
                            v-if="criterion.matches?.length"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ criterion.matches.join(', ') }}
                        </p>
                    </div>
                    <div v-if="recommendationsJob?.strongMatches.length">
                        <h4 class="mb-1 text-sm font-bold">Strong matches</h4>
                        <p
                            v-for="item in recommendationsJob.strongMatches"
                            :key="item"
                            class="text-sm text-emerald-700"
                        >
                            ✓ {{ item }}
                        </p>
                    </div>
                    <div v-if="recommendationsJob?.gaps.length">
                        <h4 class="mb-1 text-sm font-bold">Potential gaps</h4>
                        <p
                            v-for="item in recommendationsJob.gaps"
                            :key="item"
                            class="text-sm text-amber-700"
                        >
                            △ {{ item }}
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Close</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

