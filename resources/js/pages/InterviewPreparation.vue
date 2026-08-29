<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { InfiniteScroll } from '@inertiajs/vue3';
import {
    Mic,
    ChevronRight,
    Sparkles,
    History,
    PlayCircle,
    CheckCircle2,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { stringForHuman } from '@/helpers/strings';
import {
    store as interviewSessionStore,
    show as interviewSessionShow,
    complete as interviewSessionComplete,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
import { interviewPreparation } from '@/routes';

const props = defineProps<{
    activeSession?: {
        id: number;
        work_job_id: number;
        type: string;
        complexity: string;
        created_at: string;
    } | null;
    pastSessions: {
        data: {
            id: number;
            type: string;
            complexity: string;
            created_at: string;
        }[];
    };
    resumes: { id: number; title: string }[];
    applications: {
        id: number;
        work_job_id: number;
        work_job: { id: number; title: string; company: string } | null;
    }[];
    upcomingInterviews: {
        id: number;
        scheduled_at: string;
        timezone: string;
        interview_format: string | null;
        work_job: { title: string; company: string } | null;
    }[];
}>();

const interviewType = ref('resume-based');
const complexity = ref('advanced');
const resumeId = ref<string>(
    props.resumes.length > 0 ? String(props.resumes[0].id) : '',
);
const workJobId = ref<string>('none');
const selectedResume = computed(() =>
    props.resumes.find((resume) => String(resume.id) === resumeId.value),
);
const selectedApplication = computed(() =>
    props.applications.find(
        (application) => String(application.work_job_id) === workJobId.value,
    ),
);

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

const form = useForm({
    type: '',
    complexity: '',
    mode: '',
    resume_id: '',
    work_job_id: '',
});
const isCompletingInterview = ref(false);

function handleCompleteInterviewSubmit() {
    if (isCompletingInterview.value) {
        return;
    }

    isCompletingInterview.value = true;
}

function startInterview() {
    if (props.activeSession) {
        alert(
            'You already have an active interview session. Please finish it first.',
        );

        return;
    }

    if (!resumeId.value) {
        alert('Please select which resume to use for this interview.');

        return;
    }

    form.type = interviewType.value;
    form.complexity = complexity.value;
    form.mode = 'live';
    form.resume_id = resumeId.value;
    form.work_job_id = workJobId.value === 'none' ? '' : workJobId.value;
    form.post(interviewSessionStore.url());
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Interview Preparing',
                href: interviewPreparation(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Interview Preparation" />

    <div class="jobflow-page font-sans">
        <div class="mx-auto max-w-[1400px]">
            <div class="mb-8">
                <h1
                    class="mb-3 text-[13px] font-semibold text-[#7047EB] dark:text-violet-300"
                >
                    Interview Preparation with AI
                </h1>

                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="jobflow-page-title mb-2 dark:text-slate-100">
                            Get Ready for Your Interview with AI
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Practice real interview questions, get AI feedback,
                            and boost your confidence!
                        </p>
                    </div>
                    <Sparkles class="h-6 w-6 text-primary" />
                </div>
                <nav
                    class="mt-6 flex gap-2"
                    aria-label="Interview Center sections"
                >
                    <Button as-child size="sm"
                        ><a href="#prepare">Prepare</a></Button
                    >
                    <Button as-child size="sm" variant="outline"
                        ><a href="#upcoming">Upcoming</a></Button
                    >
                    <Button as-child size="sm" variant="outline"
                        ><a href="#history">History</a></Button
                    >
                </nav>
            </div>

            <div
                id="prepare"
                class="grid scroll-mt-6 grid-cols-1 gap-8 lg:grid-cols-[minmax(360px,0.95fr)_minmax(380px,1.05fr)]"
            >
                <!-- Left Column: Settings -->
                <div class="space-y-6">
                    <!-- Active Session Card -->
                    <Card
                        v-if="activeSession"
                        class="rounded-[24px] border-primary/20 bg-primary/5 py-0 shadow-none dark:bg-primary/10"
                    >
                        <CardContent class="p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <PlayCircle class="h-6 w-6 text-primary" />
                                <h3
                                    class="text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Active Interview
                                </h3>
                            </div>
                            <p
                                class="mb-4 text-sm text-slate-600 dark:text-slate-400"
                            >
                                You have an ongoing
                                {{ activeSession.complexity }}
                                {{ activeSession.type }} interview.
                            </p>
                            <Link
                                :href="
                                    interviewSessionShow.url(activeSession.id)
                                "
                                class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                Continue Interview
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
                                <Button
                                    type="submit"
                                    variant="outline"
                                    class="mt-4 w-full cursor-pointer gap-2 dark:bg-primary/15 dark:hover:bg-primary/25"
                                    :disabled="isCompletingInterview"
                                >
                                    <Loader2
                                        v-if="isCompletingInterview"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    {{
                                        isCompletingInterview
                                            ? 'Processing Completion...'
                                            : 'Complete Interview'
                                    }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <!-- Resume & Job Card -->
                    <Card
                        class="rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
                    >
                        <CardContent class="space-y-6 p-6">
                            <div>
                                <h3
                                    class="mb-3 text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                                >
                                    1. Interview Context — Resume
                                </h3>
                                <Select
                                    v-model="resumeId"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger class="w-full">
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
                                    class="mt-2 text-xs text-slate-500"
                                >
                                    You don't have any resumes yet. Create one
                                    first to personalize your interview.
                                </p>
                            </div>

                            <div>
                                <h3
                                    class="mb-3 text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Interview Context — Job/Application
                                </h3>
                                <Select
                                    v-model="workJobId"
                                    :disabled="!!activeSession"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="General interview"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            General (no specific job)
                                        </SelectItem>
                                        <SelectItem
                                            v-for="application in applications"
                                            :key="application.id"
                                            :value="
                                                String(application.work_job_id)
                                            "
                                        >
                                            {{ application.work_job?.title }}
                                            &bull;
                                            {{ application.work_job?.company }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Interview Type Card -->
                    <Card
                        class="rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
                    >
                        <CardContent class="p-6">
                            <h3
                                class="mb-6 text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                            >
                                2. Interview Type
                            </h3>

                            <RadioGroup
                                v-model="interviewType"
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2"
                                :disabled="!!activeSession"
                            >
                                <Label
                                    v-for="type in interviewTypes"
                                    :key="type.id"
                                    :for="type.id"
                                    class="relative flex min-h-32 cursor-pointer flex-col rounded-2xl border bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm dark:bg-slate-950"
                                    :class="[
                                        interviewType === type.id
                                            ? type.id === 'resume-based'
                                                ? 'border-indigo-500 bg-indigo-50/80 ring-2 ring-indigo-500/20 dark:bg-indigo-950/30'
                                                : 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                            : 'border-slate-200 dark:border-slate-800',
                                        {
                                            'pointer-events-none opacity-50':
                                                !!activeSession,
                                        },
                                    ]"
                                >
                                    <RadioGroupItem
                                        :id="type.id"
                                        :value="type.id"
                                        class="sr-only"
                                    />
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <span
                                            class="font-bold text-slate-900 dark:text-white"
                                            >{{ type.label }}</span
                                        >
                                        <CheckCircle2
                                            v-if="interviewType === type.id"
                                            class="h-5 w-5 shrink-0"
                                            :class="
                                                type.id === 'resume-based'
                                                    ? 'text-indigo-600'
                                                    : 'text-primary'
                                            "
                                        />
                                    </div>
                                    <p
                                        class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400"
                                    >
                                        {{ type.description }}
                                    </p>
                                    <span
                                        v-if="type.id === 'resume-based'"
                                        class="mt-auto pt-3 text-xs font-bold tracking-wide text-indigo-600 uppercase dark:text-indigo-300"
                                    >
                                        AI tailored
                                    </span>
                                </Label>
                            </RadioGroup>
                        </CardContent>
                    </Card>

                    <!-- Complexity Card -->
                    <Card
                        class="rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
                    >
                        <CardContent class="p-6">
                            <h3
                                class="mb-6 text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                            >
                                3. Difficulty
                            </h3>

                            <RadioGroup
                                v-model="complexity"
                                class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3"
                                :disabled="!!activeSession"
                            >
                                <Label
                                    v-for="level in complexities"
                                    :key="level.id"
                                    :for="level.id"
                                    class="relative flex min-h-32 cursor-pointer flex-col rounded-2xl border bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm dark:bg-slate-950"
                                    :class="[
                                        complexity === level.id
                                            ? 'border-primary bg-primary/5 ring-2 ring-primary/15'
                                            : 'border-slate-200 dark:border-slate-800',
                                        {
                                            'pointer-events-none opacity-50':
                                                !!activeSession,
                                        },
                                    ]"
                                >
                                    <RadioGroupItem
                                        :id="level.id"
                                        :value="level.id"
                                        class="sr-only"
                                    />
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <span
                                            class="font-bold text-slate-900 dark:text-white"
                                            >{{ level.label }}</span
                                        >
                                        <CheckCircle2
                                            v-if="complexity === level.id"
                                            class="h-5 w-5 shrink-0 text-primary"
                                        />
                                    </div>
                                    <p
                                        class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400"
                                    >
                                        {{ level.description }}
                                    </p>
                                </Label>
                            </RadioGroup>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Live Interview + Past Sessions -->
                <div class="w-full max-w-xl space-y-6 lg:justify-self-start">
                    <Card
                        class="self-start overflow-hidden rounded-[20px] border border-[#051C2E] bg-[#051C2E] py-0 text-white shadow-md"
                    >
                        <CardContent class="flex flex-col items-start p-0">
                            <button
                                @click="startInterview"
                                :disabled="
                                    form.processing ||
                                    !!activeSession ||
                                    !resumeId
                                "
                                class="group relative flex w-full flex-col items-center justify-center gap-3 overflow-hidden rounded-[24px] border border-white/10 bg-transparent p-6 text-center transition-all hover:border-blue-300/40 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <div
                                    class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_center,theme(colors.primary.DEFAULT/8%),transparent_70%)] opacity-0 transition-opacity group-hover:opacity-100"
                                />

                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-500 text-white shadow-sm transition-transform duration-300 group-hover:scale-105"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="h-6 w-6 animate-spin"
                                    />
                                    <Mic v-else class="h-6 w-6" />
                                </div>

                                <div>
                                    <h4
                                        class="mb-1 text-lg font-bold text-white"
                                    >
                                        Interview Ready
                                    </h4>
                                    <p
                                        class="mx-auto max-w-sm text-sm leading-relaxed text-slate-300"
                                    >
                                        <strong class="block">{{
                                            selectedApplication?.work_job
                                                ?.title || 'General interview'
                                        }}</strong>
                                        <span class="block">{{
                                            selectedApplication?.work_job
                                                ?.company ||
                                            'No vacancy selected'
                                        }}</span>
                                        <span class="mt-2 block"
                                            >Resume:
                                            {{
                                                selectedResume?.title ||
                                                'Not selected'
                                            }}</span
                                        >
                                        <span class="block"
                                            >Type:
                                            {{
                                                interviewTypes.find(
                                                    (item) =>
                                                        item.id ===
                                                        interviewType,
                                                )?.label
                                            }}</span
                                        >
                                        <span class="block"
                                            >Difficulty:
                                            {{
                                                complexities.find(
                                                    (item) =>
                                                        item.id === complexity,
                                                )?.label
                                            }}</span
                                        >
                                    </p>
                                </div>

                                <div
                                    class="flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-[#061E3A] transition-transform group-hover:scale-105"
                                >
                                    {{
                                        form.processing
                                            ? 'Starting...'
                                            : 'Start Interview'
                                    }}
                                    <ChevronRight class="h-4 w-4" />
                                </div>
                            </button>
                        </CardContent>
                    </Card>

                    <Card
                        id="upcoming"
                        class="w-full scroll-mt-6 rounded-[24px] border-slate-200 bg-white shadow-sm"
                    >
                        <CardContent class="p-6">
                            <h3 class="mb-4 text-lg font-bold">
                                Upcoming Interviews
                            </h3>
                            <div
                                v-if="upcomingInterviews.length"
                                class="space-y-3"
                            >
                                <article
                                    v-for="interview in upcomingInterviews"
                                    :key="interview.id"
                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-sm"
                                >
                                    <strong>{{
                                        interview.work_job?.title
                                    }}</strong>
                                    <p class="text-sm text-slate-500">
                                        {{ interview.work_job?.company }}
                                    </p>
                                    <p class="mt-2 text-sm">
                                        {{
                                            new Date(
                                                interview.scheduled_at,
                                            ).toLocaleString('en-US', {
                                                timeZone: interview.timezone,
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            })
                                        }}
                                        · {{ interview.timezone }}
                                    </p>
                                    <p
                                        v-if="interview.interview_format"
                                        class="text-sm"
                                    >
                                        {{
                                            stringForHuman(
                                                interview.interview_format,
                                            )
                                        }}
                                    </p>
                                    <Button
                                        size="sm"
                                        class="mt-3"
                                        @click="
                                            workJobId = String(
                                                interview.work_job_id,
                                            )
                                        "
                                        >Prepare for this interview</Button
                                    >
                                </article>
                            </div>
                            <p v-else class="text-sm text-slate-500">
                                No upcoming employer interviews.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Past Sessions -->
                    <Card
                        id="history"
                        v-if="pastSessions.data && pastSessions.data.length > 0"
                        class="w-full max-w-md scroll-mt-6 rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
                    >
                        <CardContent class="p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <History class="h-5 w-5 text-slate-600" />
                                <h3
                                    class="text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Past Interviews
                                </h3>
                            </div>

                            <InfiniteScroll data="pastSessions" manual>
                                <div class="space-y-3">
                                    <div
                                        v-for="session in pastSessions.data"
                                        :key="session.id"
                                        class="flex flex-col gap-1 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950"
                                    >
                                        <div
                                            class="text-sm font-medium text-slate-900 dark:text-slate-100"
                                        >
                                            {{
                                                stringForHuman(
                                                    session.complexity,
                                                )
                                            }}
                                            &bull;
                                            {{ stringForHuman(session.type) }}
                                        </div>
                                        <div class="text-sm text-slate-500">
                                            {{
                                                new Date(
                                                    session.created_at,
                                                ).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    year: 'numeric',
                                                })
                                            }}
                                        </div>
                                        <Link
                                            :href="
                                                interviewSessionShow.url(
                                                    session.id,
                                                )
                                            "
                                            class="mt-1 text-sm font-medium text-primary hover:underline"
                                        >
                                            Review
                                        </Link>
                                    </div>
                                </div>

                                <template #next="{ loading, fetch, hasMore }">
                                    <Button
                                        v-if="hasMore"
                                        @click="fetch"
                                        :disabled="loading"
                                        variant="outline"
                                        class="mt-4 w-full rounded-xl bg-white dark:bg-slate-950"
                                    >
                                        {{
                                            loading ? 'Loading...' : 'Load More'
                                        }}
                                    </Button>
                                </template>
                            </InfiniteScroll>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
