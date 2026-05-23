<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { InfiniteScroll } from '@inertiajs/vue3';
import {
    Mic,
    Type,
    Video,
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
}>();

const interviewType = ref('resume-based');
const complexity = ref('advanced');

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

const interviewModes = [
    {
        id: 'live',
        title: 'Live AI Interview Mode',
        description:
            'The AI asks questions, the user responds with his voice, the system analyzes tone, pauses, and confidence.',
        icon: Mic,
    },
    {
        id: 'text',
        title: 'Text-Based Interview Mode',
        description:
            'The questions and answers are in text format, and AI evaluates the content and style.',
        icon: Type,
    },
    {
        id: 'video',
        title: 'Video Recording Mode',
        description:
            'The user records their responses, and the AI analyzes facial expressions, voice, and speech structure.',
        icon: Video,
    },
];

const form = useForm({
    type: '',
    complexity: '',
    mode: '',
});
const isCompletingInterview = ref(false);

function handleCompleteInterviewSubmit() {
    if (isCompletingInterview.value) {
        return;
    }

    isCompletingInterview.value = true;
}

function startInterview(modeId: string) {
    if (modeId !== 'text') {
        alert('Only Text-Based Interview Mode is currently supported.');

        return;
    }

    if (props.activeSession) {
        alert(
            'You already have an active interview session. Please finish it first.',
        );

        return;
    }

    form.type = interviewType.value;
    form.complexity = complexity.value;
    form.mode = modeId;
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
                    class="rounded-[24px] border-primary/20 bg-primary/5 shadow-none dark:bg-primary/10"
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

                <!-- Past Sessions -->
                <Card
                    v-if="pastSessions.data && pastSessions.data.length > 0"
                    class="rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
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

            <!-- Right Column: Interview Modes -->
            <Card
                class="overflow-hidden rounded-[24px] border-0 bg-slate-100/50 shadow-none dark:bg-slate-900/50"
            >
                <CardContent class="p-4 sm:p-8">
                    <h3
                        class="mb-6 text-lg font-medium text-slate-600 dark:text-slate-400"
                    >
                        AI Interview Modes
                    </h3>

                    <div class="space-y-6">
                        <button
                            v-for="mode in interviewModes"
                            :key="mode.id"
                            @click="startInterview(mode.id)"
                            :disabled="form.processing || !!activeSession"
                            class="group flex w-full flex-col gap-4 rounded-[24px] border border-slate-200/60 bg-white p-5 text-left shadow-sm transition-all hover:border-primary/20 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 sm:flex-row sm:items-center sm:gap-6 sm:rounded-[32px] sm:p-8 dark:border-slate-800 dark:bg-slate-950"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center sm:h-16 sm:w-16"
                            >
                                <component
                                    :is="mode.icon"
                                    class="h-7 w-7 text-slate-900 sm:h-10 sm:w-10 dark:text-slate-100"
                                />
                            </div>

                            <div class="flex-1">
                                <h4
                                    class="mb-2 text-lg font-bold text-slate-900 sm:text-xl dark:text-slate-100"
                                >
                                    {{ mode.title }}
                                </h4>
                                <p
                                    class="max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-400"
                                >
                                    {{ mode.description }}
                                </p>
                            </div>

                            <div
                                class="flex h-10 w-10 items-center justify-center self-end rounded-full bg-slate-900 text-white transition-transform group-hover:scale-110 sm:h-12 sm:w-12 sm:self-auto dark:bg-slate-100 dark:text-slate-900"
                            >
                                <ChevronRight class="h-5 w-5 sm:h-6 sm:w-6" />
                            </div>
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
