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
import { ref } from 'vue';
import {
    store as interviewSessionStore,
    show as interviewSessionShow,
    complete as interviewSessionComplete,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
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
import { interviewPreparation } from '@/routes';

const props = defineProps<{
    activeSession?: {
        id: number;
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
}>();

const interviewType = ref('resume-based');
const complexity = ref('advanced');
const resumeId = ref<string>(
    props.resumes.length > 0 ? String(props.resumes[0].id) : '',
);
const workJobId = ref<string>('none');

const interviewTypes = [
    { id: 'behavioral', label: 'General Behavioral Questions' },
    { id: 'technical', label: 'Technical Interview' },
    { id: 'case-study', label: 'Case Study & Problem Solving' },
    {
        id: 'resume-based',
        label: 'AI-generated questions based on your resume & job description',
    },
];

const complexities = [
    { id: 'beginner', label: 'Beginner' },
    { id: 'intermediate', label: 'Intermediate' },
    { id: 'advanced', label: 'Advanced' },
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

    <div class="container mx-auto max-w-[1400px] px-5 py-8 font-sans">
        <div class="mb-8">
            <h1
                class="mb-6 text-sm font-bold text-slate-900 dark:text-slate-100"
            >
                Interview Preparation with AI
            </h1>

            <div class="flex items-start justify-between">
                <div>
                    <h2
                        class="mb-2 text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        Get Ready for Your Interview with AI
                    </h2>
                    <p class="text-lg text-slate-600 dark:text-slate-400">
                        Practice real interview questions, get AI feedback, and
                        boost your confidence!
                    </p>
                </div>
                <Sparkles class="h-6 w-6 text-primary" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[350px_1fr]">
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
                            You have an ongoing {{ activeSession.complexity }}
                            {{ activeSession.type }} interview.
                        </p>
                        <Link
                            :href="interviewSessionShow.url(activeSession.id)"
                            class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            Continue Interview
                        </Link>
                        <form
                            :action="
                                interviewSessionComplete.url(activeSession.id)
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
                                Which resume should the AI use?
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
                                You don't have any resumes yet. Create one first
                                to personalize your interview.
                            </p>
                        </div>

                        <div>
                            <h3
                                class="mb-3 text-lg leading-tight font-bold text-slate-900 dark:text-slate-100"
                            >
                                Which job application is this for?
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
                                        :value="String(application.work_job_id)"
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
                            Choosing the type of interview (by vacancy)
                        </h3>

                        <RadioGroup
                            v-model="interviewType"
                            class="space-y-4"
                            :disabled="!!activeSession"
                        >
                            <div
                                v-for="type in interviewTypes"
                                :key="type.id"
                                class="flex items-start gap-3"
                            >
                                <RadioGroupItem
                                    :id="type.id"
                                    :value="type.id"
                                    class="mt-1"
                                />
                                <Label
                                    :for="type.id"
                                    class="cursor-pointer text-[15px] leading-snug font-medium text-slate-700 dark:text-slate-300"
                                    :class="{ 'opacity-50': !!activeSession }"
                                >
                                    {{ type.label }}
                                </Label>
                            </div>
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
                            Choosing the complexity of the questions
                        </h3>

                        <RadioGroup
                            v-model="complexity"
                            class="space-y-4"
                            :disabled="!!activeSession"
                        >
                            <div
                                v-for="level in complexities"
                                :key="level.id"
                                class="flex items-center gap-3"
                            >
                                <RadioGroupItem
                                    :id="level.id"
                                    :value="level.id"
                                />
                                <Label
                                    :for="level.id"
                                    class="cursor-pointer text-[15px] font-medium text-slate-700 dark:text-slate-300"
                                    :class="{ 'opacity-50': !!activeSession }"
                                >
                                    {{ level.label }}
                                </Label>
                            </div>
                        </RadioGroup>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Live Interview + Past Sessions -->
            <div class="space-y-6">
                <Card
                    class="self-start overflow-hidden rounded-[24px] border-0 bg-transparent py-0 shadow-none"
                >
                    <CardContent class="flex flex-col items-start p-0">
                        <button
                            @click="startInterview"
                            :disabled="
                                form.processing || !!activeSession || !resumeId
                            "
                            class="group relative flex w-full max-w-md flex-col items-center justify-center gap-3 overflow-hidden rounded-[24px] border border-primary/20 bg-gradient-to-br from-primary/10 via-white to-primary/5 p-6 text-center shadow-sm transition-all hover:border-primary/40 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50 dark:border-primary/20 dark:from-primary/15 dark:via-slate-950 dark:to-slate-950"
                        >
                            <div
                                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_center,theme(colors.primary.DEFAULT/8%),transparent_70%)] opacity-0 transition-opacity group-hover:opacity-100"
                            />

                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform duration-300 group-hover:scale-110"
                            >
                                <Loader2
                                    v-if="form.processing"
                                    class="h-6 w-6 animate-spin"
                                />
                                <Mic v-else class="h-6 w-6" />
                            </div>

                            <div>
                                <h4
                                    class="mb-1 text-lg font-bold text-slate-900 dark:text-slate-100"
                                >
                                    Live AI Interview
                                </h4>
                                <p
                                    class="mx-auto max-w-sm text-xs leading-relaxed text-slate-600 dark:text-slate-400"
                                >
                                    The AI asks questions, you respond with your
                                    voice, and the system analyzes your tone,
                                    pauses, and confidence in real time.
                                </p>
                            </div>

                            <div
                                class="flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition-transform group-hover:scale-105 dark:bg-slate-100 dark:text-slate-900"
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

                <!-- Past Sessions -->
                <Card
                    v-if="pastSessions.data && pastSessions.data.length > 0"
                    class="w-full max-w-md rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
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
                                        {{ stringForHuman(session.complexity) }}
                                        &bull;
                                        {{ stringForHuman(session.type) }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{
                                            new Date(
                                                session.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </div>
                                    <Link
                                        :href="
                                            interviewSessionShow.url(session.id)
                                        "
                                        class="mt-1 text-xs text-primary hover:underline"
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
                                    {{ loading ? 'Loading...' : 'Load More' }}
                                </Button>
                            </template>
                        </InfiniteScroll>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
