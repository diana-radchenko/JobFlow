<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import {
    Bot,
    CheckCircle2,
    Loader2,
    RotateCcw,
    Send,
    User,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { stringForHuman } from '@/helpers/strings';
import {
    complete as interviewSessionComplete,
    message as interviewSessionMessage,
    results as interviewSessionResults,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
import { interviewPreparation } from '@/routes';

marked.setOptions({ gfm: true, breaks: true });

const props = defineProps<{
    session: { id: number; type: string; complexity: string; status: string };
    messages: { role: string; content: string }[];
    context: {
        resume_title?: string | null;
        job_title?: string | null;
        company?: string | null;
    };
    questionNumber: number;
    totalQuestions: number;
}>();

type InterviewState = 'PREPARING' | 'IN_PROGRESS' | 'COMPLETED' | 'ERROR';

const page = usePage();
const chatMessages = ref([...props.messages]);
const questionNumber = ref(props.questionNumber);
const state = ref<InterviewState>(
    props.session.status === 'completed'
        ? 'COMPLETED'
        : props.questionNumber > 0
          ? 'IN_PROGRESS'
          : 'PREPARING',
);
const newMessage = ref('');
const pendingAnswer = ref('');
const retryIntent = ref<'start' | 'answer'>('start');
const errorMessage = ref('');
const isProcessing = ref(false);
const isCompletingInterview = ref(false);
const messageRefs = ref<HTMLElement[]>([]);

const mockInterviewTitle = computed(() => {
    const type = stringForHuman(props.session.type);

    return type.toLowerCase().endsWith('interview')
        ? `${stringForHuman(props.session.complexity)} ${type}`
        : `${stringForHuman(props.session.complexity)} ${type} Mock Interview`;
});

function assistantMessageHtml(content: string): string {
    return DOMPurify.sanitize(
        marked.parse(content, { async: false }) as string,
    );
}

function scrollToLastMessage(): void {
    nextTick(() => {
        messageRefs.value
            .at(-1)
            ?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    });
}

watch(chatMessages, scrollToLastMessage, { deep: true });

onMounted(() => {
    scrollToLastMessage();

    if (state.value === 'PREPARING') {
        void requestQuestion('start');
    }
});

async function requestQuestion(
    intent: 'start' | 'answer',
    answer = '',
): Promise<void> {
    if (isProcessing.value || state.value === 'COMPLETED') {
        return;
    }

    isProcessing.value = true;
    retryIntent.value = intent;
    pendingAnswer.value = answer;
    state.value = intent === 'start' ? 'PREPARING' : 'IN_PROGRESS';
    errorMessage.value = '';

    try {
        const response = await fetch(
            interviewSessionMessage.url(props.session.id),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': page.props.csrf_token as string,
                },
                body: JSON.stringify({ intent, message: answer || undefined }),
            },
        );
        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message || "We couldn't prepare the interview question.",
            );
        }

        questionNumber.value = data.question_number;

        if (data.session_status === 'completed') {
            pendingAnswer.value = '';
            state.value = 'COMPLETED';

            return;
        }

        if (
            data.message &&
            !chatMessages.value.some(
                (item) =>
                    item.role === data.message.role &&
                    item.content === data.message.content,
            )
        ) {
            chatMessages.value.push(data.message);
        }

        pendingAnswer.value = '';
        state.value = 'IN_PROGRESS';
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : "We couldn't prepare the interview question.";
        state.value = 'ERROR';
    } finally {
        isProcessing.value = false;
    }
}

function sendAnswer(): void {
    const answer = newMessage.value.trim();

    if (!answer || state.value !== 'IN_PROGRESS' || isProcessing.value) {
        return;
    }

    chatMessages.value.push({ role: 'user', content: answer });
    newMessage.value = '';
    void requestQuestion('answer', answer);
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendAnswer();
    }
}

function handleCompleteInterviewSubmit(): void {
    if (!isProcessing.value) {
        isCompletingInterview.value = true;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interview', href: interviewPreparation() },
            { title: 'Mock Interview' },
        ],
    },
});
</script>

