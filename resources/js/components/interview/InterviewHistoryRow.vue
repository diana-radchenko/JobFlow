<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { stringForHuman } from '@/helpers/strings';
import type { InterviewHistorySession } from '@/types/interview-center';
import { results as interviewSessionResults } from '@/actions/App/Http/Controllers/InterviewSessionController';

const props = defineProps<{ session: InterviewHistorySession }>();

const typeBadge = computed(() => {
    if (props.session.type === 'resume-based') {
        return { label: 'AI', color: 'bg-violet-50 text-violet-600' };
    }

    if (props.session.type === 'technical') {
        return { label: 'T', color: 'bg-blue-50 text-blue-600' };
    }

    if (props.session.type === 'behavioral') {
        return { label: 'B', color: 'bg-emerald-50 text-emerald-600' };
    }

    return { label: 'C', color: 'bg-amber-50 text-amber-600' };
});

defineEmits<{
    delete: [session: InterviewHistorySession];
}>();

function interviewTypeLabel(type: string): string {
    return type === 'resume-based' ? 'AI Personalized' : stringForHuman(type);
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}
</script>

<template>
    <article
        :data-test="`interview-history-${session.id}`"
        class="group flex flex-wrap items-center gap-3 border-b border-[#EDF0F7] py-5 last:border-b-0"
    >
        <span
            :class="typeBadge.color"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-base font-semibold ring-1 ring-white/60 ring-inset"
        >
            {{ typeBadge.label }}
        </span>
        <div class="min-w-0 flex-1 basis-36">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-[14px] leading-5 font-semibold text-[#14213D]">
                    {{ interviewTypeLabel(session.type) }}
                    <span class="font-normal text-[#667085]">·</span>
                    {{ stringForHuman(session.complexity) }}
                </h4>
            </div>
            <p class="mt-0.5 truncate text-xs text-[#52658B]">
                {{ session.resume?.title || 'General resume context' }}
                <template v-if="session.work_job">
                    · {{ session.work_job.title }}
                </template>
            </p>
            <p
                class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-[#52658B]"
            >
                <CalendarDays class="h-3.5 w-3.5" />
                {{ formatDate(session.created_at) }}
                <span
                    class="rounded-md border border-emerald-100 bg-emerald-50/70 px-2 py-0.5 text-[13px] font-medium text-emerald-700"
                    >Completed</span
                >
            </p>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <Link
                :href="interviewSessionResults.url(session.id)"
                class="inline-flex h-9 items-center justify-center rounded-lg border border-[#E3E8F2] bg-white px-3 text-sm font-semibold text-[#0A2E48] transition hover:border-[#0A2E48] hover:bg-[#F5F7FB]"
            >
                View Results
            </Link>
            <button
                type="button"
                :aria-label="`Delete interview ${session.id}`"
                title="Delete interview"
                class="inline-flex h-9 w-8 items-center justify-center rounded-lg border border-red-100 bg-red-50/40 text-red-500 transition hover:border-red-200 hover:bg-red-100 focus-visible:ring-2 focus-visible:ring-red-300 focus-visible:outline-none"
                @click.stop="$emit('delete', session)"
            >
                <Trash2 class="h-4 w-4" />
            </button>
        </div>
    </article>
</template>
