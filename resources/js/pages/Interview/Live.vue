<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import {
    Bot,
    CheckCircle2,
    Loader2,
    Mic,
    MicOff,
    RotateCcw,
    Send,
    User,
    Volume2,
} from 'lucide-vue-next';
import { marked } from 'marked';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import {
    complete as interviewSessionComplete,
    message as interviewSessionMessage,
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
import { stringForHuman } from '@/helpers/strings';
import { interviewPreparation } from '@/routes';

marked.setOptions({ gfm: true, breaks: true });

const INITIAL_GREETING =
    "Hello! Welcome to your AI interview practice. Take a moment to get comfortable, and when you're ready, tell me we can start.";

interface SpeechRecognitionLike {
    continuous: boolean;
    interimResults: boolean;
    lang: string;
    onend: (() => void) | null;
    onerror: ((event: { error?: string }) => void) | null;
    onresult:
        | ((event: {
              resultIndex: number;
              results: {
                  length: number;
                  [index: number]: {
                      isFinal: boolean;
                      [index: number]: { transcript: string };
                  };
              };
          }) => void)
        | null;
    abort: () => void;
    start: () => void;
    stop: () => void;
}

type SpeechRecognitionConstructor = new () => SpeechRecognitionLike;

declare global {
    interface Window {
        SpeechRecognition?: SpeechRecognitionConstructor;
        webkitSpeechRecognition?: SpeechRecognitionConstructor;
    }
}

function assistantMessageHtml(content: string): string {
    const raw = marked.parse(content, { async: false }) as string;

    return DOMPurify.sanitize(raw);
}

const props = defineProps<{
    session: {
        id: number;
        type: string;
        complexity: string;
        mode: string;
        status: string;
        created_at: string;
    };
    messages: {
        role: string;
        content: string;
    }[];
}>();

const chatMessages = ref([...props.messages]);
const messageRefs = ref<HTMLElement[]>([]);
const committedTranscript = ref('');
const currentTranscript = ref('');
const recognitionLanguage = ref('en-US');
const isListening = ref(false);
const isProcessing = ref(false);
const isCompletingInterview = ref(false);
const isSpeaking = ref(false);
const liveError = ref<string | null>(null);
const recognition = ref<SpeechRecognitionLike | null>(null);

const recognitionLanguages = [
    { value: 'en-US', label: 'English' },
    { value: 'uz-UZ', label: 'Uzbek' },
    { value: 'ru-RU', label: 'Russian' },
];

const supportsSpeechRecognition = computed(() =>
    Boolean(window.SpeechRecognition ?? window.webkitSpeechRecognition),
);

const isSecureBrowserContext = computed(() => window.isSecureContext);

const canUseVoiceInput = computed(
    () => supportsSpeechRecognition.value && isSecureBrowserContext.value,
);

const statusLabel = computed(() => {
    if (props.session.status === 'completed') {
        return 'Completed';
    }

    if (isProcessing.value) {
        return 'Thinking';
    }

    if (isSpeaking.value) {
        return 'Speaking';
    }

    if (isListening.value) {
        return 'Listening';
    }

    return 'Ready';
});

const scrollToMessage = (index: number) => {
    nextTick(() => {
        messageRefs.value[index]?.scrollIntoView({
            behavior: 'smooth',
            block: 'end',
        });
    });
};

const scrollToLastMessage = () => {
    if (chatMessages.value.length > 0) {
        scrollToMessage(chatMessages.value.length - 1);
    }
};

watch(chatMessages, scrollToLastMessage, { deep: true });

function makeRecognition(): SpeechRecognitionLike | null {
    const SpeechRecognition =
        window.SpeechRecognition ?? window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        return null;
    }

    const instance = new SpeechRecognition();
    instance.continuous = true;
    instance.interimResults = true;
    instance.lang = recognitionLanguage.value;
    instance.onresult = (event) => {
        let interimTranscript = '';

        for (
            let index = event.resultIndex;
            index < event.results.length;
            index++
        ) {
            const transcript = event.results[index][0].transcript;

            if (event.results[index].isFinal) {
                committedTranscript.value =
                    `${committedTranscript.value} ${transcript}`.trim();
            } else {
                interimTranscript += transcript;
            }
        }

        currentTranscript.value =
            `${committedTranscript.value} ${interimTranscript}`.trim();
    };
    instance.onerror = (event) => {
        liveError.value =
            event.error === 'not-allowed'
                ? 'Microphone permission was denied. Allow microphone access and try again.'
                : 'Speech recognition stopped unexpectedly. Try again.';
        isListening.value = false;
    };
    instance.onend = () => {
        isListening.value = false;
    };

    return instance;
}