<template>
    <Head title="Mock Interview" />

    <div
        class="container mx-auto flex h-[calc(100vh-120px)] max-w-4xl flex-col px-5 py-8 font-sans"
    >
        <div class="mb-6 shrink-0 items-center justify-between gap-5 md:flex">
            <div>
                <p
                    class="mb-1 text-xs font-semibold tracking-wide text-[#7047EB] uppercase"
                >
                    Mock Interview
                </p>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ mockInterviewTitle }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ context.job_title || 'General interview'
                    }}<span v-if="context.company">
                        · {{ context.company }}</span
                    >
                    · {{ context.resume_title }}
                </p>
                <p
                    v-if="state === 'IN_PROGRESS'"
                    class="mt-2 text-sm font-semibold text-blue-700"
                >
                    Question {{ questionNumber }} of {{ totalQuestions }}
                </p>
                <p
                    v-else-if="state === 'PREPARING'"
                    class="mt-2 text-sm font-semibold text-blue-700"
                >
                    Preparing question...
                </p>
                <p
                    v-else-if="state === 'COMPLETED'"
                    class="mt-2 text-sm font-semibold text-emerald-700"
                >
                    Completed
                </p>
                <p v-else class="mt-2 text-sm font-semibold text-red-700">
                    Question preparation failed
                </p>
            </div>

            <form
                v-if="state === 'IN_PROGRESS'"
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
                    class="mt-4 gap-2 md:mt-0"
                    :disabled="isCompletingInterview || isProcessing"
                >
                    <Loader2
                        v-if="isCompletingInterview"
                        class="h-4 w-4 animate-spin"
                    />
                    <CheckCircle2 v-else class="h-4 w-4" />
                    End Mock Interview
                </Button>
            </form>
        </div>

        <Card
            class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden rounded-2xl border-slate-200 bg-white py-0 shadow-sm"
        >
            <CardContent class="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
                <div
                    v-if="state === 'PREPARING'"
                    class="flex h-full flex-col items-center justify-center gap-4 text-slate-500"
                >
                    <Loader2 class="h-10 w-10 animate-spin text-blue-500" />
                    <p>The AI is preparing your first interview question.</p>
                </div>

                <div
                    v-else-if="state === 'ERROR'"
                    class="flex h-full flex-col items-center justify-center gap-4 text-center"
                >
                    <Bot class="h-11 w-11 text-red-300" />
                    <p class="font-semibold text-slate-900">
                        {{
                            errorMessage ||
                            "We couldn't prepare the interview question."
                        }}
                    </p>
                    <Button
                        variant="outline"
                        class="gap-2"
                        @click="requestQuestion(retryIntent, pendingAnswer)"
                        ><RotateCcw class="h-4 w-4" />Try Again</Button
                    >
                </div>

                <template v-else>
                    <div
                        v-for="(msg, index) in chatMessages"
                        :key="index"
                        :ref="
                            (element: any) => {
                                if (element)
                                    messageRefs[index] = element as HTMLElement;
                            }
                        "
                        class="flex max-w-[85%] gap-4"
                        :class="
                            msg.role === 'user'
                                ? 'ml-auto flex-row-reverse'
                                : 'mr-auto'
                        "
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="
                                msg.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            <User
                                v-if="msg.role === 'user'"
                                class="h-4 w-4"
                            /><Bot v-else class="h-4 w-4" />
                        </div>
                        <div
                            v-if="msg.role === 'user'"
                            class="rounded-2xl rounded-tr-sm bg-primary px-4 py-3 text-[15px] leading-relaxed whitespace-pre-wrap text-primary-foreground"
                        >
                            {{ msg.content }}
                        </div>
                        <div
                            v-else
                            class="rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-[15px] leading-relaxed text-slate-900 [&_li]:my-0.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5"
                            v-html="assistantMessageHtml(msg.content)"
                        />
                    </div>
                    <div
                        v-if="isProcessing"
                        class="mr-auto flex items-center gap-3 text-sm text-slate-500"
                    >
                        <Loader2 class="h-5 w-5 animate-spin" />Preparing the
                        next question...
                    </div>
                </template>
            </CardContent>

            <CardFooter
                v-if="state === 'IN_PROGRESS'"
                class="shrink-0 border-t border-slate-100 bg-white p-4"
            >
                <div class="mx-auto w-full max-w-3xl">
                    <div
                        class="flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 focus-within:border-primary/30 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary/10"
                    >
                        <Textarea
                            v-model="newMessage"
                            placeholder="Type your answer..."
                            class="max-h-[200px] min-h-[44px] w-full resize-none border-none bg-transparent px-0 py-2 text-[15px] shadow-none focus-visible:ring-0"
                            :disabled="isProcessing"
                            @keydown="handleKeydown"
                        />
                        <Button
                            size="icon"
                            class="ml-2 h-9 w-9 shrink-0 rounded-xl"
                            :disabled="!newMessage.trim() || isProcessing"
                            @click="sendAnswer"
                            ><Send class="h-4 w-4"
                        /></Button>
                    </div>
                </div>
            </CardFooter>
            <CardFooter
                v-else-if="state === 'COMPLETED'"
                class="shrink-0 justify-center gap-3 border-t border-slate-100 bg-slate-50 p-4"
            >
                <Button as-child
                    ><Link :href="interviewSessionResults.url(session.id)"
                        >View Results</Link
                    ></Button
                >
                <Button as-child variant="outline"
                    ><Link :href="interviewPreparation()"
                        >Practice Again</Link
                    ></Button
                >
            </CardFooter>
        </Card>
    </div>
</template>
