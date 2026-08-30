<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    BrainCircuit,
    BriefcaseBusiness,
    CheckCircle2,
    FileText,
    HelpCircle,
    Loader2,
    MessageSquareText,
    Mic,
    PlayCircle,
    Sparkles,
    Signal,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import InterviewHistoryRow from '@/components/interview/InterviewHistoryRow.vue';
import UpcomingInterviewRow from '@/components/interview/UpcomingInterviewRow.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    InterviewHistorySession,
    UpcomingInterview,
} from '@/types/interview-center';
import { show as interviewPrepShow } from '@/actions/App/Http/Controllers/InterviewPrepController';
import {
    complete as interviewSessionComplete,
    destroy as interviewSessionDestroy,
    show as interviewSessionShow,
    store as interviewSessionStore,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
import { interviewPreparation } from '@/routes';

type InterviewTab = 'prepare' | 'upcoming' | 'history';
const tabs: InterviewTab[] = ['prepare', 'upcoming', 'history'];

const props = defineProps<{
    activeSession?: {
        id: number;
        work_job_id: number | null;
        type: string;
        complexity: string;
        mode: string;
        created_at: string;
        resume: { id: number; title: string } | null;
        work_job: { id: number; title: string; company: string } | null;
    } | null;
    pastSessions: {
        data: InterviewHistorySession[];
    };
    resumes: { id: number; title: string }[];
    applications: {
        id: number;
        work_job_id: number;
        work_job: { id: number; title: string; company: string } | null;
    }[];
    upcomingInterviews: UpcomingInterview[];
}>();

const activeTab = ref<InterviewTab>('prepare');
const interviewType = ref('resume-based');
const complexity = ref('advanced');
const resumeId = ref<string>(
    props.resumes.length > 0 ? String(props.resumes[0].id) : '',
);
const workJobId = ref<string>('none');
const interviewMode = ref<'text' | 'live'>('text');
const isCompletingInterview = ref(false);
const howItWorksOpen = ref(false);
const deleteDialogOpen = ref(false);
const sessionToDelete = ref<InterviewHistorySession | null>(null);
const deletingSessionId = ref<number | null>(null);
const deletedSessionIds = ref<number[]>([]);

const interviewTypes = [
    {
        id: 'behavioral',
        label: 'Behavioral',
        description: 'Practice experience, motivation, and teamwork questions.',
    },
    {
        id: 'technical',
        label: 'Technical',
        description: 'Focus on role-specific knowledge and practical skills.',
    },
    {
        id: 'case-study',
        label: 'Case Study / Problem Solving',
        description: 'Work through structured scenarios and explain decisions.',
    },
    {
        id: 'resume-based',
        label: 'AI Personalized',
        description: 'Questions tailored to your resume and selected vacancy.',
    },
];

const complexities = [
    {
        id: 'beginner',
        label: 'Beginner',
        description: 'Foundational questions with a supportive pace.',
    },
    {
        id: 'intermediate',
        label: 'Intermediate',
        description: 'Balanced questions that test applied experience.',
    },
    {
        id: 'advanced',
        label: 'Advanced',
        description: 'Challenging scenarios with deeper follow-up questions.',
    },
];

const selectedInterviewType = computed(() =>
    interviewTypes.find((type) => type.id === interviewType.value),
);
const selectedComplexity = computed(() =>
    complexities.find((level) => level.id === complexity.value),
);
const visiblePastSessions = computed(() =>
    props.pastSessions.data.filter(
        (session) => !deletedSessionIds.value.includes(session.id),
    ),
);
const recentSessions = computed(() => visiblePastSessions.value.slice(0, 3));
const upcomingPreview = computed(() => props.upcomingInterviews.slice(0, 3));

const form = useForm({
    type: '',
    complexity: '',
    mode: '',
    resume_id: '',
    work_job_id: '',
});

function handleCompleteInterviewSubmit() {
    if (isCompletingInterview.value) {
        return;
    }

    isCompletingInterview.value = true;
}

function selectedContext() {
    return {
        type: interviewType.value,
        complexity: complexity.value,
        mode: interviewMode.value,
        resume_id: resumeId.value,
        work_job_id: workJobId.value === 'none' ? undefined : workJobId.value,
    };
}

function prepareWithAi() {
    if (!resumeId.value || props.activeSession) {
        return;
    }

    router.visit(interviewPrepShow.url({ query: selectedContext() }));
}

function startAiInterview(): void {
    if (props.activeSession) {
        alert(
            'You already have an active AI interview. Please finish it first.',
        );

        return;
    }

    if (!resumeId.value) {
        alert('Please select which resume to use for this interview.');

        return;
    }

    form.type = interviewType.value;
    form.complexity = complexity.value;
    form.mode = interviewMode.value;
    form.resume_id = resumeId.value;
    form.work_job_id = workJobId.value === 'none' ? '' : workJobId.value;
    form.post(interviewSessionStore.url());
}

function prepareForUpcoming(interview: UpcomingInterview): void {
    const hasApplication = props.applications.some(
        (application) => application.work_job_id === interview.work_job_id,
    );
    const hasResume = props.resumes.some(
        (resume) => resume.id === interview.resume_id,
    );

    if (hasApplication) {
        workJobId.value = String(interview.work_job_id);
    }

    if (hasResume && interview.resume_id) {
        resumeId.value = String(interview.resume_id);
    }

    activeTab.value = 'prepare';
    nextTick(() => {
        document
            .getElementById('interview-setup')
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function requestDelete(session: InterviewHistorySession): void {
    sessionToDelete.value = session;
    deleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!sessionToDelete.value || deletingSessionId.value !== null) {
        return;
    }

    const sessionId = sessionToDelete.value.id;
    deletingSessionId.value = sessionId;

    router.delete(interviewSessionDestroy.url(sessionId), {
        preserveScroll: true,
        onSuccess: () => {
            deletedSessionIds.value = [...deletedSessionIds.value, sessionId];
            deleteDialogOpen.value = false;
            sessionToDelete.value = null;
        },
        onFinish: () => {
            deletingSessionId.value = null;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Interview',
                href: interviewPreparation(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Interview Center" />

    <div class="jobflow-page interview-center font-sans dark:bg-slate-950">
        <div class="jobflow-page-frame">
            <header
                class="relative flex min-h-24 flex-wrap items-start justify-between gap-4 md:min-h-28"
            >
                <div class="relative z-10 pt-3">
                    <h1
                        class="flex items-center gap-3 text-[28px] leading-tight font-bold tracking-tight text-[#07162F] md:text-[34px] dark:text-white"
                    >
                        Interview Center
                        <Sparkles class="h-7 w-7 text-[#7A6CFF]" />
                    </h1>
                    <p
                        class="mt-3 text-sm text-[#52658B] md:text-base dark:text-slate-400"
                    >
                        Practice smarter. Interview better. Get hired.
                    </p>
                </div>
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -top-3 right-44 hidden h-36 w-44 lg:block"
                >
                    <div
                        class="absolute right-2 bottom-0 h-12 w-36 rounded-[50%] bg-violet-200/45 blur-xl"
                    />
                    <div
                        class="absolute top-0 right-3 h-24 w-24 rotate-12 rounded-2xl border border-blue-100/70 bg-blue-100/40"
                    />
                    <div
                        class="absolute top-2 right-7 h-24 w-24 rotate-6 rounded-2xl border border-white bg-gradient-to-br from-blue-100/70 to-violet-100/40"
                    />
                    <div
                        class="absolute top-4 right-12 flex h-24 w-24 items-center justify-center rounded-2xl border border-white/90 bg-gradient-to-br from-white/80 to-blue-100/60 shadow-[0_10px_28px_rgba(128,147,240,0.12)]"
                    >
                        <Mic
                            class="h-12 w-12 text-white drop-shadow-[0_2px_5px_rgba(92,155,255,0.85)]"
                            :stroke-width="1.5"
                        />
                    </div>
                    <div
                        class="absolute right-8 bottom-0 h-7 w-32 rounded-[50%] border border-white bg-white/55 shadow-[0_8px_16px_rgba(154,121,242,0.15)]"
                    />
                </div>
                <button
                    type="button"
                    class="relative z-10 inline-flex h-10 items-center gap-2 rounded-lg border border-[#E3E8F2] bg-white px-4 text-xs font-semibold text-[#0A2E48] shadow-[0_3px_8px_rgba(7,31,73,0.04)] transition hover:border-[#6759FF] hover:bg-[#F5F7FB]"
                    @click="howItWorksOpen = true"
                >
                    <HelpCircle class="h-4 w-4 text-[#4C42DE]" /> How it works
                </button>
            </header>

            <nav
                class="flex w-full gap-2"
                aria-label="Interview Center sections"
                role="tablist"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    role="tab"
                    :data-test="'interview-tab-' + tab"
                    class="min-w-24 rounded-lg border px-5 py-2.5 text-sm font-semibold capitalize transition"
                    :class="
                        activeTab === tab
                            ? 'border-[#03162E] bg-[#03162E] text-white shadow-sm'
                            : 'border-[#E7ECF3] bg-white text-[#14213D] hover:border-[#A9B8D5]'
                    "
                    :aria-selected="activeTab === tab"
                    @click="activeTab = tab"
                >
                    {{ tab }}
                </button>
            </nav>

            <div
                v-if="activeTab === 'prepare'"
                class="grid gap-5 xl:grid-cols-[minmax(0,1.42fr)_minmax(340px,1fr)]"
            >
                <main class="min-w-0 space-y-5">
                    <section
                        id="interview-setup"
                        data-test="interview-setup"
                        class="jobflow-surface scroll-mt-6 overflow-hidden p-5 md:p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2
                                    class="flex items-center gap-3 text-base font-semibold text-[#07162F]"
                                >
                                    <Sparkles
                                        class="h-6 w-6 text-[#7A6CFF] drop-shadow-[0_2px_4px_rgba(122,108,255,0.25)]"
                                    />
                                    Setup your interview
                                </h2>
                            </div>
                        </div>

                        <div
                            v-if="activeSession"
                            class="mt-5 flex flex-col gap-4 rounded-2xl border border-[#BFD3EC] bg-[#F4F8FD] p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-start gap-3">
                                <PlayCircle
                                    class="mt-0.5 h-5 w-5 shrink-0 text-[#3157D5]"
                                />
                                <div>
                                    <p
                                        class="text-sm font-semibold text-[#14213D]"
                                    >
                                        Active AI Interview
                                    </p>
                                    <p class="mt-0.5 text-xs text-[#667085]">
                                        {{
                                            activeSession.resume?.title ||
                                            'Resume'
                                        }}
                                        <template v-if="activeSession.work_job">
                                            · {{ activeSession.work_job.title }}
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    :href="
                                        interviewSessionShow.url(
                                            activeSession.id,
                                        )
                                    "
                                    class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0B2F66] px-3 text-xs font-semibold text-white transition hover:bg-[#123B7A]"
                                >
                                    Continue
                                </Link>
                                <form
                                    :action="
                                        interviewSessionComplete.url(
                                            activeSession.id,
                                        )
                                    "
                                    method="POST"
                                    @submit="handleCompleteInterviewSubmit"
                                >
                                    <input
                                        type="hidden"
                                        name="_token"
                                        :value="$page.props.csrf_token"
                                    />
                                    <button
                                        type="submit"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[#D7DEE8] bg-white px-3 text-xs font-semibold text-[#0A2E48] disabled:opacity-50"
                                        :disabled="isCompletingInterview"
                                    >
                                        <Loader2
                                            v-if="isCompletingInterview"
                                            class="h-3.5 w-3.5 animate-spin"
                                        />
                                        <CheckCircle2
                                            v-else
                                            class="h-3.5 w-3.5"
                                        />
                                        End Interview
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-8 grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    for="interview-resume"
                                    class="flex items-center gap-2 text-sm font-semibold text-[#14213D]"
                                >
                                    Resume
                                </label>
                                <Select
                                    v-model="resumeId"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger
                                        id="interview-resume"
                                        data-test="interview-resume-select"
                                        class="h-11 w-full border-[#E3E8F2] bg-white text-xs shadow-[0_2px_6px_rgba(7,31,73,0.025)]"
                                    >
                                        <FileText
                                            class="h-4 w-4 shrink-0 text-[#52658B]"
                                        />
                                        <SelectValue
                                            placeholder="Select a resume"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="resume in resumes"
                                            :key="resume.id"
                                            :value="String(resume.id)"
                                        >
                                            {{ resume.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="resumes.length === 0"
                                    class="text-xs text-red-600"
                                >
                                    Create a resume first to personalize your
                                    interview.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label
                                    for="interview-job"
                                    class="flex items-center gap-2 text-sm font-semibold text-[#14213D]"
                                >
                                    Job / Application
                                </label>
                                <Select
                                    v-model="workJobId"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger
                                        id="interview-job"
                                        data-test="interview-job-select"
                                        class="h-11 w-full border-[#E3E8F2] bg-white text-xs shadow-[0_2px_6px_rgba(7,31,73,0.025)]"
                                    >
                                        <BriefcaseBusiness
                                            class="h-4 w-4 shrink-0 text-[#52658B]"
                                        />
                                        <SelectValue
                                            placeholder="General Interview"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            General Interview (no specific job)
                                        </SelectItem>
                                        <SelectItem
                                            v-for="application in applications"
                                            :key="application.id"
                                            :value="
                                                String(application.work_job_id)
                                            "
                                        >
                                            {{ application.work_job?.title }} ·
                                            {{ application.work_job?.company }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div
                            class="mt-7 grid gap-5 md:grid-cols-[1.08fr_1fr_1.05fr]"
                        >
                            <div class="space-y-2">
                                <label
                                    for="interview-type"
                                    class="text-sm font-semibold text-[#14213D]"
                                >
                                    Interview Type
                                </label>
                                <Select
                                    v-model="interviewType"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger
                                        id="interview-type"
                                        data-test="interview-type-select"
                                        class="h-11 w-full border-[#E3E8F2] bg-white text-xs"
                                        :title="
                                            selectedInterviewType?.description
                                        "
                                    >
                                        <Sparkles
                                            class="h-4 w-4 shrink-0 text-[#52658B]"
                                        />
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in interviewTypes"
                                            :key="type.id"
                                            :value="type.id"
                                        >
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label
                                    for="interview-difficulty"
                                    class="text-sm font-semibold text-[#14213D]"
                                >
                                    Difficulty
                                </label>
                                <Select
                                    v-model="complexity"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger
                                        id="interview-difficulty"
                                        data-test="interview-difficulty-select"
                                        class="h-11 w-full border-[#E3E8F2] bg-white text-xs"
                                        :title="selectedComplexity?.description"
                                    >
                                        <Signal
                                            class="h-4 w-4 shrink-0 text-[#52658B]"
                                        />
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="level in complexities"
                                            :key="level.id"
                                            :value="level.id"
                                        >
                                            {{ level.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <fieldset
                                class="space-y-2"
                                :disabled="!!activeSession"
                            >
                                <legend
                                    class="text-sm font-semibold text-[#14213D]"
                                >
                                    Mode
                                </legend>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        data-test="interview-mode-text"
                                        :aria-pressed="interviewMode === 'text'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-lg border px-2 text-xs font-medium transition"
                                        :class="
                                            interviewMode === 'text'
                                                ? 'border-[#7663FF] bg-white text-[#6759FF] shadow-[0_0_0_1px_rgba(118,99,255,0.06)]'
                                                : 'border-[#E3E8F2] bg-white text-[#52658B] hover:border-[#A9B8D5]'
                                        "
                                        @click="interviewMode = 'text'"
                                    >
                                        <MessageSquareText class="h-4 w-4" />
                                        Text
                                    </button>
                                    <button
                                        type="button"
                                        data-test="interview-mode-voice"
                                        :aria-pressed="interviewMode === 'live'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-lg border px-2 text-xs font-medium transition"
                                        :class="
                                            interviewMode === 'live'
                                                ? 'border-[#7663FF] bg-white text-[#6759FF] shadow-[0_0_0_1px_rgba(118,99,255,0.06)]'
                                                : 'border-[#E3E8F2] bg-white text-[#52658B] hover:border-[#A9B8D5]'
                                        "
                                        @click="interviewMode = 'live'"
                                    >
                                        <Mic class="h-4 w-4" /> Voice
                                    </button>
                                </div>
                            </fieldset>
                        </div>

                        <div class="mt-9 grid gap-4 sm:grid-cols-2">
                            <button
                                type="button"
                                data-test="prepare-with-ai"
                                class="group relative flex min-h-48 items-center gap-4 overflow-hidden rounded-xl border border-[#DBDAFF] bg-gradient-to-br from-[#F7F7FF] via-white to-[#F1F2FF] p-5 text-left shadow-[0_5px_15px_rgba(112,71,235,0.06)] transition hover:-translate-y-0.5 hover:border-[#ABA1FF] hover:shadow-[0_12px_30px_rgba(112,71,235,0.12)] disabled:pointer-events-none disabled:opacity-50"
                                :disabled="!!activeSession || !resumeId"
                                @click="prepareWithAi"
                            >
                                <span
                                    class="absolute -bottom-5 -left-5 h-32 w-32 rounded-full bg-blue-200/35 blur-2xl"
                                    aria-hidden="true"
                                />
                                <span
                                    class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-[20px] border border-white bg-gradient-to-br from-violet-200 via-violet-100 to-sky-200 text-[#8657E8] shadow-[0_8px_18px_rgba(132,87,230,0.20),inset_0_1px_3px_white]"
                                >
                                    <BrainCircuit
                                        class="h-9 w-9 drop-shadow-[0_1px_2px_white]"
                                        :stroke-width="1.4"
                                    />
                                </span>
                                <span class="relative min-w-0 flex-1">
                                    <span
                                        class="block text-base font-semibold text-[#07162F]"
                                        >Prepare with AI</span
                                    >
                                    <span
                                        class="mt-3 block text-xs leading-6 text-[#263D63]"
                                        >Get personalized tips, likely questions
                                        and expert guidance.</span
                                    >
                                </span>
                                <span
                                    class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#A596FF] to-[#6759FF] text-white shadow-[0_5px_12px_rgba(112,71,235,0.2)]"
                                >
                                    <ArrowRight
                                        class="h-4 w-4 transition group-hover:translate-x-0.5"
                                    />
                                </span>
                            </button>

                            <button
                                type="button"
                                data-test="start-ai-interview"
                                class="group relative flex min-h-48 items-center gap-4 overflow-hidden rounded-xl border border-[#25365C] bg-gradient-to-br from-[#102044] via-[#03162E] to-[#001225] p-5 text-left text-white shadow-[0_6px_18px_rgba(28,35,79,0.12),inset_0_1px_10px_rgba(133,151,227,0.18)] transition hover:-translate-y-0.5 hover:border-[#7663FF] disabled:pointer-events-none disabled:opacity-50"
                                :disabled="
                                    form.processing ||
                                    !!activeSession ||
                                    !resumeId
                                "
                                @click="startAiInterview"
                            >
                                <span
                                    class="absolute -bottom-12 -left-12 h-36 w-36 rounded-full bg-[#7545FC]/20 blur-3xl"
                                />
                                <span
                                    class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-full border border-[#5856FC] bg-[#051B42] text-[#6981FF] shadow-[0_0_14px_rgba(105,73,255,0.3),inset_0_-4px_10px_rgba(115,41,242,0.3)]"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="h-7 w-7 animate-spin"
                                    />
                                    <Mic
                                        v-else
                                        class="h-8 w-8 drop-shadow-[0_0_5px_rgba(70,161,255,0.8)]"
                                        :stroke-width="1.6"
                                    />
                                </span>
                                <span class="relative min-w-0 flex-1">
                                    <span class="block text-base font-semibold">
                                        {{
                                            form.processing
                                                ? 'Starting...'
                                                : 'Start AI Interview'
                                        }}
                                    </span>
                                    <span
                                        class="mt-3 block text-xs leading-6 text-slate-200"
                                        >Practice real interview questions and
                                        get feedback.</span
                                    >
                                </span>
                                <span
                                    class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#25456F] text-[#9CCEFF]"
                                >
                                    <ArrowRight
                                        class="h-4 w-4 transition group-hover:translate-x-0.5"
                                    />
                                </span>
                            </button>
                        </div>

                        <div
                            class="mt-8 flex items-center gap-3 rounded-lg border border-[#E8ECF7] bg-[#F7F8FE] px-4 py-3 text-xs text-[#52658B]"
                        >
                            <Sparkles class="h-4 w-4 shrink-0 text-[#6759FF]" />
                            AI tailors everything to your resume, role and
                            goals.
                        </div>
                    </section>
                </main>

                <aside class="min-w-0 space-y-5">
                    <section class="jobflow-surface p-5">
                        <div
                            class="flex items-center justify-between gap-3 border-b border-[#EDF0F7] pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-base font-semibold text-[#14213D]"
                                >
                                    Upcoming Interviews
                                </h2>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-semibold text-[#3157D5] hover:text-[#0B2F66]"
                                @click="activeTab = 'upcoming'"
                            >
                                View all
                            </button>
                        </div>
                        <div v-if="upcomingPreview.length" class="mt-0">
                            <UpcomingInterviewRow
                                v-for="interview in upcomingPreview"
                                :key="interview.id"
                                :interview="interview"
                                @prepare="prepareForUpcoming"
                            />
                        </div>
                        <div v-else class="mt-4 rounded-2xl bg-[#F5F7FB] p-4">
                            <p class="text-sm font-semibold text-[#14213D]">
                                No upcoming interviews.
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-[#667085]"
                            >
                                Apply to jobs to see scheduled interviews here.
                            </p>
                        </div>
                    </section>

                    <section class="jobflow-surface p-5">
                        <div
                            class="flex items-center justify-between gap-3 border-b border-[#EDF0F7] pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-base font-semibold text-[#14213D]"
                                >
                                    Recent Interviews
                                </h2>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-semibold text-[#3157D5] hover:text-[#0B2F66]"
                                @click="activeTab = 'history'"
                            >
                                View all history
                            </button>
                        </div>
                        <div v-if="recentSessions.length" class="mt-0">
                            <InterviewHistoryRow
                                v-for="session in recentSessions"
                                :key="session.id"
                                :session="session"
                                @delete="requestDelete"
                            />
                        </div>
                        <div v-else class="mt-4 rounded-2xl bg-[#F5F7FB] p-4">
                            <p class="text-sm font-semibold text-[#14213D]">
                                No completed AI interviews yet.
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-[#667085]"
                            >
                                Start your first AI interview and your results
                                will appear here.
                            </p>
                        </div>
                    </section>
                </aside>
            </div>

            <section
                v-else-if="activeTab === 'upcoming'"
                data-test="upcoming-interviews-panel"
                class="jobflow-surface p-5 md:p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="jobflow-section-title">
                            Upcoming Interviews
                        </h2>
                        <p class="mt-1 text-sm text-[#667085]">
                            Prepare using the resume and vacancy connected to
                            each interview.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="activeTab = 'prepare'"
                    >
                        Back to setup
                    </Button>
                </div>
                <div
                    v-if="upcomingInterviews.length"
                    class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                >
                    <UpcomingInterviewRow
                        v-for="interview in upcomingInterviews"
                        :key="interview.id"
                        :interview="interview"
                        @prepare="prepareForUpcoming"
                    />
                </div>
                <div v-else class="mt-6 rounded-2xl bg-[#F5F7FB] p-5">
                    <p class="font-semibold text-[#14213D]">
                        No upcoming interviews.
                    </p>
                    <p class="mt-1 text-sm text-[#667085]">
                        Apply to jobs to see scheduled interviews here.
                    </p>
                </div>
            </section>

            <section
                v-else
                data-test="interview-history-panel"
                class="jobflow-surface p-5 md:p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="jobflow-section-title">Interview History</h2>
                        <p class="mt-1 text-sm text-[#667085]">
                            Review feedback or remove completed AI interview
                            sessions.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="activeTab = 'prepare'"
                    >
                        Back to setup
                    </Button>
                </div>

                <InfiniteScroll
                    v-if="visiblePastSessions.length"
                    data="pastSessions"
                    manual
                >
                    <div class="mt-6 space-y-3">
                        <InterviewHistoryRow
                            v-for="session in visiblePastSessions"
                            :key="session.id"
                            :session="session"
                            @delete="requestDelete"
                        />
                    </div>
                    <template #next="{ loading, fetch, hasMore }">
                        <Button
                            v-if="hasMore"
                            variant="outline"
                            class="mt-5 w-full"
                            :disabled="loading"
                            @click="fetch"
                        >
                            {{ loading ? 'Loading...' : 'Load more' }}
                        </Button>
                    </template>
                </InfiniteScroll>
                <div v-else class="mt-6 rounded-2xl bg-[#F5F7FB] p-5">
                    <p class="font-semibold text-[#14213D]">
                        No completed AI interviews yet.
                    </p>
                    <p class="mt-1 text-sm text-[#667085]">
                        Start your first AI interview and your results will
                        appear here.
                    </p>
                </div>
            </section>
        </div>

        <Dialog v-model:open="howItWorksOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>How Interview Center works</DialogTitle>
                    <DialogDescription>
                        One setup powers preparation, practice and results.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="flex gap-3 rounded-xl bg-[#F5F7FB] p-3">
                        <Sparkles
                            class="mt-0.5 h-4 w-4 shrink-0 text-[#7047EB]"
                        />
                        <div>
                            <p class="text-sm font-semibold text-[#14213D]">
                                Prepare with AI
                            </p>
                            <p class="mt-0.5 text-xs text-[#667085]">
                                Get coaching before your interview.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 rounded-xl bg-[#F5F7FB] p-3">
                        <Mic class="mt-0.5 h-4 w-4 shrink-0 text-[#3157D5]" />
                        <div>
                            <p class="text-sm font-semibold text-[#14213D]">
                                AI Interview
                            </p>
                            <p class="mt-0.5 text-xs text-[#667085]">
                                Practice a realistic interview without hints.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 rounded-xl bg-[#F5F7FB] p-3">
                        <MessageSquareText
                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                        />
                        <div>
                            <p class="text-sm font-semibold text-[#14213D]">
                                Results
                            </p>
                            <p class="mt-0.5 text-xs text-[#667085]">
                                Review feedback after completion.
                            </p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete this interview?</DialogTitle>
                    <DialogDescription>
                        This will permanently delete this interview session and
                        its saved results. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="deletingSessionId !== null"
                        @click="deleteDialogOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        class="bg-red-600 text-white hover:bg-red-700"
                        :disabled="deletingSessionId !== null"
                        @click="confirmDelete"
                    >
                        <Loader2
                            v-if="deletingSessionId !== null"
                            class="h-4 w-4 animate-spin"
                        />
                        Delete Interview
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