function resetTranscript() {
    committedTranscript.value = '';
    currentTranscript.value = '';
    liveError.value = null;
}

async function ensureMicrophonePermission(): Promise<boolean> {
    if (!navigator.mediaDevices?.getUserMedia) {
        return true;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true,
        });

        stream.getTracks().forEach((track) => track.stop());

        return true;
    } catch {
        liveError.value =
            'Microphone permission was denied. Allow microphone access and try again.';

        return false;
    }
}

async function startListening() {
    if (!isSecureBrowserContext.value) {
        liveError.value =
            'Live voice mode requires HTTPS or localhost. Open the app through the secure Herd URL and try again.';

        return;
    }

    if (!supportsSpeechRecognition.value) {
        liveError.value =
            'Your browser does not support speech recognition. Use Chrome or Edge for live mode.';

        return;
    }

    if (!(await ensureMicrophonePermission())) {
        return;
    }

    if (isProcessing.value || props.session.status === 'completed') {
        return;
    }

    window.speechSynthesis.cancel();
    isSpeaking.value = false;
    liveError.value = null;

    recognition.value?.abort();
    recognition.value = makeRecognition();
    recognition.value?.start();
    isListening.value = true;
}

function stopListening() {
    recognition.value?.stop();
    isListening.value = false;
}

function stopListeningAndSend() {
    stopListening();

    if (currentTranscript.value.trim()) {
        sendMessage(currentTranscript.value);
    }
}

function speakAssistantMessage(content: string) {
    if (!('speechSynthesis' in window)) {
        return;
    }

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(content);
    utterance.lang = 'en-US';
    utterance.rate = 0.95;
    utterance.onstart = () => {
        isSpeaking.value = true;
    };
    utterance.onend = () => {
        isSpeaking.value = false;
    };
    utterance.onerror = () => {
        isSpeaking.value = false;
    };

    window.speechSynthesis.speak(utterance);
}

async function sendMessage(text: string, showUserMessage = true) {
    const trimmedText = text.trim();

    if (
        !trimmedText ||
        isProcessing.value ||
        props.session.status === 'completed'
    ) {
        return;
    }

    const csrfToken = usePage().props.csrf_token as string | undefined;

    if (!csrfToken) {
        liveError.value =
            'Session token missing. Refresh the page and try again.';

        return;
    }

    if (showUserMessage) {
        chatMessages.value.push({
            role: 'user',
            content: trimmedText,
        });
    }

    isProcessing.value = true;
    liveError.value = null;

    try {
        const response = await fetch(
            interviewSessionMessage.url(props.session.id),
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: trimmedText }),
            },
        );

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const serverMessage =
                typeof data.message === 'string'
                    ? data.message
                    : (data.errors?.message?.[0] ??
                      `Request failed (${response.status})`);

            throw new Error(serverMessage);
        }

        if (data.message) {
            chatMessages.value.push(data.message);
            speakAssistantMessage(data.message.content);
        }

        if (showUserMessage) {
            resetTranscript();
        }
    } catch (error) {
        if (showUserMessage) {
            chatMessages.value.pop();
        }

        liveError.value =
            error instanceof Error
                ? error.message
                : 'Could not reach the AI. Check OPENAI_API_KEY in .env and try again.';
    } finally {
        isProcessing.value = false;
    }
}

function submitTranscript() {
    stopListening();
    sendMessage(currentTranscript.value);
}

function handleCompleteInterviewSubmit() {
    if (isCompletingInterview.value || isProcessing.value) {
        return;
    }

    stopListening();
    window.speechSynthesis.cancel();
    isSpeaking.value = false;
    isCompletingInterview.value = true;
}

