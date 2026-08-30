<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import {
    Bot,
    CheckCircle2,
    Loader2,
    Mic,
    MicOff,
    RotateCcw,
    User,
    Volume2,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
    audio as interviewSessionAudio,
    complete as interviewSessionComplete,
    message as interviewSessionMessage,
    results as interviewSessionResults,
    transcribe as interviewSessionTranscribe,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useInterviewVoice } from '@/composables/useInterviewVoice';
import { stringForHuman } from '@/helpers/strings';
import { interviewPreparation } from '@/routes';

marked.setOptions({ gfm: true, breaks: true });
const props = defineProps<{
    session: {
        id: number;
        type: string;
        complexity: string;
        mode: string;
        status: string;
        created_at: string;
    };
    messages: { role: string; content: string }[];
}>();
const page = usePage();
const chatMessages = ref([...props.messages]);
const messageRefs = ref<HTMLElement[]>([]);
const currentTranscript = ref('');
const recognitionLanguage = ref('en-US');
const recognitionLanguages = [{ value: 'en-US', label: 'English' }];
const isProcessing = ref(false);
const isCompletingInterview = ref(false);
const completed = ref(props.session.status === 'completed');
const messageError = ref('');
const failedMessage = ref<{ text: string; intent: 'start' | 'answer' } | null>(
    null,
);
const voice = useInterviewVoice({
    csrfToken: () => page.props.csrf_token,
    transcribeUrl: () => interviewSessionTranscribe.url(props.session.id),
    audioUrl: () => interviewSessionAudio.url(props.session.id),
    onTranscript: async (text) => {
        currentTranscript.value = text;
        await sendMessage(text);
    },
});
const {
    isListening,
    isStartingListening,
    isTranscribing,
    isPreparingAudio,
    isSpeaking,
    canUseVoiceInput,
    isSecureBrowserContext,
    canReplay,
} = voice;
const liveError = computed(() => messageError.value || voice.error.value);
const statusLabel = computed(() =>
    completed.value
        ? 'Completed'
        : isProcessing.value
          ? 'Thinking'
          : voice.status.value,
);
const hasQuestion = computed(() =>
    chatMessages.value.some((message) => message.role === 'assistant'),
);
const busy = computed(
    () =>
        isProcessing.value ||
        isTranscribing.value ||
        isPreparingAudio.value ||
        isStartingListening.value,
);
const aiInterviewTitle = computed(() => {
    const type = stringForHuman(props.session.type);

    return type.toLowerCase().endsWith('interview')
        ? `${stringForHuman(props.session.complexity)} ${type}`
        : `${stringForHuman(props.session.complexity)} ${type} AI Interview`;
});
function assistantMessageHtml(content: string): string {
    return DOMPurify.sanitize(
        marked.parse(content, { async: false }) as string,
    );
}
watch(
    chatMessages,
    () =>
        nextTick(() =>
            messageRefs.value
                .at(-1)
                ?.scrollIntoView({ behavior: 'smooth', block: 'end' }),
        ),
    { deep: true },
);

async function sendMessage(
    text: string,
    intent: 'start' | 'answer' = 'answer',
): Promise<void> {
    if (
        isProcessing.value ||
        completed.value ||
        (intent === 'answer' && !text.trim())
    ) {
        return;
    }

    voice.stopAudio();
    messageError.value = '';
    voice.error.value = '';
    failedMessage.value = null;
    isProcessing.value = true;
    const userMessage = { role: 'user', content: text.trim() };

    if (intent === 'answer') {
        chatMessages.value.push(userMessage);
    }

    try {
        const response = await voice.request(
            interviewSessionMessage.url(props.session.id),
            {
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text.trim(), intent }),
            },
            65_000,
        );
        const data = await response.json();
        const content =
            typeof data.message === 'string'
                ? data.message
                : data.message?.content;
        const isCompleted = data.session_status === 'completed';

        if (!isCompleted && (typeof content !== 'string' || !content.trim())) {
            throw new Error(
                'No interview response was received. Please try again.',
            );
        }

        if (content) {
            chatMessages.value.push({ role: 'assistant', content });
        }

        currentTranscript.value = '';

        if (isCompleted) {
            completed.value = true;
        } else {
            void voice.speak(content);
        }
    } catch (failure) {
        if (intent === 'answer') {
            const index = chatMessages.value.indexOf(userMessage);

            if (index !== -1) {
                chatMessages.value.splice(index, 1);
            }
        }

        messageError.value =
            failure instanceof Error
                ? failure.message
                : 'The interview request failed. Please try again.';
        failedMessage.value = { text, intent };
    } finally {
        isProcessing.value = false;
    }
}
async function toggleRecording() {
    if (isListening.value) {
        voice.stopRecording();
    } else if (!busy.value && !completed.value && hasQuestion.value) {
        await voice.startRecording();
    }
}
async function retry() {
    if (failedMessage.value) {
        await sendMessage(failedMessage.value.text, failedMessage.value.intent);
    } else if (voice.retryAction.value) {
        await voice.retryAction.value();
    } else {
        await voice.startRecording();
    }
}
function handleCompleteInterviewSubmit(event: Event) {
    if (busy.value || isCompletingInterview.value) {
        event.preventDefault();

        return;
    }

    voice.stopRecording(false);
    voice.stopAudio();
    isCompletingInterview.value = true;
}
onMounted(() => {
    if (!hasQuestion.value && !completed.value) {
        void sendMessage('', 'start');
    }
});
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interview', href: interviewPreparation() },
            { title: 'Voice AI Interview' },
        ],
    },
});
</script>

