<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AiAssistantForm from '@/components/ResumeEditor/AiAssistantForm.vue';
import resumeEditor from '@/routes/resume-editor';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Resumes',
                href: '/resumes',
            },
            {
                title: 'AI Assistant',
                href: '#',
            },
        ],
    },
});

const page = usePage();
const props = computed(() => page.props as any);
</script>

<template>
    <Head title="AI Resume Assistant" />

    <div class="flex-1 overflow-y-auto p-6">
        <div class="mx-auto max-w-2xl">
            <div class="mb-4 flex items-center justify-between">
                <Link
                    :href="resumeEditor.show.url(props.resume.id)"
                    class="flex items-center gap-1 text-sm text-foreground/60 hover:text-foreground"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to editor
                </Link>
                <span class="text-sm font-medium text-foreground/70">
                    {{ props.resume?.title }}
                </span>
            </div>

            <AiAssistantForm
                :resume="props.resume"
                :ai-messages="props.aiMessages"
            />
        </div>
    </div>
</template>
