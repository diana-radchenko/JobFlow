<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, Trash2 } from 'lucide-vue-next';
import { stringForHuman } from '@/helpers/strings';
import type { InterviewHistorySession } from '@/types/interview-center';
import { results as interviewSessionResults } from '@/actions/App/Http/Controllers/InterviewSessionController';

defineProps<{ session: InterviewHistorySession }>();

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
        class="group grid gap-4 rounded-2xl border border-[#E7ECF3] bg-white p-4 transition hover:border-[#CBD5E1] hover:shadow-[0_8px_24px_rgba(7,31,73,0.08)] sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
    >
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="font-semibold text-[#14213D]">
                    {{ interviewTypeLabel(session.type) }}
                    <span class="font-normal text-[#667085]">·</span>
                    {{ stringForHuman(session.complexity) }}
                </h4>
                <span
                    class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                >
                    Completed
                </span>
            </div>
            <p class="mt-1 truncate text-sm text-[#667085]">
                {{ session.resume?.title || 'General resume context' }}
                <template v-if="session.work_job">
                    · {{ session.work_job.title }}
                </template>
            </p>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-[#667085]">
                <CalendarDays class="h-3.5 w-3.5" />
                {{ formatDate(session.created_at) }}
            </p>
        </div>

        <div class="flex items-center gap-2 sm:justify-end">
            <Link
                :href="interviewSessionResults.url(session.id)"
                class="inline-flex h-9 items-center justify-center rounded-lg border border-[#D7DEE8] bg-white px-3 text-xs font-semibold text-[#0A2E48] transition hover:border-[#0A2E48] hover:bg-[#F5F7FB]"
            >
                View Results
            </Link>
            <button
                type="button"
                :aria-label="`Delete interview ${session.id}`"
                title="Delete interview"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-600 transition hover:border-red-200 hover:bg-red-100 focus-visible:ring-2 focus-visible:ring-red-300 focus-visible:outline-none"
                @click.stop="$emit('delete', session)"
            >
                <Trash2 class="h-4 w-4" />
            </button>
        </div>
    </article>
</template>