<template>
    <Head title="Voice AI Interview" />

    <div
        class="container mx-auto grid min-h-[calc(100vh-120px)] max-w-[1200px] gap-6 px-5 py-8 font-sans lg:grid-cols-[1fr_380px]"
    >
        <section class="flex min-h-0 flex-col gap-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Voice AI Interview
                    </p>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        {{ aiInterviewTitle }}
                    </h1>
                </div>

                <div
                    class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                >
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :class="[
                            statusLabel === 'Listening'
                                ? 'animate-pulse bg-green-500'
                                : statusLabel === 'Thinking' ||
                                    statusLabel === 'Transcribing' ||
                                    statusLabel === 'Preparing voice'
                                  ? 'bg-yellow-500'
                                  : statusLabel === 'Speaking'
                                    ? 'animate-pulse bg-blue-500'
                                    : statusLabel === 'Completed'
                                      ? 'bg-slate-400'
                                      : 'bg-primary',
                        ]"
                    />
                    {{ statusLabel }}
                </div>
            </div>

            <div
                class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"
            >
                <div>
                    <span
                        class="font-medium text-slate-900 dark:text-slate-100"
                    >
                        Voice input:
                    </span>
                    <span v-if="canUseVoiceInput">ready</span>
                    <span v-else-if="!isSecureBrowserContext">
                        HTTPS or localhost required
                    </span>
                    <span v-else>
                        Microphone recording not supported in this browser
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="font-medium text-slate-900 dark:text-slate-100"
                    >
                        Speech language
                    </span>
                    <Select
                        v-model="recognitionLanguage"
                        :disabled="isListening"
                    >
                        <SelectTrigger class="w-[150px]">
                            <SelectValue placeholder="Language" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="language in recognitionLanguages"
                                :key="language.value"
                                :value="language.value"
                            >
                                {{ language.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Card
                class="flex min-h-[520px] flex-1 flex-col justify-center rounded-[28px] border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950"
            >
                <CardContent
                    class="flex flex-1 flex-col items-center justify-center gap-8 p-6 text-center sm:p-10"
                >
                    <button
                        type="button"
                        class="flex h-36 w-36 items-center justify-center rounded-full border transition-all duration-200 sm:h-44 sm:w-44"
                        :class="[
                            isListening
                                ? 'border-green-300 bg-green-50 shadow-[0_0_0_18px_rgba(34,197,94,0.12)] dark:border-green-900 dark:bg-green-950/40'
                                : isProcessing ||
                                    isPreparingAudio ||
                                    isTranscribing
                                  ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950/40'
                                  : isSpeaking
                                    ? 'border-blue-300 bg-blue-50 shadow-[0_0_0_18px_rgba(59,130,246,0.12)] dark:border-blue-900 dark:bg-blue-950/40'
                                    : 'border-slate-200 bg-slate-50 hover:border-primary/40 dark:border-slate-800 dark:bg-slate-900',
                        ]"
                        :disabled="busy || completed || !hasQuestion"
                        :aria-label="
                            isListening
                                ? 'Stop recording and send answer'
                                : 'Start recording'
                        "
                        :aria-pressed="isListening"
                        @click="toggleRecording"
                    >
                        <Loader2
                            v-if="
                                isProcessing ||
                                isPreparingAudio ||
                                isTranscribing
                            "
                            class="h-16 w-16 animate-spin text-yellow-600"
                        />
                        <Volume2
                            v-else-if="isSpeaking"
                            class="h-16 w-16 text-blue-600"
                        />
                        <MicOff
                            v-else-if="isListening"
                            class="h-16 w-16 text-green-600"
                        />
                        <Mic v-else class="h-16 w-16 text-primary" />
                    </button>

                    <div class="max-w-2xl space-y-3">
                        <h2
                            class="text-xl font-bold text-slate-900 dark:text-slate-100"
                        >
                            {{
                                completed
                                    ? 'Interview completed'
                                    : isStartingListening
                                      ? 'Waiting for microphone permission'
                                      : isListening
                                        ? 'Listening to your answer'
                                        : isTranscribing
                                          ? 'Transcribing your answer'
                                          : isProcessing
                                            ? 'AI is preparing the next response'
                                            : isPreparingAudio
                                              ? 'AI is preparing the voice'
                                              : isSpeaking
                                                ? 'AI is speaking'
                                                : 'Ready for your answer'
                            }}
                        </h2>
                        <p
                            v-if="!completed"
                            class="text-sm text-slate-500 dark:text-slate-400"
                        >
                            Click the microphone, allow access, and speak. Click
                            again to send. Recordings stop after 90 seconds. The
                            interviewer's voice is AI-generated.
                        </p>
                    </div>

                    <div
                        class="min-h-28 w-full max-w-3xl rounded-2xl border border-slate-200 bg-slate-50 p-5 text-left text-sm leading-relaxed text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                    >
                        <span v-if="currentTranscript">
                            {{ currentTranscript }}
                        </span>
                        <span v-else class="text-slate-400">
                            Speak in English. Your transcribed answer will
                            appear here.
                        </span>
                    </div>

                    <p
                        v-if="liveError"
                        role="alert"
                        class="max-w-2xl rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400"
                    >
                        {{ liveError }}
                    </p>
                    <Button
                        v-if="canReplay && !completed"
                        variant="outline"
                        :disabled="isListening || busy"
                        @click="voice.replay"
                    >
                        Play audio
                    </Button>
                    <div
                        v-if="!completed"
                        class="w-full max-w-3xl space-y-3 text-left"
                    >
                        <label
                            for="voice-text-answer"
                            class="text-sm font-medium"
                            >Or type your answer</label
                        >
                        <Textarea
                            id="voice-text-answer"
                            v-model="currentTranscript"
                            :disabled="busy || isListening || !hasQuestion"
                            placeholder="Type your answer if your microphone is unavailable..."
                        />
                        <Button
                            :disabled="
                                busy ||
                                isListening ||
                                !hasQuestion ||
                                !currentTranscript.trim()
                            "
                            @click="sendMessage(currentTranscript)"
                            >Send answer</Button
                        >
                    </div>
                    <Button
                        v-else
                        @click="
                            router.visit(
                                interviewSessionResults.url(session.id),
                            )
                        "
                        >View Interview Results</Button
                    >
                </CardContent>

                <CardFooter
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:justify-between dark:border-slate-800"
                >
                    <div class="flex w-full gap-3 sm:w-auto">
                        <Button
                            v-if="liveError && !completed"
                            type="button"
                            variant="outline"
                            class="flex-1 gap-2 sm:flex-none"
                            :disabled="
                                isListening ||
                                isProcessing ||
                                isPreparingAudio ||
                                isTranscribing ||
                                completed
                            "
                            @click="retry"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Try Again
                        </Button>
                    </div>

                    <form
                        v-if="!completed"
                        :action="interviewSessionComplete.url(session.id)"
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
                            class="w-full gap-2 sm:w-auto"
                            :disabled="
                                isCompletingInterview ||
                                isProcessing ||
                                isPreparingAudio ||
                                isTranscribing
                            "
                        >
                            <Loader2
                                v-if="isCompletingInterview"
                                class="h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            End AI Interview
                        </Button>
                    </form>
                </CardFooter>
            </Card>
        </section>

        <aside class="min-h-0">
            <Card
                class="flex h-full max-h-[calc(100vh-150px)] flex-col gap-0 overflow-hidden rounded-[24px] border-slate-200 bg-white py-0 shadow-sm dark:border-slate-800 dark:bg-slate-950"
            >
                <CardContent class="flex-1 space-y-5 overflow-y-auto p-4">
                    <h2
                        class="text-sm font-semibold text-slate-900 dark:text-slate-100"
                    >
                        Conversation
                    </h2>

                    <div
                        v-if="chatMessages.length === 0"
                        class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-900"
                    >
                        {{
                            isProcessing
                                ? 'The AI is preparing your first question.'
                                : 'No question yet. Select Try Again to start.'
                        }}
                    </div>

                    <div
                        v-for="(msg, index) in chatMessages"
                        :key="index"
                        :ref="
                            (el: any) => {
                                if (el) messageRefs[index] = el as HTMLElement;
                            }
                        "
                        class="flex gap-3"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="
                                msg.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                            "
                        >
                            <User v-if="msg.role === 'user'" class="h-4 w-4" />
                            <Bot v-else class="h-4 w-4" />
                        </div>
                        <div
                            class="min-w-0 rounded-2xl px-4 py-3 text-sm leading-relaxed"
                            :class="
                                msg.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-100'
                            "
                        >
                            <p
                                v-if="msg.role === 'user'"
                                class="whitespace-pre-wrap"
                            >
                                {{ msg.content }}
                            </p>
                            <div
                                v-else
                                class="[&_a]:text-primary [&_a]:underline [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
                                v-html="assistantMessageHtml(msg.content)"
                            />
                        </div>
                    </div>

                    <div v-if="isProcessing" class="flex gap-3">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800"
                        >
                            <Bot class="h-4 w-4" />
                        </div>
                        <div
                            class="flex items-center gap-1.5 rounded-2xl bg-slate-100 px-4 py-3 dark:bg-slate-900"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.3s]"
                            ></span>
                            <span
                                class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.15s]"
                            ></span>
                            <span
                                class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"
                            ></span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </aside>
    </div>
</template>
