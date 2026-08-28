<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Bot,
    SlidersHorizontal,
    ArrowDownUp,
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
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
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
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import { stringForHuman } from '@/helpers/strings';
import type {
    InterviewSession,
    UserWorkJobApplication,
    WorkJob,
} from '@/types/laravel-models';
import { show as interviewSessionShow } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { dashboard } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';
import { store as scoreResumeRequest } from '@/routes/resume-score';

interface DashboardResume {
    id: number;
    title: string;
}

interface ResumeScoreResult {
    score: number;
    summary: string;
    highlights: string[];
    additions: string[];
    removals: string[];
}

interface DashboardSummary {
    applications: number;
    interviews: number;
    resumeCompleteness: number | null;
    recommendedMatches: number;
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

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
    interviewSessions: InterviewSession[] | null;
    nextInterview: InterviewSession | null;
    dashboardSummary: DashboardSummary;
    profileFirstName: string;
    resumes: DashboardResume[];
    selectedResumeId: number | null;
    recommendedJobs: Array<{ job: WorkJob; score: number; reasons: string[] }>;
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
            salary: app.work_job?.salary_start
                ? Number(app.work_job.salary_start).toLocaleString()
                : '-',
            status: status.label,
            statusTone: status.tone,
            statusIcon: status.icon,
        };
    });

    return [...realApps];
});

const trackerStatusOptions = computed(() =>
    Array.from(
        new Set(tableApplications.value.map((app) => app.status)),
    ).sort(),
);

const trackerStatusFilter = ref<string[]>([]);

type TrackerSortOption =
    | 'company-asc'
    | 'company-desc'
    | 'title-asc'
    | 'title-desc'
    | 'salary-asc'
    | 'salary-desc'
    | 'status-asc'
    | 'status-desc';

const trackerSort = ref<TrackerSortOption>('company-asc');

const trackerSalaryValue = (salary: string) =>
    Number(salary.replace(/[^0-9.-]/g, '')) || 0;

const filteredSortedApplications = computed(() => {
    const filtered = trackerStatusFilter.value.length
        ? tableApplications.value.filter((app) =>
              trackerStatusFilter.value.includes(app.status),
          )
        : tableApplications.value;

    const [key, direction] = trackerSort.value.split('-') as [
        'company' | 'title' | 'salary' | 'status',
        'asc' | 'desc',
    ];

    const sorted = [...filtered].sort((a, b) => {
        const result =
            key === 'salary'
                ? trackerSalaryValue(a.salary) - trackerSalaryValue(b.salary)
                : a[key].localeCompare(b[key]);

        return direction === 'asc' ? result : -result;
    });

    return sorted;
});

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
    new Intl.DateTimeFormat(undefined, {
        timeZone: session.timezone ?? 'UTC',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(session.scheduled_at!));

const interviewTime = (session: InterviewSession) =>
    new Intl.DateTimeFormat(undefined, {
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
            label: date.toLocaleDateString(undefined, { month: 'short' }),
            active: today.getFullYear() === year && today.getMonth() === month,
            hasEvent,
        };
    });
});

