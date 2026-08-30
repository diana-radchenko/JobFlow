<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { ArrowRight, Loader2, Sparkles } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { stringForHuman } from '@/helpers/strings';
import { guidance as interviewPrepGuidance } from '@/actions/App/Http/Controllers/InterviewPrepController';
import { store as interviewSessionStore } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { interviewPreparation } from '@/routes';

const props = defineProps<{
    context: {
        type: string;
        complexity: string;
        mode: 'text' | 'live';
        resume_id: number;
        work_job_id?: number | null;
        resume_title: string;
        job_title?: string | null;
        company?: string | null;
    };
}>();

type PrepState = 'idle' | 'preparing' | 'ready' | 'error';

const page = usePage();
const state = ref<PrepState>('idle');
const practiceAnswer = ref('');
const guidance = ref('');
const errorMessage = ref('');
const startForm = useForm({
    type: props.context.type,
    complexity: props.context.complexity,
    mode: props.context.mode,
    resume_id: props.context.resume_id,
    work_job_id: props.context.work_job_id ?? '',
});

const guidanceHtml = computed(() =>
    DOMPurify.sanitize(
        marked.parse(guidance.value, { async: false }) as string,
    ),
);

async function prepare(): Promise<void> {
    if (state.value === 'preparing') {
        return;
    }

    state.value = 'preparing';
    errorMessage.value = '';

    try {
        const response = await fetch(interviewPrepGuidance.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token as string,
            },
            body: JSON.stringify({
                ...props.context,
                practice_answer: practiceAnswer.value.trim() || null,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Preparation failed.');
        }

        guidance.value = data.guidance;
        state.value = 'ready';
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : "We couldn't prepare your interview guidance.";
        state.value = 'error';
    }
}

function startAiInterview(): void {
    startForm.post(interviewSessionStore.url());
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interview', href: interviewPreparation() },
            { title: 'AI Interview Prep' },
        ],
    },
});
</script>

<template>
    <Head title="AI Interview Prep" />

    <div class="jobflow-page mx-auto max-w-5xl font-sans">
        <div class="mb-7">
            <p class="mb-2 text-sm font-semibold text-[#7047EB]">
                AI Interview Prep
            </p>
            <h1 class="jobflow-page-title">Prepare before your AI interview</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                Get coaching based on your selected resume and vacancy. Formal
                scoring happens only after the AI interview.
            </p>
        </div>

        <Card class="mb-6 rounded-2xl border-slate-200 bg-white shadow-sm">
            <CardContent class="grid gap-4 p-6 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">
                        Resume
                    </p>
                    <p class="mt-1 font-semibold">{{ context.resume_title }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">
                        Vacancy
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ context.job_title || 'General interview' }}
                    </p>
                    <p v-if="context.company" class="text-sm text-slate-500">
                        {{ context.company }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">
                        Interview
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ stringForHuman(context.type) }} ·
                        {{ stringForHuman(context.complexity) }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                <CardContent class="p-6">
                    <h2 class="text-lg font-bold text-slate-900">
                        Practice workspace
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Optionally paste a practice answer for targeted
                        coaching.
                    </p>
                    <Textarea
                        v-model="practiceAnswer"
                        class="mt-5 min-h-44"
                        placeholder="Paste a practice answer, or leave blank for a preparation plan..."
                    />
                    <Button
                        class="mt-4 w-full gap-2"
                        :disabled="state === 'preparing'"
                        @click="prepare"
                    >
                        <Loader2
                            v-if="state === 'preparing'"
                            class="h-4 w-4 animate-spin"
                        />
                        <Sparkles v-else class="h-4 w-4" />
                        {{ guidance ? 'Refresh Guidance' : 'Prepare with AI' }}
                    </Button>
                    <Button
                        variant="outline"
                        class="mt-3 w-full gap-2"
                        :disabled="startForm.processing"
                        @click="startAiInterview"
                    >
                        Start AI Interview <ArrowRight class="h-4 w-4" />
                    </Button>
                </CardContent>
            </Card>

            <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                <CardContent class="min-h-80 p-6">
                    <h2 class="text-lg font-bold text-slate-900">
                        Your preparation guide
                    </h2>
                    <div
                        v-if="state === 'preparing'"
                        class="mt-12 flex flex-col items-center gap-3 text-slate-500"
                    >
                        <Loader2 class="h-7 w-7 animate-spin" />
                        <p>Preparing focused guidance...</p>
                    </div>
                    <div
                        v-else-if="state === 'error'"
                        class="mt-8 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800"
                    >
                        <p>{{ errorMessage }}</p>
                        <Button variant="outline" class="mt-4" @click="prepare"
                            >Try Again</Button
                        >
                    </div>
                    <div
                        v-else-if="guidance"
                        class="prose prose-slate mt-5 max-w-none text-sm"
                        v-html="guidanceHtml"
                    />
                    <p v-else class="mt-5 text-sm text-slate-500">
                        Generate a focused preparation plan or paste an answer
                        to receive coaching before you start.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
