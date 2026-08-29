<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    index as jobChatIndex,
    store as sendJobMessageRoute,
} from '@/actions/App/Http/Controllers/JobChatController';
import employerApplications from '@/routes/employer/applications';
import { show as jobSelectionShow } from '@/routes/job-selection';

type Message = {
    id: number;
    body: string;
    type: 'user' | 'system';
    sender_id: number;
    created_at: string;
    read_at: string | null;
    sender: { name: string };
};
type Conversation = {
    id: number;
    application_id: number;
    employer_user_id: number;
    candidate_user_id: number;
    unread_count: number;
    work_job_id: number;
    work_job: { id: number; title: string; company: string };
    employer: { name: string };
    candidate: { name: string };
    latest_message: Message | null;
    messages?: Message[];
};

const props = defineProps<{
    conversations: Conversation[];
    selectedConversation: Conversation | null;
    currentUser: { id: number; role: 'candidate' | 'employer' };
}>();
const search = ref('');
const history = ref<HTMLElement | null>(null);
const isEmployer = computed(() => props.currentUser.role === 'employer');
const selected = computed(() => props.selectedConversation);
const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.conversations.filter((item) =>
        `${item.candidate.name} ${item.employer.name} ${item.work_job.company} ${item.work_job.title}`
            .toLowerCase()
            .includes(query),
    );
});
const participantName = (conversation: Conversation) =>
    isEmployer.value
        ? conversation.candidate.name
        : conversation.work_job.company || conversation.employer.name;
const formatTime = (value?: string | null) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const today = new Date();

    return date.toDateString() === today.toDateString()
        ? date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
        : date.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const form = useForm({ body: '' });
const scrollToLatest = async () => {
    await nextTick();

    if (history.value) {
        history.value.scrollTop = history.value.scrollHeight;
    }
};
const send = () => {
    const body = form.body.trim();

    if (!selected.value || !body) {
        return;
    }

    form.body = body;
    form.post(sendJobMessageRoute(selected.value.application_id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            void scrollToLatest();
        },
    });
};

watch(
    () => props.selectedConversation?.messages?.length,
    () => void scrollToLatest(),
);
let poller: number | undefined;
onMounted(() => {
    void scrollToLatest();
    poller = window.setInterval(() => {
        if (!form.processing) {
            router.reload({
                only: ['conversations', 'selectedConversation'],
                preserveScroll: true,
                preserveState: true,
            });
        }
    }, 5000);
});
onUnmounted(() => {
    if (poller) {
        window.clearInterval(poller);
    }
});
</script>

<template>
    <Head title="Chat" />
    <div class="mx-auto grid max-w-6xl gap-4 p-6 lg:grid-cols-[340px_1fr]">
        <Card>
            <CardContent class="space-y-3 pt-6">
                <h1 class="text-xl font-bold">Chat</h1>
                <input
                    v-model="search"
                    class="w-full rounded-md border bg-background p-2"
                    placeholder="Search conversations"
                />
                <Link
                    v-for="conversation in filtered"
                    :key="conversation.id"
                    :href="
                        jobChatIndex({
                            query: { conversation: conversation.id },
                        }).url
                    "
                    class="block rounded-xl border p-3 transition-colors hover:bg-accent"
                    :class="{
                        'border-primary bg-accent':
                            selected?.id === conversation.id,
                    }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span class="min-w-0 truncate font-semibold">{{
                            participantName(conversation)
                        }}</span>
                        <span class="shrink-0 text-xs text-muted-foreground">{{
                            formatTime(conversation.latest_message?.created_at)
                        }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <p
                            class="min-w-0 truncate text-xs text-muted-foreground"
                        >
                            {{ conversation.work_job.title }}
                        </p>
                        <span
                            v-if="conversation.unread_count"
                            class="rounded-full bg-primary px-2 py-0.5 text-xs font-bold text-primary-foreground"
                            >{{ conversation.unread_count }}</span
                        >
                    </div>
                    <p class="mt-2 truncate text-sm">
                        {{
                            conversation.latest_message?.body ??
                            'No messages yet'
                        }}
                    </p>
                </Link>
            </CardContent>
        </Card>

        <Card>
            <CardContent
                v-if="selected"
                class="flex min-h-[620px] flex-col pt-6"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b pb-4"
                >
                    <div>
                        <h2 class="font-bold">
                            {{ participantName(selected) }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ selected.work_job.title }}
                        </p>
                    </div>
                    <Link
                        v-if="isEmployer"
                        :href="
                            employerApplications.show([
                                selected.work_job_id,
                                selected.application_id,
                            ]).url
                        "
                        class="text-sm font-semibold text-primary hover:underline"
                        >View Application</Link
                    >
                    <Link
                        v-else
                        :href="jobSelectionShow(selected.work_job_id).url"
                        class="text-sm font-semibold text-primary hover:underline"
                        >View Vacancy</Link
                    >
                </div>
                <div
                    ref="history"
                    class="flex-1 space-y-3 overflow-y-auto py-4"
                >
                    <template
                        v-for="message in selected.messages"
                        :key="message.id"
                    >
                        <div
                            v-if="message.type === 'system'"
                            class="mx-auto max-w-md rounded-xl border bg-muted/50 px-4 py-3 text-center"
                        >
                            <p class="text-sm font-medium whitespace-pre-wrap">
                                {{ message.body }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    new Date(
                                        message.created_at,
                                    ).toLocaleString()
                                }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="flex"
                            :class="
                                message.sender_id === currentUser.id
                                    ? 'justify-end'
                                    : 'justify-start'
                            "
                        >
                            <div
                                class="max-w-[78%] rounded-2xl px-4 py-3"
                                :class="
                                    message.sender_id === currentUser.id
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-accent'
                                "
                            >
                                <p class="whitespace-pre-wrap">
                                    {{ message.body }}
                                </p>
                                <p class="mt-1 text-xs opacity-70">
                                    {{ formatTime(message.created_at) }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <form class="flex gap-2 border-t pt-3" @submit.prevent="send">
                    <input
                        v-model="form.body"
                        required
                        maxlength="5000"
                        class="flex-1 rounded-md border bg-background p-2"
                        placeholder="Write a message..."
                    />
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.body.trim()"
                        >Send</Button
                    >
                </form>
            </CardContent>
            <CardContent
                v-else
                class="flex min-h-[620px] items-center justify-center p-10 text-center"
            >
                <div>
                    <h2 class="text-lg font-semibold">No conversations yet</h2>
                    <p class="mt-2 max-w-md text-muted-foreground">
                        <template v-if="isEmployer"
                            >Candidate conversations will appear here when
                            candidates apply.</template
                        >
                        <template v-else
                            >Your conversations with employers will appear here
                            after you apply for a vacancy.</template
                        >
                    </p>
                    <Button v-if="!isEmployer" as-child class="mt-4"
                        ><a href="/job-selection">Browse Jobs</a></Button
                    >
                </div>
            </CardContent>
        </Card>
    </div>
</template>

