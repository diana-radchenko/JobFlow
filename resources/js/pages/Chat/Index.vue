<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    index as jobChatIndex,
    store as sendJobMessageRoute,
} from '@/actions/App/Http/Controllers/JobChatController';

type Message = {
    id: number;
    body: string;
    sender_id: number;
    created_at: string;
    sender: { name: string };
};
type Conversation = {
    id: number;
    application_id: number;
    employer_user_id: number;
    candidate_user_id: number;
    unread_count: number;
    work_job: { title: string; company: string };
    employer: { name: string };
    candidate: { name: string };
    messages: Message[];
};
const props = defineProps<{
    conversations: Conversation[];
    selectedConversationId: number | null;
}>();
const search = ref('');
const selected = computed(
    () =>
        props.conversations.find(
            (item) => item.id === props.selectedConversationId,
        ) ?? props.conversations[0],
);
const filtered = computed(() =>
    props.conversations.filter((item) =>
        `${item.candidate.name} ${item.employer.name} ${item.work_job.title}`
            .toLowerCase()
            .includes(search.value.toLowerCase()),
    ),
);
const form = useForm({ body: '' });
const send = () => {
    if (!selected.value) {
        return;
    }

    form.post(sendJobMessageRoute(selected.value.application_id).url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Recruitment Chat" />
    <div class="mx-auto grid max-w-6xl gap-4 p-6 lg:grid-cols-[320px_1fr]">
        <Card
            ><CardContent class="space-y-3 pt-6"
                ><h1 class="text-xl font-bold">Chat</h1>
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
                    class="block rounded-xl border p-3 hover:bg-accent"
                >
                    <div class="flex justify-between font-semibold">
                        <span
                            >{{ conversation.candidate.name }} /
                            {{ conversation.employer.name }}</span
                        ><span
                            v-if="conversation.unread_count"
                            class="rounded-full bg-primary px-2 text-primary-foreground"
                            >{{ conversation.unread_count }}</span
                        >
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ conversation.work_job.company }} ·
                        {{ conversation.work_job.title }}
                    </p>
                    <p class="truncate text-sm">
                        {{
                            conversation.messages.at(-1)?.body ??
                            'No messages yet'
                        }}
                    </p>
                </Link>
            </CardContent></Card
        >
        <Card
            ><CardContent
                v-if="selected"
                class="flex min-h-[560px] flex-col pt-6"
                ><div class="border-b pb-3">
                    <h2 class="font-bold">
                        {{ selected.candidate.name }} ·
                        {{ selected.employer.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ selected.work_job.company }} —
                        {{ selected.work_job.title }}
                    </p>
                </div>
                <div class="flex-1 space-y-3 overflow-y-auto py-4">
                    <div
                        v-for="message in selected.messages"
                        :key="message.id"
                        class="rounded-xl bg-accent p-3"
                    >
                        <div class="text-xs font-semibold">
                            {{ message.sender.name }} ·
                            {{ new Date(message.created_at).toLocaleString() }}
                        </div>
                        <p class="whitespace-pre-wrap">{{ message.body }}</p>
                    </div>
                </div>
                <form class="flex gap-2 border-t pt-3" @submit.prevent="send">
                    <input
                        v-model="form.body"
                        required
                        maxlength="5000"
                        class="flex-1 rounded-md border bg-background p-2"
                        placeholder="Write a message"
                    /><Button type="submit" :disabled="form.processing"
                        >Send</Button
                    >
                </form> </CardContent
            ><CardContent v-else class="py-20 text-center text-muted-foreground"
                >A conversation appears after an employer or candidate sends the
                first message from a real application.</CardContent
            ></Card
        >
    </div>
</template>