const periodLabel = computed(() => {
    if (viewMode.value === 'month') {
        return monthStart.value.toLocaleDateString(undefined, {
            month: 'long',
            year: 'numeric',
        });
    }

    if (viewMode.value === 'year') {
        return String(anchorDate.value.getFullYear());
    }

    const start = weekDays.value[0].date;
    const end = weekDays.value[6].date;
    const startLabel = start.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
    const endLabel = end.toLocaleDateString(
        undefined,
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
    props.recommendedJobs.map(({ job, score, reasons }) => ({
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
        reasons,
    })),
);

type RecommendedJob = (typeof recommendedJobs.value)[number];

const jobScores = reactive<Record<number, ResumeScoreResult>>({});
const scoringJobId = ref<number | null>(null);
const scoreErrors = reactive<Record<number, string>>({});

const resumePickerOpen = ref(false);
const resumePickerJob = ref<RecommendedJob | null>(null);

const recommendationsOpen = ref(false);
const recommendationsJob = ref<RecommendedJob | null>(null);
const activeRecommendations = computed<ResumeScoreResult | null>(() =>
    recommendationsJob.value
        ? (jobScores[recommendationsJob.value.id] ?? null)
        : null,
);

const scoreLabel = (job: RecommendedJob) => {
    const result = jobScores[job.id];

    return result ? `${result.score}/100` : 'Not scored yet';
};

const scoreButtonLabel = (job: RecommendedJob) => {
    if (scoringJobId.value === job.id) {
        return 'SCORING…';
    }

    return jobScores[job.id] ? 'RESULT' : 'SCORE RESUME';
};

const scoreResumeForJob = async (job: RecommendedJob, resumeId: number) => {
    scoringJobId.value = job.id;
    delete scoreErrors[job.id];

    try {
        const response = await fetch(scoreResumeRequest.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token as string,
            },
            body: JSON.stringify({
                resume_id: resumeId,
                job_title: job.title,
                job_company: job.company,
                job_salary: job.salary,
                job_tags: job.tags,
            }),
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const data = (await response.json()) as ResumeScoreResult;
        jobScores[job.id] = data;
        recommendationsJob.value = job;
        recommendationsOpen.value = true;
    } catch (error) {
        console.error('Failed to score resume:', error);
        scoreErrors[job.id] = 'Could not score this resume. Please try again.';
    } finally {
        scoringJobId.value = null;
    }
};

const startScoring = (job: RecommendedJob) => {
    if (props.resumes.length === 0) {
        router.visit('/resumes');

        return;
    }

    if (props.resumes.length === 1) {
        scoreResumeForJob(job, props.resumes[0].id);

        return;
    }

    resumePickerJob.value = job;
    resumePickerOpen.value = true;
};

const onScoreResume = (job: RecommendedJob) => {
    if (scoringJobId.value !== null) {
        return;
    }

    if (jobScores[job.id]) {
        recommendationsJob.value = job;
        recommendationsOpen.value = true;

        return;
    }

    startScoring(job);
};

const onRescoreResume = (job: RecommendedJob) => {
    if (scoringJobId.value !== null) {
        return;
    }

    startScoring(job);
};

const selectResumeForScoring = (resumeId: number) => {
    const job = resumePickerJob.value;
    resumePickerOpen.value = false;
    resumePickerJob.value = null;

    if (job) {
        scoreResumeForJob(job, resumeId);
    }
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

    <div class="container mx-auto max-w-[1400px] px-5 py-8 font-sans">
        <div class="mb-6 flex items-center gap-3">
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

        <section
            aria-label="Job search summary"
            class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4"
        >
            <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="flex items-center gap-3 p-4">
                    <BriefcaseBusiness class="h-5 w-5 text-blue-600" />
                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Applications
                        </p>
                        <p class="text-2xl font-extrabold">
                            {{ dashboardSummary.applications }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="flex items-center gap-3 p-4">
                    <CalendarDays class="h-5 w-5 text-amber-600" />
                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Interviews
                        </p>
                        <p class="text-2xl font-extrabold">
                            {{ dashboardSummary.interviews }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="flex items-center gap-3 p-4">
                    <FileCheck2 class="h-5 w-5 text-emerald-600" />
                    <div>
                        <p class="text-xs font-medium text-slate-500">
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
            <Card class="border-slate-200/70 shadow-sm dark:border-slate-800">
                <CardContent class="flex items-center gap-3 p-4">
                    <Target class="h-5 w-5 text-violet-600" />
                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Recommended matches
                        </p>
                        <p class="text-2xl font-extrabold">
                            {{ dashboardSummary.recommendedMatches }}
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
                            {{ nextInterview.work_job?.title ?? 'Interview' }}
                        </h2>
                        <p
                            class="font-medium text-slate-600 dark:text-slate-300"
                        >
                            {{ nextInterview.work_job?.company ?? 'Employer' }}
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
                        <div v-if="timelineEvents.length" class="relative p-6">
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
                                        <div class="mt-1 text-sm opacity-80">
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
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Application Tracker -->
                <div>
                    <div class="mt-2 mb-4 flex items-center justify-between">
                        <button
                            type="button"
                            @click="visitRequestTracker"
                            class="cursor-pointer rounded-lg px-2 py-1 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            <h2
                                class="text-xl font-bold text-slate-900 hover:text-primary dark:text-slate-100"
                            >
                                Application Tracker (Update)
                            </h2>
                        </button>
                        <div class="flex gap-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-9 w-9 rounded-full border-0 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground"
                                    >
                                        <SlidersHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel
                                        >Filter by status</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuCheckboxItem
                                        v-for="status in trackerStatusOptions"
                                        :key="status"
                                        :checked="
                                            trackerStatusFilter.includes(status)
                                        "
                                        @update:checked="
                                            (checked: boolean) => {
                                                trackerStatusFilter = checked
                                                    ? [
                                                          ...trackerStatusFilter,
                                                          status,
                                                      ]
                                                    : trackerStatusFilter.filter(
                                                          (s) => s !== status,
                                                      );
                                            }
                                        "
                                    >
                                        {{ status }}
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-9 w-9 rounded-full border-0 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 hover:text-primary-foreground"
                                    >
                                        <ArrowDownUp class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel
                                        >Sort by</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuRadioGroup
                                        v-model="trackerSort"
                                    >
                                        <DropdownMenuRadioItem
                                            value="company-asc"
                                            >Company
                                            (A-Z)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="company-desc"
                                            >Company
                                            (Z-A)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem value="title-asc"
                                            >Job Title
                                            (A-Z)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="title-desc"
                                            >Job Title
                                            (Z-A)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="salary-desc"
                                            >Salary
                                            (High-Low)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="salary-asc"
                                            >Salary
                                            (Low-High)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="status-asc"
                                            >Status (A-Z)</DropdownMenuRadioItem
                                        >
                                        <DropdownMenuRadioItem
                                            value="status-desc"
                                            >Status (Z-A)</DropdownMenuRadioItem
                                        >
                                    </DropdownMenuRadioGroup>
                                </DropdownMenuContent>
                            </DropdownMenu>
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
                                        v-for="app in filteredSortedApplications"
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
                            <p
                                v-if="recommendedJobs.length === 0"
                                class="rounded-2xl border border-dashed p-5 text-sm text-slate-500"
                            >
                                No published employer vacancies are available
                                yet. Recommendations will appear here when
                                employers publish matching jobs.
                            </p>
                            <Card
                                v-for="job in recommendedJobs"
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
                                            <a
                                                :href="job.url"
                                                class="block text-[15px] leading-snug font-bold text-slate-900 transition-colors hover:text-primary dark:text-slate-100"
                                            >
                                                {{ job.title }}
                                            </a>
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
                                            AI SCORE: {{ scoreLabel(job) }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                class="h-9 rounded-lg border border-slate-100 bg-white text-xs font-bold text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                                :disabled="
                                                    scoringJobId === job.id
                                                "
                                                @click="onScoreResume(job)"
                                            >
                                                <Spinner
                                                    v-if="
                                                        scoringJobId === job.id
                                                    "
                                                    class="mr-2 h-3 w-3"
                                                />
                                                {{ scoreButtonLabel(job) }}
                                            </Button>
                                            <Button
                                                v-if="jobScores[job.id]"
                                                variant="ghost"
                                                class="h-9 shrink-0 rounded-lg border border-slate-100 bg-white text-xs font-bold text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                                :disabled="
                                                    scoringJobId === job.id
                                                "
                                                title="Score again"
                                                @click="onRescoreResume(job)"
                                            >
                                                SCORE
                                            </Button>
                                        </div>
                                    </div>
                                    <p
                                        v-if="scoreErrors[job.id]"
                                        class="mt-2 text-xs text-destructive"
                                    >
                                        {{ scoreErrors[job.id] }}
                                    </p>
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
                            <a
                                v-for="article in articles"
                                :key="article.id"
                                :href="article.url"
                                target="_blank"
                                class="flex flex-col gap-3 overflow-hidden rounded-[24px] border border-slate-200/60 bg-white py-0 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800"
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
                                        class="text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resume Picker Dialog (shown when the user has multiple resumes) -->
        <Dialog
            :open="resumePickerOpen"
            @update:open="resumePickerOpen = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Choose a resume to score</DialogTitle>
                    <DialogDescription>
                        Which resume should the AI use to score your fit for "{{
                            resumePickerJob?.title
                        }}"?
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <Button
                        v-for="resume in props.resumes"
                        :key="resume.id"
                        type="button"
                        variant="outline"
                        class="w-full justify-start"
                        @click="selectResumeForScoring(resume.id)"
                    >
                        {{ resume.title }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- AI Score Recommendations Dialog -->
        <Dialog
            :open="recommendationsOpen"
            @update:open="recommendationsOpen = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        AI Score: {{ activeRecommendations?.score }}/100
                    </DialogTitle>
                    <DialogDescription>
                        {{ activeRecommendations?.summary }}
                    </DialogDescription>
                </DialogHeader>
                <div class="max-h-[60vh] space-y-4 overflow-y-auto">
                    <div v-if="activeRecommendations?.highlights.length">
                        <h4 class="mb-1 text-sm font-bold text-foreground">
                            Highlight
                        </h4>
                        <ul
                            class="list-disc space-y-1 pl-5 text-sm text-foreground/70"
                        >
                            <li
                                v-for="(
                                    item, index
                                ) in activeRecommendations.highlights"
                                :key="index"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                    <div v-if="activeRecommendations?.additions.length">
                        <h4 class="mb-1 text-sm font-bold text-foreground">
                            Add
                        </h4>
                        <ul
                            class="list-disc space-y-1 pl-5 text-sm text-foreground/70"
                        >
                            <li
                                v-for="(
                                    item, index
                                ) in activeRecommendations.additions"
                                :key="index"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                    <div v-if="activeRecommendations?.removals.length">
                        <h4 class="mb-1 text-sm font-bold text-foreground">
                            Remove
                        </h4>
                        <ul
                            class="list-disc space-y-1 pl-5 text-sm text-foreground/70"
                        >
                            <li
                                v-for="(
                                    item, index
                                ) in activeRecommendations.removals"
                                :key="index"
                            >
                                {{ item }}
                            </li>
                        </ul>
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

