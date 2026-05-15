<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { interviewPreparation } from '@/routes';
import { message as interviewSessionMessage, complete as interviewSessionComplete } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { ref, onMounted, nextTick, watch } from 'vue';
import { Send, CheckCircle2, User, Bot, Loader2 } from 'lucide-vue-next';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { stringForHuman } from '@/helpers/strings';

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
const chatContainer = ref<HTMLElement | null>(null);

const scrollToMessage = (index: number) => {
    nextTick(() => {
        messageRefs.value[index]?.scrollIntoView({ behavior: 'smooth', block: 'end' });
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
    if (chatMessages.value.length === 0 && props.session.status === 'in_progress') {
        sendMessage('Hello, I am ready to begin the interview.');
    }
});

async function sendMessage(textOverride?: string) {
    const text = textOverride || newMessage.value.trim();
    
    if (!text || isProcessing.value || props.session.status === 'completed') return;

    // Add user message to UI
    if (!textOverride) {
        chatMessages.value.push({
            role: 'user',
            content: text
        });
        newMessage.value = '';
    }
    
    isProcessing.value = true;

    try {
        const response = await fetch(interviewSessionMessage.url(props.session.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token as string,
            },
            body: JSON.stringify({ message: text }),
        });

        if (!response.ok) throw new Error('Request failed');
        
        const data = await response.json();

        if (data.message) {
            chatMessages.value.push(data.message);
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        // Remove the user message if it failed
        if (!textOverride) {
            chatMessages.value.pop();
            newMessage.value = text; // restore text
        }
        alert('An error occurred while communicating with the AI. Please try again.');
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
            }
        ],
    },
});
</script>

<template>
    <Head title="Text-Based Interview" />

    <div class="container mx-auto px-5 py-8 font-sans max-w-4xl h-[calc(100vh-120px)] flex flex-col">
        <div class="md:flex items-center justify-between mb-6 shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                    {{ stringForHuman(session.complexity) }} {{ stringForHuman(session.type) }} Interview
                </h1>
                <p class="text-sm text-slate-500">
                    <span v-if="session.status === 'completed'" class="text-green-600 font-medium">Completed</span>
                    <span v-else class="text-blue-600 font-medium">In Progress</span>
                </p>
            </div>

            <form
                v-if="session.status === 'in_progress'"
                :action="interviewSessionComplete.url(session.id)"
                method="POST"
                @submit="handleCompleteInterviewSubmit"
            >
                <input type="hidden" name="_token" :value="$page.props.csrf_token">
                <Button type="submit" variant="outline" class="gap-2 mt-5 md:mt-0" :disabled="isCompletingInterview || isProcessing">
                    <Loader2 v-if="isCompletingInterview" class="w-4 h-4 animate-spin" />
                    <CheckCircle2 v-else class="w-4 h-4" />
                    {{ isCompletingInterview ? 'Processing Completion...' : 'Complete Interview' }}
                </Button>
            </form>
        </div>

        <Card class="flex-1 flex flex-col min-h-0 bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 shadow-sm rounded-2xl overflow-hidden py-0 gap-0">
            <!-- Chat History -->
            <CardContent class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6" ref="chatContainer">
                <div v-if="chatMessages.length === 0" class="h-full flex flex-col items-center justify-center text-slate-500 space-y-4">
                    <Bot class="w-12 h-12 text-slate-300" />
                    <p>The AI is preparing your first question...</p>
                </div>

                <div 
                    v-for="(msg, index) in chatMessages" 
                    :key="index"
                    :ref="(el: any) => { if (el) messageRefs[index] = el as HTMLElement }"
                    class="flex gap-4 max-w-[85%]"
                    :class="[
                        msg.role === 'user' ? 'ml-auto flex-row-reverse' : 'mr-auto'
                    ]"
                >
                    <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                         :class="msg.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                        <User v-if="msg.role === 'user'" class="w-4 h-4" />
                        <Bot v-else class="w-4 h-4" />
                    </div>
                    
                    <div
                        v-if="msg.role === 'user'"
                        class="px-4 py-3 rounded-2xl text-[15px] leading-relaxed whitespace-pre-wrap bg-primary text-primary-foreground rounded-tr-sm"
                    >
                        {{ msg.content }}
                    </div>
                    <div
                        v-else
                        class="px-4 py-3 rounded-2xl text-[15px] leading-relaxed bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-tl-sm [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_em]:italic [&_a]:text-primary [&_a]:underline [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:my-0.5 [&_code]:rounded [&_code]:bg-slate-200/80 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-[0.9em] dark:[&_code]:bg-slate-800 [&_pre]:my-2 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-200/80 [&_pre]:p-3 [&_pre]:text-sm dark:[&_pre]:bg-slate-800 [&_blockquote]:border-l-2 [&_blockquote]:border-slate-300 [&_blockquote]:pl-3 [&_blockquote]:text-slate-600 dark:[&_blockquote]:border-slate-600 dark:[&_blockquote]:text-slate-400"
                        v-html="assistantMessageHtml(msg.content)"
                    />
                </div>

                <div v-if="isProcessing" class="flex gap-4 max-w-[85%] mr-auto">
                    <div class="shrink-0 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                        <Bot class="w-4 h-4" />
                    </div>
                    <div class="px-4 py-3 rounded-2xl bg-slate-100 dark:bg-slate-900 text-slate-500 rounded-tl-sm flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></span>
                    </div>
                </div>
            </CardContent>

            <!-- Input Area -->
            <CardFooter v-if="session.status === 'in_progress'" class="p-4 bg-white dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 shrink-0">
                <div class="relative w-full max-w-3xl mx-auto group">
                    <div class="relative flex items-center bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl transition-all duration-200 focus-within:ring-2 focus-within:ring-primary/10 focus-within:border-primary/30 focus-within:bg-white dark:focus-within:bg-slate-900 overflow-hidden px-3 py-2">
                        <Textarea 
                            ref="textarea"
                            v-model="newMessage"
                            placeholder="Type your response..."
                            class="min-h-[44px] max-h-[200px] w-full bg-transparent dark:bg-transparent border-none shadow-none focus-visible:ring-0 px-0 py-2 text-[15px] resize-none"
                            @keydown="handleKeydown"
                            :disabled="isProcessing"
                        />
                        <div class="flex items-center ml-2 shrink-0">
                            <Button 
                                @click="sendMessage()" 
                                size="icon"
                                class="h-9 w-9 rounded-xl transition-all duration-200 active:scale-95 shadow-sm cursor-pointer"
                                :disabled="!newMessage.trim() || isProcessing"
                            >
                                <Send class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="mt-2 flex justify-between px-2">
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                            Press <span class="font-medium text-slate-500 dark:text-slate-400">Enter</span> to send, <span class="font-medium text-slate-500 dark:text-slate-400">Shift+Enter</span> for new line
                        </p>
                    </div>
                </div>
            </CardFooter>
            <CardFooter v-else class="p-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 shrink-0 justify-center">
                <p class="text-sm text-slate-500">This interview has been completed. You can review the chat history above.</p>
            </CardFooter>
        </Card>
    </div>
</template>
