<script setup lang="ts">
import '../../../css/interview-readability.css';
import { Head, Link } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { ArrowLeft, RotateCcw } from 'lucide-vue-next';
import { marked } from 'marked';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { stringForHuman } from '@/helpers/strings';
import { interviewPreparation } from '@/routes';

const props = defineProps<{
    session: { id: number; type: string; complexity: string };
    result: string | null;
    context: {
        resume_title?: string | null;
        job_title?: string | null;
        company?: string | null;
    };
}>();

const resultHtml = computed(() =>
    props.result
        ? DOMPurify.sanitize(
              marked.parse(props.result, { async: false }) as string,
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
                AI Interview Completed
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
                    v-if="result"
                    class="prose prose-slate max-w-none [&_h2]:mt-7 [&_h2]:text-lg [&_h2]:font-bold"
                    v-html="resultHtml"
                />
                <p v-else class="text-sm text-slate-500">
                    Your result is not available yet. Return to Interview Center
                    and try another AI interview.
                </p>
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
