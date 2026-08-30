<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Loader2, RotateCcw } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { feedback } from '@/actions/App/Http/Controllers/InterviewSessionController';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    sessionId: number;
    initialResult?: string | null;
    initialStatus?: string | null;
}>();
const emit = defineEmits<{ ready: [result: string] }>();
const page = usePage();
const phase = ref(
    props.initialResult
        ? 'ready'
        : props.initialStatus === 'failed'
          ? 'failed'
          : 'generating',
);
const failureMessage =
    "Your interview was saved, but we couldn't generate feedback yet.";
let requestController: AbortController | null = null;
let disposed = false;
let retryTimer: ReturnType<typeof setTimeout> | undefined;

async function generateFeedback(startedAt = Date.now()): Promise<void> {
    if (requestController || disposed) {
        return;
    }

    phase.value = 'generating';
    requestController = new AbortController();
    const timeout = setTimeout(() => requestController?.abort(), 70_000);

    try {
        const response = await fetch(feedback.url(props.sessionId), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token as string,
            },
            signal: requestController.signal,
        });
        const data = await response.json();

        if (disposed) {
            return;
        }

        if (response.status === 202 && Date.now() - startedAt < 90_000) {
            retryTimer = setTimeout(
                () => void generateFeedback(startedAt),
                1500,
            );

            return;
        }

        if (
            !response.ok ||
            typeof data.result !== 'string' ||
            !data.result.trim()
        ) {
            throw new Error(failureMessage);
        }

        phase.value = 'ready';
        emit('ready', data.result);
    } catch {
        if (!disposed) {
            phase.value = 'failed';
        }
    } finally {
        clearTimeout(timeout);
        requestController = null;
    }
}

onMounted(() => {
    if (phase.value === 'generating') {
        void generateFeedback();
    }
});
onBeforeUnmount(() => {
    disposed = true;
    clearTimeout(retryTimer);
    requestController?.abort();
});
</script>

<template>
    <div data-test="interview-feedback" class="space-y-3 text-sm">
        <div
            v-if="phase === 'generating'"
            role="status"
            class="flex items-center gap-3 text-slate-600"
        >
            <Loader2 class="h-5 w-5 shrink-0 animate-spin text-blue-600" />
            <div>
                <p class="font-semibold">Generating your feedback...</p>
                <p class="mt-1">
                    Your answers are saved. AI is analyzing your interview.
                </p>
            </div>
        </div>
        <div v-else-if="phase === 'failed'" role="alert" class="space-y-3">
            <p>{{ failureMessage }}</p>
            <Button variant="outline" @click="generateFeedback()"
                ><RotateCcw class="mr-2 h-4 w-4" />Retry Feedback</Button
            >
        </div>
        <p v-else class="text-emerald-700">Your feedback is ready.</p>
    </div>
</template>