onMounted(() => {
    if (
        chatMessages.value.length === 0 &&
        props.session.status === 'in_progress'
    ) {
        chatMessages.value.push({
            role: 'assistant',
            content: INITIAL_GREETING,
        });

        speakAssistantMessage(INITIAL_GREETING);
    }

    scrollToLastMessage();
});

onBeforeUnmount(() => {
    recognition.value?.abort();
    window.speechSynthesis.cancel();
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Interview Preparing',
                href: interviewPreparation(),
            },
            {
                title: 'Live Interview',
            },
        ],
    },
});
</script>

<template>
    <Head title="Live Interview" />

    <div
        class="container mx-auto grid min-h-[calc(100vh-120px)] max-w-[1200px] gap-6 px-5 py-8 font-sans lg:grid-cols-[1fr_380px]"
    >
        <section class="flex min-h-0 flex-col gap-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Live AI Interview
                    </p>
                    <h1
                        class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                    >
                        {{ stringForHuman(session.complexity) }}
                        {{ stringForHuman(session.type) }} Interview
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
                                : statusLabel === 'Thinking'
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
                    <span v-else>Chrome or Edge required</span>
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
                                : isProcessing
                                  ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950/40'
                                  : isSpeaking
                                    ? 'border-blue-300 bg-blue-50 shadow-[0_0_0_18px_rgba(59,130,246,0.12)] dark:border-blue-900 dark:bg-blue-950/40'
                                    : 'border-slate-200 bg-slate-50 hover:border-primary/40 dark:border-slate-800 dark:bg-slate-900',
                        ]"
                        :disabled="
                            isProcessing || session.status === 'completed'
                        "
                        @click="
                            isListening ? stopListening() : startListening()
                        "
                    >
                        <Loader2
                            v-if="isProcessing"
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
                                isListening
                                    ? 'Listening to your answer'
                                    : isProcessing
                                      ? 'AI is preparing the next response'
                                      : isSpeaking
                                        ? 'AI is speaking'
                                        : 'Ready for your answer'
                            }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Speak naturally. Your answer is sent as voice-driven
                            speech once you stop the microphone.
                        </p>
                    </div>

                    <div
                        class="min-h-28 w-full max-w-3xl rounded-2xl border border-slate-200 bg-slate-50 p-5 text-left text-sm leading-relaxed text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                    >
                        <span v-if="currentTranscript">
                            {{ currentTranscript }}
                        </span>
                        <span v-else class="text-slate-400">
                            Live caption will appear here while you speak.
                        </span>
                    </div>

                    <p
                        v-if="liveError"
                        class="max-w-2xl rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400"
                    >
                        {{ liveError }}
                    </p>
                </CardContent>

                <CardFooter
                    class="flex flex-col gap-3 border-t border-slate-100 p-4 sm:flex-row sm:justify-between dark:border-slate-800"
                >
                    <div class="flex w-full gap-3 sm:w-auto">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1 gap-2 sm:flex-none"
                            :disabled="
                                isListening ||
                                isProcessing ||
                                session.status === 'completed'
                            "
                            @click="resetTranscript"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Retry
                        </Button>
                        <Button
                            type="button"
                            class="flex-1 gap-2 sm:flex-none"
                            :disabled="
                                isListening ||
                                !currentTranscript.trim() ||
                                isProcessing ||
                                session.status === 'completed'
                            "
                            @click="submitTranscript"
                        >
                            <Send class="h-4 w-4" />
                            Send Answer
                        </Button>
                        <Button
                            v-if="isListening"
                            type="button"
                            class="flex-1 gap-2 sm:flex-none"
                            @click="stopListeningAndSend"
                        >
                            <MicOff class="h-4 w-4" />
                            Stop & Send
                        </Button>
                    </div>

                    <form
                        v-if="session.status === 'in_progress'"
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
                            :disabled="isCompletingInterview || isProcessing"
                        >
                            <Loader2
                                v-if="isCompletingInterview"
                                class="h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            Complete Interview
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
                        The AI is preparing your first question.
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
