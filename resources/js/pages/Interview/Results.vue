<script setup lang="ts">
import '../../../css/interview-readability.css';
import { Head, Link } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { ArrowLeft, RotateCcw } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';
import InterviewFeedback from '@/components/interview/InterviewFeedback.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { stringForHuman } from '@/helpers/strings';
import { interviewPreparation } from '@/routes';

const props = defineProps<{
    session: {
        id: number;
        type: string;
        complexity: string;
        feedback_status?: string | null;
    };
    result: string | null;
    context: {
        resume_title?: string | null;
        job_title?: string | null;
        company?: string | null;
    };
}>();

const generatedResult = ref<string | null>(null);
watch(
    () => props.session.id,
    () => {
        generatedResult.value = null;
    },
);
const displayedResult = computed(() => props.result || generatedResult.value);
const resultHtml = computed(() =>
    displayedResult.value
        ? DOMPurify.sanitize(
              marked.parse(
                  displayedResult.value.replace(
                      /^(?:\*\*)?(Overall Assessment|Strengths|Areas to Improve|Recommendation)(?:\*\*)?:?\s*$/gm,
                      '## $1\n',
                  ),
                  { async: false },
              ) as string,
          )
        : '',
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Interview', href: interviewPreparation() },
            { title: 'Interview Results' },
        ],
    },
});
</script>

<template>
    <Head title="Interview Results" />
    <div class="jobflow-page interview-readability mx-auto max-w-4xl font-sans">
        <div class="mb-7">
            <p class="mb-2 text-sm font-semibold text-emerald-700">
                Interview completed
            </p>
            <h1 class="jobflow-page-title">Interview Results</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ stringForHuman(session.type) }} ·
                {{ stringForHuman(session.complexity) }} ·
                {{ context.job_title || 'General interview' }}
            </p>
        </div>
        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
            <CardContent class="p-6 sm:p-8">
                <div
                    v-if="displayedResult"
                    data-test="interview-result"
                    class="max-w-none text-[15px] leading-relaxed break-words text-slate-800 [&_h1]:mt-7 [&_h1]:mb-3 [&_h1]:text-lg [&_h1]:font-bold [&_h2]:mt-7 [&_h2]:mb-3 [&_h2]:text-lg [&_h2]:font-bold [&_h3]:mt-7 [&_h3]:mb-3 [&_h3]:text-lg [&_h3]:font-bold [&_li]:my-2 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mb-4 [&_strong]:font-semibold [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&>:first-child]:mt-0"
                    v-html="resultHtml"
                />
                <InterviewFeedback
                    v-else
                    :key="session.id"
                    :session-id="session.id"
                    :initial-status="session.feedback_status"
                    @ready="generatedResult = $event"
                />
            </CardContent>
        </Card>
        <div class="mt-5 flex flex-wrap gap-3">
            <Button as-child variant="outline"
                ><Link :href="interviewPreparation()"
                    ><ArrowLeft class="mr-2 h-4 w-4" />Interview Center</Link
                ></Button
            >
            <Button as-child
                ><Link :href="interviewPreparation()"
                    ><RotateCcw class="mr-2 h-4 w-4" />Practice Again</Link
                ></Button
            >
        </div>
    </div>
</template>
