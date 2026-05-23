<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { Send, CheckCircle2, User, Bot, Loader2 } from 'lucide-vue-next';
import { marked } from 'marked';
import { ref, onMounted, nextTick, watch } from 'vue';
import {
    message as interviewSessionMessage,
    complete as interviewSessionComplete,
} from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { stringForHuman } from '@/helpers/strings';
import { interviewPreparation } from '@/routes';

marked.setOptions({ gfm: true, breaks: true });

/**
 * Renders assistant markdown to safe HTML for v-html.
 */
function assistantMessageHtml(content: string): string {
    const raw = marked.parse(content, { async: false }) as string;

    return DOMPurify.sanitize(raw);
}

const props = defineProps<{
    session: {
        id: number;
        type: string;
        complexity: string;
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
const newMessage = ref('');
const isProcessing = ref(false);
const isCompletingInterview = ref(false);
const chatError = ref<string | null>(null);
const chatContainer = ref<HTMLElement | null>(null);

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

onMounted(() => {
    scrollToLastMessage();

    // If it's a new session, trigger the first message from AI
    if (
        chatMessages.value.length === 0 &&
        props.session.status === 'in_progress'
    ) {
        sendMessage('Hello, I am ready to begin the interview.');
    }
});

async function sendMessage(textOverride?: string) {
    const text = textOverride || newMessage.value.trim();

    if (!text || isProcessing.value || props.session.status === 'completed') {
        return;
    }

    const csrfToken = usePage().props.csrf_token as string | undefined;

    if (!csrfToken) {
        chatError.value =
            'Session token missing. Refresh the page and try again.';

        return;
    }

    chatMessages.value.push({
        role: 'user',
        content: text,
    });

    if (!textOverride) {
        newMessage.value = '';
    }

    isProcessing.value = true;
    chatError.value = null;

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
                body: JSON.stringify({ message: text }),
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
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        chatMessages.value.pop();

        if (!textOverride) {
            newMessage.value = text;
        }

        chatError.value =
            error instanceof Error
                ? error.message
                : 'Could not reach the AI. Check OPENAI_API_KEY in .env and try again.';
    } finally {
        isProcessing.value = false;
    }
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function handleCompleteInterviewSubmit() {
    if (isCompletingInterview.value || isProcessing.value) {
        return;
    }

    isCompletingInterview.value = true;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Interview Preparing',
                href: interviewPreparation(),
            },
            {
                title: 'Text-Based Interview',
            },
        ],
    },
});
</script>

