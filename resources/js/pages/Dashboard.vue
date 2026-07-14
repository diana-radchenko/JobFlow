<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Bot,
    SlidersHorizontal,
    ArrowDownUp,
    Maximize2,
    Sparkles,
    Heart,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { show as interviewSessionShow } from '@/actions/App/Http/Controllers/InterviewSessionController';
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
import { Spinner } from '@/components/ui/spinner';
import { stringForHuman } from '@/helpers/strings';
import { dashboard } from '@/routes';
import { show as jobSelectionShow } from '@/routes/job-selection';
import { store as scoreResumeRequest } from '@/routes/resume-score';
import type {
    InterviewSession,
    UserWorkJobApplication,
} from '@/types/laravel-models';

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

const props = defineProps<{
    applications: UserWorkJobApplication[] | null;
    interviewSessions: InterviewSession[] | null;
    profileFirstName: string;
    resumes: DashboardResume[];
}>();

const visitJob = (app: any) => {
    if (app.jobId) {
        router.visit(jobSelectionShow(app.jobId).url);
    }
};

const visitSession = (session: InterviewSession) => {
    router.visit(interviewSessionShow(session.id).url);
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

// Mock data
/* const mockApplications = [
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
]; */

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
            jobId: app.work_job_id,
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
    return [...realApps];
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

const today = startOfDay(new Date());
const selectedDate = ref(today);
const weekStart = ref(startOfWeek(today));

const weekDays = computed(() =>
    Array.from({ length: 7 }, (_, i) => {
        const date = new Date(weekStart.value);
        date.setDate(date.getDate() + i);

        return {
            date,
            day: DAY_LABELS[date.getDay()],
            dateLabel: String(date.getDate()),
            active: isSameDay(date, selectedDate.value),
            hasEvent: !!sessionsByDay.value.get(date.toDateString())?.length,
        };
    }),
);

const weekRangeLabel = computed(() => {
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

const goToPreviousWeek = () => {
    const date = new Date(weekStart.value);
    date.setDate(date.getDate() - 7);
    weekStart.value = date;
};

const goToNextWeek = () => {
    const date = new Date(weekStart.value);
    date.setDate(date.getDate() + 7);
    weekStart.value = date;
};

const selectDay = (date: Date) => {
    selectedDate.value = date;
};

const sessionsByDay = computed(() => {
    const map = new Map<string, InterviewSession[]>();

    for (const session of props.interviewSessions || []) {
        if (!session.created_at) {
continue;
}

        const key = new Date(session.created_at).toDateString();
        const bucket = map.get(key) ?? [];
        bucket.push(session);
        map.set(key, bucket);
    }

    for (const bucket of map.values()) {
        bucket.sort(
            (a, b) =>
                new Date(a.created_at!).getTime() -
                new Date(b.created_at!).getTime(),
        );
    }

    return map;
});

const timelineEvents = computed(() => {
    const sessions =
        sessionsByDay.value.get(selectedDate.value.toDateString()) || [];

    return sessions.map((session) => {
        const date = new Date(session.created_at!);
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return {
            time: `${hours}:${minutes}`,
            title: `${stringForHuman(session.type)} Interview`,
            duration: `${stringForHuman(session.complexity)} · ${stringForHuman(session.status)}`,
            session,
        };
    });
});

const aiJobsMock = [
    {
        id: 1,
        url: "https://www.innovatetechinc.com/careers.php",
        company: 'InnovateTech',
        logoText: 'IT',
        title: 'InnovateTech is looking for a Software Engineer to join our team!',
        salary: '$95,000',
        tags: ['Python', 'React'],
    },
    {
        id: 2,
        url: "https://www.data-wise-inc.com/career-opportunities",
        company: 'DataWise',
        logoText: 'DW',
        title: 'DataWise is hiring a Data Scientist to drive data-driven decision-making.',
        salary: '$105,000',
        tags: ['Machine learning'],
    },
/*     {
        id: 3,
        url: "#",
        company: 'SecureNet',
        logoText: 'SN',
        title: 'SecureNet is looking for a Cybersecurity Specialist!',
        salary: '$98,000',
        tags: ['SOC', 'IDS/IPS'],
    }, */
];

type AiJobMock = (typeof aiJobsMock)[number];

const jobScores = reactive<Record<number, ResumeScoreResult>>({});
const scoringJobId = ref<number | null>(null);
const scoreErrors = reactive<Record<number, string>>({});

const resumePickerOpen = ref(false);
const resumePickerJob = ref<AiJobMock | null>(null);

const recommendationsOpen = ref(false);
const recommendationsJob = ref<AiJobMock | null>(null);
const activeRecommendations = computed<ResumeScoreResult | null>(() =>
    recommendationsJob.value ? (jobScores[recommendationsJob.value.id] ?? null) : null,
);

const scoreLabel = (job: AiJobMock) => {
    const result = jobScores[job.id];

    return result ? `${result.score}/100` : 'Not scored yet';
};

const scoreButtonLabel = (job: AiJobMock) => {
    if (scoringJobId.value === job.id) {
        return 'SCORING…';
    }

    return jobScores[job.id] ? 'VIEW RECOMMENDATIONS' : 'SCORE RESUME';
};

const scoreResumeForJob = async (job: AiJobMock, resumeId: number) => {
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
        scoreErrors[job.id] =
            'Could not score this resume. Please try again.';
    } finally {
        scoringJobId.value = null;
    }
};

const onScoreResume = (job: AiJobMock) => {
    if (scoringJobId.value !== null) {
        return;
    }

    if (jobScores[job.id]) {
        recommendationsJob.value = job;
        recommendationsOpen.value = true;
        return;
    }

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

const selectResumeForScoring = (resumeId: number) => {
    const job = resumePickerJob.value;
    resumePickerOpen.value = false;
    resumePickerJob.value = null;

    if (job) {
        scoreResumeForJob(job, resumeId);
    }
};

const articlesMock = [
    {
        id: 1,
        image: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&h=150&auto=format&fit=crop',
        title: 'Working Part-Time in Retirement: Is It Right for You?',
        url: 'https://www.tiaa.org/public/transitioners/working-part-time-in-retirement--is-it-right-for-you',
    },
    {
        id: 2,
        image: 'https://proximus.talent-pool.com/cdn/image/5337d40d-aa5a-4cca-96eb-2ee1485ee6aa?withoutEnlargement=true&width=1440&format=webp',
        title: 'Why freelance at Proximus?',
        url: 'https://proximus.talent-pool.com/category/274/data-analysts-security?utm_medium=paidsearch&utm_source=googlesearch_rsr&utm_campaign=proximus_belgium_softwareengineering_talent_pool&utm_content=english-uk_text&gad_source=1&gad_campaignid=23822730197&gbraid=0AAAAA-QuVl7UcuDyNP2qXDQP1ukHqa0Dd&gclid=Cj0KCQjw3K7RBhDJARIsAKRtP5S60GfJvKYGUPXRZqXmqMS23enhCyn1edDxp1YTfGH5XLpkwMbT0koaAm8lEALw_wcB',
    },
    {
        id: 3,
        image: 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=400&h=150&auto=format&fit=crop',
        title: 'How to Become a Financial Planner After 50: A Late-Career Pivot',
        url: 'https://www.aarp.org/work/careers/financial-planner-career-change/?utm_source',
    },
    {
        id: 4,
        image: 'https://www.kingseducation.com/assets/uploads/Stout_800.jpg',
        title: 'Why (and how) to get an internship after graduation',
        url: 'https://www.kingseducation.com/kings-life/internship-after-graduation?utm_source',
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
                            class="flex items-center justify-between border-b border-slate-200/60 px-4 pt-4 dark:border-slate-800"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full text-slate-500 hover:text-primary"
                                @click="goToPreviousWeek"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <span
                                class="text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                {{ weekRangeLabel }}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 rounded-full text-slate-500 hover:text-primary"
                                @click="goToNextWeek"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                        <div
                            class="flex justify-between border-b border-slate-200/60 px-4 py-6 dark:border-slate-800"
                        >
                            <button
                                v-for="day in weekDays"
                                :key="day.date.toISOString()"
                                type="button"
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
                                        class="flex h-10 items-center justify-center rounded-full bg-primary px-4 text-xs font-bold text-primary-foreground shadow-sm"
                                    >
                                        {{ event.time }}
                                    </div>
                                </div>

                                <!-- Event Card -->
                                <div class="mt-6 flex-1">
                                    <button
                                        type="button"
                                        class="relative w-full cursor-pointer rounded-[16px] bg-primary p-4 text-left text-primary-foreground shadow-sm transition-opacity hover:opacity-90"
                                        @click="visitSession(event.session)"
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
                                        @click="visitJob(app)"
                                        :class="app.jobId ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800' : ''"
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
                                            <a
                                                :href="job.url"
                                                target="_blank"
                                                class="block text-[15px] leading-snug font-bold text-slate-900 hover:text-primary transition-colors dark:text-slate-100"
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
                                        <Button
                                            variant="ghost"
                                            class="h-9 rounded-lg border border-slate-100 bg-white text-xs font-bold text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                                            :disabled="scoringJobId === job.id"
                                            @click="onScoreResume(job)"
                                        >
                                            <Spinner
                                                v-if="scoringJobId === job.id"
                                                class="mr-2 h-3 w-3"
                                            />
                                            {{ scoreButtonLabel(job) }}
                                        </Button>
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
                                v-for="article in articlesMock"
                                :key="article.id"
                                :href="article.url"
                                target="_blank"
                                class="flex flex-col gap-3 overflow-hidden rounded-[24px] border border-slate-200/60 bg-white py-0 shadow-sm dark:border-slate-800 dark:bg-slate-900 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                            >
                                <img
                                    :src="article.image"
                                    alt="Article cover"
                                    class="h-38 w-full rounded-t-[16px] object-cover"
                                />
                                <p
                                    class="px-4 pb-4 text-[15px] leading-snug font-bold text-slate-900 dark:text-slate-100"
                                >
                                    {{ article.title }}
                                </p>
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
                        Which resume should the AI use to score your fit for
                        "{{ resumePickerJob?.title }}"?
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
                                v-for="(item, index) in activeRecommendations.highlights"
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
                                v-for="(item, index) in activeRecommendations.additions"
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
                                v-for="(item, index) in activeRecommendations.removals"
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
