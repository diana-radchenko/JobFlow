<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Copy, FileText, Pencil, Plus, Sparkles, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import resumeEditor from '@/routes/resume-editor';
import resumesUrl from '@/routes/resumes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Resumes',
                href: '/resumes',
            },
        ],
    },
});

interface ResumeSummary {
    id: number;
    title: string;
    updated_at: string;
    skills_count: number;
    projects_count: number;
    educations_count: number;
    work_experiences_count: number;
}

defineProps<{
    resumes: ResumeSummary[];
}>();

const showCreateForm = ref(false);
const createForm = useForm({ title: '' });

const submitCreate = () => {
    createForm.post(resumesUrl.store.url(), {
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
};

const editingId = ref<number | null>(null);
const renameForm = useForm({ title: '' });

const startRename = (resume: ResumeSummary) => {
    editingId.value = resume.id;
    renameForm.title = resume.title;
};

const submitRename = (resume: ResumeSummary) => {
    renameForm.put(resumesUrl.update.url(resume.id), {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const duplicateResume = (resume: ResumeSummary) => {
    useForm({}).post(resumesUrl.duplicate.url(resume.id));
};

const deleteResume = (resume: ResumeSummary) => {
    if (confirm(`Delete "${resume.title}"? This cannot be undone.`)) {
        useForm({}).delete(resumesUrl.destroy.url(resume.id));
    }
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
</script>

<template>
    <Head title="My Resumes" />

    <div class="mx-auto max-w-4xl space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-semibold">My Resumes</h1>
                <p class="text-sm text-foreground/60">
                    Keep separate resumes to highlight different skills and
                    experience for different job applications.
                </p>
            </div>
            <Button
                v-if="!showCreateForm"
                type="button"
                @click="showCreateForm = true"
            >
                <Plus class="mr-2 h-4 w-4" />
                New Resume
            </Button>

        <Card v-if="showCreateForm">
            <CardContent class="pt-6">
                <form
                    @submit.prevent="submitCreate"
                    class="flex items-end gap-3"
                >
                    <div class="flex-1">
                        <label
                            for="new-resume-title"
                            class="mb-1 block text-sm font-medium"
                        >
                            Resume title
                        </label>
                        <Input
                            id="new-resume-title"
                            v-model="createForm.title"
                            placeholder="e.g., Frontend Developer - Acme Corp"
                            required
                        />
                    </div>
                    <Button type="submit" :disabled="createForm.processing">
                        Create
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="
                            showCreateForm = false;
                            createForm.reset();
                        "
                    >
                        Cancel
                    </Button>
                </form>
            </CardContent>
        </Card>

        <div v-if="resumes.length === 0 && !showCreateForm" class="py-12 text-center">
            <FileText class="mx-auto mb-3 h-10 w-10 text-foreground/30" />
            <p class="text-foreground/60">
                You don't have any resumes yet. Create your first one to get
                started.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Card v-for="resume in resumes" :key="resume.id">
                <CardHeader>
                    <form
                        v-if="editingId === resume.id"
                        @submit.prevent="submitRename(resume)"
                        class="flex gap-2"
                    >
                        <Input
                            v-model="renameForm.title"
                            autofocus
                            required
                        />
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="renameForm.processing"
                        >
                            Save
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="editingId = null"
                        >
                            Cancel
                        </Button>
                    </form>
                    <template v-else>
                        <CardTitle>{{ resume.title }}</CardTitle>
                        <CardDescription>
                            Updated {{ formatDate(resume.updated_at) }}
                        </CardDescription>
                    </template>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap gap-4 text-xs text-foreground/60">
                        <span>{{ resume.work_experiences_count }} experience</span>
                        <span>{{ resume.educations_count }} education</span>
                        <span>{{ resume.skills_count }} skills</span>
                        <span>{{ resume.projects_count }} projects</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button as-child size="sm">
                            <a :href="resumeEditor.show.url(resume.id)">
                                Edit
                            </a>
                        </Button>
                        <Button as-child size="sm" variant="outline">
                            <a :href="resumeEditor.assistant.url(resume.id)">
                                <Sparkles class="mr-1 h-4 w-4" />
                                Build with AI
                            </a>
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="startRename(resume)"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="duplicateResume(resume)"
                        >
                            <Copy class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="deleteResume(resume)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
