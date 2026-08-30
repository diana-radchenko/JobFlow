<script setup lang="ts">
import '../../../css/interview-readability.css';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { ArrowRight, Loader2, Mic, Sparkles } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, ref } from 'vue';
import {
    audio as interviewPrepAudio,
    guidance as interviewPrepGuidance,
    transcribe as interviewPrepTranscribe,
} from '@/actions/App/Http/Controllers/InterviewPrepController';
import { store as interviewSessionStore } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { useInterviewVoice } from '@/composables/useInterviewVoice';
import { stringForHuman } from '@/helpers/strings';
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
const voice = useInterviewVoice({
    csrfToken: () => page.props.csrf_token,
    transcribeUrl: () => interviewPrepTranscribe.url(),
    audioUrl: () => interviewPrepAudio.url(),
    context: () => ({ ...props.context }),
    onTranscript: async (text) => {
        practiceAnswer.value = text;
        await prepare();
    },
});
const {
    isListening,
    isStartingListening,
    isTranscribing,
    isPreparingAudio,
    isSpeaking,
    canReplay,
} = voice;
const voiceBusy = computed(
    () =>
        isListening.value ||
        isStartingListening.value ||
        isTranscribing.value ||
        isPreparingAudio.value,
);
const voiceStatus = computed(() =>
    state.value === 'preparing' ? 'Thinking' : voice.status.value,
);
async function toggleRecording() {
    if (isListening.value) {
        voice.stopRecording();
    } else {
        await voice.startRecording();
    }
}
function readGuidance() {
    // Speak plain coaching text, never HTML or Markdown syntax.
    const document = new DOMParser().parseFromString(
        guidanceHtml.value,
        'text/html',
    );
    void voice.speak(document.body.textContent || '');
}

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
    voice.stopAudio();

    try {
        const response = await voice.request(
            interviewPrepGuidance.url(),
            {
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...props.context,
                    practice_answer: practiceAnswer.value.trim() || null,
                }),
            },
            65_000,
        );
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Preparation failed.');
        }

        if (typeof data.guidance !== 'string' || !data.guidance.trim()) {
            throw new Error(
                'No preparation guidance was received. Please try again.',
            );
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
    voice.stopRecording(false);
    voice.stopAudio();
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

    <div class="jobflow-page interview-readability mx-auto max-w-5xl font-sans">
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
                    <div
                        v-if="context.mode === 'live'"
                        class="mt-4 space-y-3 rounded-xl border border-slate-200 p-4"
                    >
                        <p class="text-sm font-semibold" role="status">
                            {{ voiceStatus }}
                        </p>
                        <p class="text-sm text-slate-600">
                            Speak your practice answer in English. Stop
                            recording to receive coaching, not a score. Maximum
                            90 seconds.
                        </p>
                        <Button
                            variant="outline"
                            :disabled="
                                state === 'preparing' ||
                                isStartingListening ||
                                isTranscribing ||
                                isPreparingAudio
                            "
                            :aria-pressed="isListening"
                            @click="toggleRecording"
                        >
                            <Mic class="mr-2 h-4 w-4" />{{
                                isListening
                                    ? 'Stop recording and get coaching'
                                    : 'Start recording'
                            }}
                        </Button>
                        <p
                            v-if="voice.error.value"
                            role="alert"
                            class="text-sm text-red-700"
                        >
                            {{ voice.error.value }}
                        </p>
                        <Button
                            v-if="voice.retryAction.value"
                            variant="outline"
                            :disabled="voiceBusy || state === 'preparing'"
                            @click="voice.retryAction.value?.()"
                            >Try Again</Button
                        >
                    </div>
                    <label
                        for="prep-practice-answer"
                        class="mt-5 block text-sm font-medium"
                        >{{
                            context.mode === 'live'
                                ? 'Transcript / text fallback'
                                : 'Practice answer'
                        }}</label
                    >
                    <Textarea
                        id="prep-practice-answer"
                        v-model="practiceAnswer"
                        class="mt-5 min-h-44"
                        placeholder="Paste a practice answer, or leave blank for a preparation plan..."
                    />
                    <Button
                        class="mt-4 w-full gap-2"
                        :disabled="state === 'preparing' || voiceBusy"
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
                        :disabled="
                            startForm.processing ||
                            state === 'preparing' ||
                            voiceBusy
                        "
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
                        v-if="context.mode === 'live' && guidance"
                        class="mt-3 flex flex-wrap items-center gap-2"
                    >
                        <Button
                            variant="outline"
                            :disabled="voiceBusy || state === 'preparing'"
                            @click="readGuidance"
                            >Read guide aloud</Button
                        >
                        <Button
                            v-if="canReplay"
                            variant="outline"
                            :disabled="voiceBusy"
                            @click="voice.replay"
                            >Play audio</Button
                        >
                        <Button
                            v-if="isSpeaking"
                            variant="outline"
                            @click="voice.stopAudio"
                            >Stop audio</Button
                        >
                        <p class="text-xs text-slate-500">
                            AI-generated voice · text guidance always remains
                            available.
                        </p>
                    </div>
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