<template>
    <Head title="Text-Based Interview" />

    <div
        class="container mx-auto flex h-[calc(100vh-120px)] max-w-4xl flex-col px-5 py-8 font-sans"
    >
        <div class="mb-6 shrink-0 items-center justify-between md:flex">
            <div>
                <h1
                    class="text-2xl font-bold text-slate-900 dark:text-slate-100"
                >
                    {{ stringForHuman(session.complexity) }}
                    {{ stringForHuman(session.type) }} Interview
                </h1>
                <p class="text-sm text-slate-500">
                    <span
                        v-if="session.status === 'completed'"
                        class="font-medium text-green-600"
                        >Completed</span
                    >
                    <span v-else class="font-medium text-blue-600"
                        >In Progress</span
                    >
                </p>
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
                    class="mt-5 gap-2 md:mt-0"
                    :disabled="isCompletingInterview || isProcessing"
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
        </div>

        <Card
            class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden rounded-2xl border-slate-200 bg-white py-0 shadow-sm dark:border-slate-800 dark:bg-slate-950"
        >
            <!-- Chat History -->
            <CardContent
                class="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6"
                ref="chatContainer"
            >
                <div
                    v-if="chatMessages.length === 0"
                    class="flex h-full flex-col items-center justify-center space-y-4 text-slate-500"
                >
                    <Bot class="h-12 w-12 text-slate-300" />
                    <p>The AI is preparing your first question...</p>
                </div>

                <div
                    v-for="(msg, index) in chatMessages"
                    :key="index"
                    :ref="
                        (el: any) => {
                            if (el) messageRefs[index] = el as HTMLElement;
                        }
                    "
                    class="flex max-w-[85%] gap-4"
                    :class="[
                        msg.role === 'user'
                            ? 'ml-auto flex-row-reverse'
                            : 'mr-auto',
                    ]"
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
                        v-if="msg.role === 'user'"
                        class="rounded-2xl rounded-tr-sm bg-primary px-4 py-3 text-[15px] leading-relaxed whitespace-pre-wrap text-primary-foreground"
                    >
                        {{ msg.content }}
                    </div>
                    <div
                        v-else
                        class="rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-[15px] leading-relaxed text-slate-900 dark:bg-slate-900 dark:text-slate-100 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-slate-300 [&_blockquote]:pl-3 [&_blockquote]:text-slate-600 dark:[&_blockquote]:border-slate-600 dark:[&_blockquote]:text-slate-400 [&_code]:rounded [&_code]:bg-slate-200/80 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-[0.9em] dark:[&_code]:bg-slate-800 [&_em]:italic [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_pre]:my-2 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-200/80 [&_pre]:p-3 [&_pre]:text-sm dark:[&_pre]:bg-slate-800 [&_strong]:font-semibold [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
                        v-html="assistantMessageHtml(msg.content)"
                    />
                </div>

                <div v-if="isProcessing" class="mr-auto flex max-w-[85%] gap-4">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                    >
                        <Bot class="h-4 w-4" />
                    </div>
                    <div
                        class="flex items-center gap-1.5 rounded-2xl rounded-tl-sm bg-slate-100 px-4 py-3 text-slate-500 dark:bg-slate-900"
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

            <p
                v-if="chatError"
                class="shrink-0 border-t border-red-100 bg-red-50 px-6 py-2 text-sm text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400"
            >
                {{ chatError }}
            </p>

            <!-- Input Area -->
            <CardFooter
                v-if="session.status === 'in_progress'"
                class="shrink-0 border-t border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-950"
            >
                <div class="group relative mx-auto w-full max-w-3xl">
                    <div
                        class="relative flex items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 transition-all duration-200 focus-within:border-primary/30 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary/10 dark:border-slate-800 dark:bg-slate-900/50 dark:focus-within:bg-slate-900"
                    >
                        <Textarea
                            ref="textarea"
                            v-model="newMessage"
                            placeholder="Type your response..."
                            class="max-h-[200px] min-h-[44px] w-full resize-none border-none bg-transparent px-0 py-2 text-[15px] shadow-none focus-visible:ring-0 dark:bg-transparent"
                            @keydown="handleKeydown"
                            :disabled="isProcessing"
                        />
                        <div class="ml-2 flex shrink-0 items-center">
                            <Button
                                @click="sendMessage()"
                                size="icon"
                                class="h-9 w-9 cursor-pointer rounded-xl shadow-sm transition-all duration-200 active:scale-95"
                                :disabled="!newMessage.trim() || isProcessing"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="mt-2 flex justify-between px-2">
                        <p
                            class="text-[11px] text-slate-400 dark:text-slate-500"
                        >
                            Press
                            <span
                                class="font-medium text-slate-500 dark:text-slate-400"
                                >Enter</span
                            >
                            to send,
                            <span
                                class="font-medium text-slate-500 dark:text-slate-400"
                                >Shift+Enter</span
                            >
                            for new line
                        </p>
                    </div>
                </div>
            </CardFooter>
            <CardFooter
                v-else
                class="shrink-0 justify-center border-t border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
            >
                <p class="text-sm text-slate-500">
                    This interview has been completed. You can review the chat
                    history above.
                </p>
            </CardFooter>
        </Card>
    </div>
</template>
