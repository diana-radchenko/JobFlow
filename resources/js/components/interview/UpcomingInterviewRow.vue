<script setup lang="ts">
import { CalendarClock, CalendarDays, Video } from 'lucide-vue-next';
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
        class="flex flex-wrap items-center gap-3 border-b border-[#EDF0F7] py-5 last:border-b-0"
    >
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#F8F6FF] to-[#EEECFF] text-[#6759FF] shadow-[0_4px_12px_rgba(112,71,235,0.06)]"
        >
            <CalendarDays class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1 basis-36">
            <h4 class="truncate text-[14px] font-semibold text-[#14213D]">
                {{ interview.work_job?.title || 'Scheduled interview' }}
            </h4>
            <p class="mt-1 truncate text-xs text-[#52658B]">
                {{ interview.work_job?.company || 'Employer interview' }}
            </p>
            <p
                class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-[13px] text-[#52658B]"
            >
                <CalendarClock class="h-3.5 w-3.5" />
                {{ formatDateTime(interview) }}
                <span
                    v-if="interview.interview_format"
                    class="inline-flex items-center gap-1"
                >
                    ·
                    <Video
                        v-if="interview.interview_format === 'video'"
                        class="ml-1 h-3 w-3"
                    />
                    {{ stringForHuman(interview.interview_format) }}
                </span>
            </p>
        </div>
        <button
            type="button"
            class="ml-auto inline-flex h-9 items-center justify-center rounded-lg border border-[#E3E8F2] bg-white px-4 text-sm font-semibold text-[#0B2F66] transition hover:border-[#4F6FEF] hover:text-[#3157D5] focus-visible:ring-2 focus-visible:ring-[#4F6FEF]/30 focus-visible:outline-none"
            @click="$emit('prepare', interview)"
        >
            Prepare
        </button>
    </article>
</template>
