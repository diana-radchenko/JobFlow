<script setup lang="ts">
import { ArrowRight, CalendarClock, Video } from 'lucide-vue-next';
import { stringForHuman } from '@/helpers/strings';
import type { UpcomingInterview } from '@/types/interview-center';

defineProps<{ interview: UpcomingInterview }>();

defineEmits<{
    prepare: [interview: UpcomingInterview];
}>();

function formatDateTime(interview: UpcomingInterview): string {
    try {
        return new Intl.DateTimeFormat('en-US', {
            timeZone: interview.timezone || 'UTC',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            timeZoneName: 'short',
        }).format(new Date(interview.scheduled_at));
    } catch {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(interview.scheduled_at));
    }
}
</script>

<template>
    <article
        :data-test="`upcoming-interview-${interview.id}`"
        class="rounded-2xl border border-[#E7ECF3] bg-white p-4 transition hover:border-[#C7D7EA] hover:shadow-[0_8px_24px_rgba(7,31,73,0.08)]"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h4 class="truncate font-semibold text-[#14213D]">
                    {{ interview.work_job?.title || 'Scheduled interview' }}
                </h4>
                <p class="mt-0.5 truncate text-sm text-[#667085]">
                    {{ interview.work_job?.company || 'Employer interview' }}
                </p>
            </div>
            <span
                v-if="interview.interview_format"
                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#EEF3FA] px-2.5 py-1 text-[11px] font-semibold text-[#0A2E48]"
            >
                <Video class="h-3 w-3" />
                {{ stringForHuman(interview.interview_format) }}
            </span>
        </div>
        <p class="mt-3 flex items-center gap-1.5 text-xs text-[#667085]">
            <CalendarClock class="h-3.5 w-3.5" />
            {{ formatDateTime(interview) }}
        </p>
        <button
            type="button"
            class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#0B2F66] transition hover:text-[#3157D5] focus-visible:ring-2 focus-visible:ring-[#4F6FEF]/30 focus-visible:outline-none"
            @click="$emit('prepare', interview)"
        >
            Prepare <ArrowRight class="h-3.5 w-3.5" />
        </button>
    </article>
</template>
