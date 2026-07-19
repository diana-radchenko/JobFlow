<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { Send, Bot, User, Sparkles } from 'lucide-vue-next';
import { marked } from 'marked';
import { ref, onMounted, nextTick, watch } from 'vue';
import { message as assistantMessage } from '@/actions/App/Http/Controllers/ResumeAssistantController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';

marked.setOptions({ gfm: true, breaks: true });

interface ChatMessage {
    role: string;
    content: string;
}

const props = defineProps<{
    resume: { id: number; title: string };
    aiMessages?: ChatMessage[];
}>();

/**
 * Renders assistant markdown to safe HTML for v-html.
 */
function assistantMessageHtml(content: string): string {
    return DOMPurify.sanitize(marked.parse(content, { async: false }) as string);
}

const chatMessages = ref<ChatMessage[]>([...(props.aiMessages ?? [])]);
const messageRefs = ref<HTMLElement[]>([]);
const newMessage = ref('');
const isProcessing = ref(false);

const scrollToLastMessage = () => {
    if (chatMessages.value.length === 0) {
        return;
    }

    nextTick(() => {
        messageRefs.value[chatMessages.value.length - 1]?.scrollIntoView({
            behavior: 'smooth',
            block: 'end',
        });
    });
};

watch(chatMessages, scrollToLastMessage, { deep: true });

onMounted(() => {
    scrollToLastMessage();

    if (chatMessages.value.length === 0) {
        sendMessage("Hi! I'd like to build my resume.");
    }
});

async function sendMessage(textOverride?: string) {
    const text = textOverride || newMessage.value.trim();

    if (!text || isProcessing.value) {
        return;
    }

    if (!textOverride) {
        chatMessages.value.push({ role: 'user', content: text });
        newMessage.value = '';
    }

    isProcessing.value = true;

    try {
        const response = await fetch(assistantMessage.url(props.resume.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': usePage().props.csrf_token as string,
            },
            body: JSON.stringify({ message: text }),
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const data = await response.json();

        if (data.message) {
            chatMessages.value.push(data.message);
        }

        // The assistant may have saved new resume data via tools; refresh props
        // so the other editor sections reflect the changes.
        router.reload();
    } catch (error) {
        console.error('Failed to send message:', error);

        if (!textOverride) {
            chatMessages.value.pop();
            newMessage.value = text;
        }
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
</script>

<template>
    <div>
        <div class="mb-4">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <Sparkles class="h-5 w-5 text-primary" />
                AI Assistant
            </h2>
            <p class="text-sm text-foreground/60">
                Answer a few questions and I'll fill in your resume for you.
            </p>
        </div>

        <Card class="flex h-[70vh] flex-col gap-0 overflow-hidden py-0">
            <CardContent class="flex-1 space-y-6 overflow-y-auto p-4">
                <div
                    v-for="(msg, index) in chatMessages"
                    :key="index"
                    :ref="
                        (el: any) => {
                            if (el) messageRefs[index] = el as HTMLElement;
                        }
                    "
                    class="flex max-w-[90%] gap-3"
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
                                : 'bg-muted text-foreground/70'
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
                        class="rounded-2xl rounded-tl-sm bg-muted px-4 py-3 text-[15px] leading-relaxed [&_a]:text-primary [&_a]:underline [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:font-semibold [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
                        v-html="assistantMessageHtml(msg.content)"
                    />
                </div>

                <div v-if="isProcessing" class="mr-auto flex max-w-[90%] gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-foreground/70"
                    >
                        <Bot class="h-4 w-4" />
                    </div>
                    <div
                        class="flex items-center gap-1.5 rounded-2xl rounded-tl-sm bg-muted px-4 py-3"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40 [animation-delay:-0.3s]"
                        ></span>
                        <span
                            class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40 [animation-delay:-0.15s]"
                        ></span>
                        <span
                            class="h-1.5 w-1.5 animate-bounce rounded-full bg-foreground/40"
                        ></span>
                    </div>
                </div>
            </CardContent>

            <CardFooter class="shrink-0 border-t p-4">
                <div
                    class="relative flex w-full items-center overflow-hidden rounded-2xl border bg-background px-3 py-2 focus-within:ring-2 focus-within:ring-primary/10"
                >
                    <Textarea
                        v-model="newMessage"
                        placeholder="Type your answer..."
                        class="max-h-[160px] min-h-[44px] w-full resize-none border-none bg-transparent px-0 py-2 text-[15px] shadow-none focus-visible:ring-0"
                        @keydown="handleKeydown"
                        :disabled="isProcessing"
                    />
                    <Button
                        @click="sendMessage()"
                        size="icon"
                        class="ml-2 h-9 w-9 shrink-0 rounded-xl"
                        :disabled="!newMessage.trim() || isProcessing"
                    >
                        <Send class="h-4 w-4" />
                    </Button>
                </div>
            </CardFooter>
        </Card>
    </div>
</template>
