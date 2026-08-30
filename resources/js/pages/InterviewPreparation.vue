<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    BriefcaseBusiness,
    CheckCircle2,
    ChevronRight,
    FileText,
    HelpCircle,
    History,
    Info,
    Loader2,
    MessageSquareText,
    Mic,
    PlayCircle,
    Sparkles,
    Target,
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

    <div class="jobflow-page font-sans dark:bg-slate-950">
        <div class="jobflow-page-frame">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p
                        class="text-[11px] font-semibold tracking-[0.16em] text-[#3157D5] uppercase"
                    >
                        AI-powered practice
                    </p>
                    <h1 class="jobflow-page-title mt-1 dark:text-white">
                        Interview Center
                    </h1>
                    <p class="mt-2 text-sm text-[#667085] dark:text-slate-400">
                        Practice smarter. Interview better. Get hired.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-[#D7DEE8] bg-white px-3 text-xs font-semibold text-[#0A2E48] transition hover:border-[#0A2E48] hover:bg-[#F5F7FB]"
                    @click="howItWorksOpen = true"
                >
                    <HelpCircle class="h-4 w-4" /> How it works
                </button>
            </header>

            <nav
                class="flex w-full gap-1 border-b border-[#E2E8F0]"
                aria-label="Interview Center sections"
                role="tablist"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    role="tab"
                    :data-test="'interview-tab-' + tab"
                    class="relative px-4 py-3 text-sm font-semibold capitalize transition"
                    :class="
                        activeTab === tab
                            ? 'text-[#0B2F66] after:absolute after:right-0 after:bottom-[-1px] after:left-0 after:h-0.5 after:bg-[#3157D5]'
                            : 'text-[#667085] hover:text-[#14213D]'
                    "
                    :aria-selected="activeTab === tab"
                    @click="activeTab = tab"
                >
                    {{ tab }}
                </button>
            </nav>

            <div
                v-if="activeTab === 'prepare'"
                class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.72fr)]"
            >
                <main class="min-w-0 space-y-5">
                    <section
                        id="interview-setup"
                        data-test="interview-setup"
                        class="jobflow-surface scroll-mt-6 overflow-hidden p-5 md:p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="jobflow-section-title">
                                    Set up your interview
                                </h2>
                                <p class="mt-1 text-sm text-[#667085]">
                                    Choose your context, then prepare or start a
                                    realistic AI interview.
                                </p>
                            </div>
                            <div
                                class="hidden h-10 w-10 items-center justify-center rounded-xl bg-[#EEF3FA] text-[#0B2F66] sm:flex"
                            >
                                <Target class="h-5 w-5" />
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

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <label
                                    for="interview-resume"
                                    class="flex items-center gap-2 text-sm font-semibold text-[#14213D]"
                                >
                                    <FileText class="h-4 w-4 text-[#3157D5]" />
                                    Resume
                                </label>
                                <Select
                                    v-model="resumeId"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger
                                        id="interview-resume"
                                        data-test="interview-resume-select"
                                        class="w-full"
                                    >
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
                    any }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div
                            class="mt-5 grid gap-5 border-t border-[#E7ECF3] pt-5 md:grid-cols-2 xl:grid-cols-[1.1fr_0.9fr_1fr]"
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
                                        class="w-full"
                                    >
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
                                <p
                                    class="text-xs leading-relaxed text-[#667085]"
                                >
                                    {{ selectedInterviewType?.description }}
                                </p>
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
                                        class="w-full"
                                    >
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
                                <p
                                    class="text-xs leading-relaxed text-[#667085]"
                                >
                                    {{ selectedComplexity?.description }}
                                </p>
                            </div>

                            <fieldset
                                class="space-y-2"
                                :disabled="!!activeSession"
                            >
                                <legend
                                    class="text-sm font-semibold text-[#14213D]"
                                >
                                    Interview Mode
                                </legend>
                                <div
                                    class="grid grid-cols-2 rounded-xl border border-[#D7DEE8] bg-[#F5F7FB] p-1"
                                >
                                    <button
                                        type="button"
                                        data-test="interview-mode-text"
                                        :aria-pressed="interviewMode === 'text'"
                                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                                        :class="
                                            interviewMode === 'text'
                                                ? 'bg-white text-[#0B2F66] shadow-sm'
                                                : 'text-[#667085] hover:text-[#14213D]'
                                        "
                                        @click="interviewMode = 'text'"
                                    >
                                        Text
                                    </button>
                                    <button
                                        type="button"
                                        data-test="interview-mode-voice"
                                        :aria-pressed="interviewMode === 'live'"
                                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                                        :class="
                                            interviewMode === 'live'
                                                ? 'bg-white text-[#0B2F66] shadow-sm'
                                                : 'text-[#667085] hover:text-[#14213D]'
                                        "
                                        @click="interviewMode = 'live'"
                                    >
                                        Voice
                                    </button>
                                </div>
                                <p
                                    class="text-xs leading-relaxed text-[#667085]"
                                >
                                    {{
                                        interviewMode === 'text'
                                            ? 'Type your answers at your own pace.'
                                            : 'Practice speaking your answers aloud.'
                                    }}
                                </p>
                            </fieldset>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <button
                                type="button"
                                data-test="prepare-with-ai"
                                class="group rounded-2xl border border-[#DCE5F2] bg-gradient-to-br from-white to-[#F1EEFF] p-5 text-left transition hover:-translate-y-0.5 hover:border-[#B9C7E5] hover:shadow-[0_12px_30px_rgba(49,87,213,0.10)] disabled:pointer-events-none disabled:opacity-50"
                                :disabled="!!activeSession || !resumeId"
                                @click="prepareWithAi"
                            >
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#7047EB] shadow-sm"
                                >
                                    <Sparkles class="h-5 w-5" />
                                </span>
                                <span
                                    class="mt-4 flex items-center justify-between gap-3 text-base font-semibold text-[#14213D]"
                                >
                                    Prepare with AI
                                    <ArrowRight
                                        class="h-4 w-4 transition group-hover:translate-x-0.5"
                                    />
                                </span>
                                <span
                                    class="mt-1 block text-sm leading-relaxed text-[#667085]"
                                >
                                    Get personalized tips, likely questions and
                                    expert guidance.
                                </span>
                            </button>

                            <button
                                type="button"
                                data-test="start-ai-interview"
                                class="group relative overflow-hidden rounded-2xl bg-[#051C2E] p-5 text-left text-white shadow-[0_14px_34px_rgba(5,28,46,0.22)] transition hover:-translate-y-0.5 hover:bg-[#0A2E48] disabled:pointer-events-none disabled:opacity-50"
                                :disabled="
                                    form.processing ||
                                    !!activeSession ||
                                    !resumeId
                                "
                                @click="startAiInterview"
                            >
                                <span
                                    class="absolute -top-16 -right-16 h-36 w-36 rounded-full bg-[#4F6FEF]/20 blur-3xl"
                                />
                                <span
                                    class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/15"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="h-5 w-5 animate-spin"
                                    />
                                    <Mic v-else class="h-5 w-5" />
                                </span>
                                <span
                                    class="relative mt-4 flex items-center justify-between gap-3 text-base font-semibold"
                                >
                                    {{
                                        form.processing
                                            ? 'Starting...'
                                            : 'Start AI Interview'
                                    }}
                                    <ChevronRight
                                        v-if="!form.processing"
                                        class="h-4 w-4 transition group-hover:translate-x-0.5"
                                    />
                                </span>
                                <span
                                    class="relative mt-1 block text-sm leading-relaxed text-slate-300"
                                >
                                    Practice real interview questions and get
                                    feedback.
                                </span>
                            </button>
                        </div>

                        <div
                            class="mt-4 flex items-center gap-2 rounded-xl bg-[#F5F7FB] px-4 py-3 text-xs text-[#667085]"
                        >
                            <Info class="h-4 w-4 shrink-0 text-[#3157D5]" />
                            AI tailors everything to your resume, role and
                            goals.
                        </div>
                    </section>
                </main>

                <aside class="min-w-0 space-y-5">
                    <section class="jobflow-surface p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#EEF3FA] text-[#0B2F66]"
                                >
                                    <BriefcaseBusiness class="h-4 w-4" />
                                </span>
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
                        <div
                            v-if="upcomingPreview.length"
                            class="mt-4 space-y-3"
                        >
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
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F1EEFF] text-[#7047EB]"
                                >
                                    <History class="h-4 w-4" />
                                </span>
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
                        <div
                            v-if="recentSessions.length"
                            class="mt-4 space-y-3"
                        >
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
