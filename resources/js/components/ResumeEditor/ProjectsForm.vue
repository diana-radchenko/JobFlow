<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
} from 'lucide-vue-next';
import { ProjectTypeEnum } from '@/enums/laravel-models-enums';
import type { ProjectType } from '@/types/laravel-models';
import { stringForHuman } from '@/helpers/strings';

interface Props {
    projects: any[];
}

const props = defineProps<Props>();

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const typeOptions: ProjectType[] = Object.values(ProjectTypeEnum);
const typeLabels: Record<string, string> = {
    project: 'Project',
    achievement: 'Achievement',
};

const form = useForm({
    title: '',
    type: 'project',
    description: '',
    url: '',
    start_date: '',
    end_date: '',
});

const showForm = ref(false);
const editingId = ref<number | null>(null);

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(`/resume-editor/project/${editingId.value}`, {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post('/resume-editor/project', {
            onSuccess: () => resetForm(),
        });
    }
};

const editProject = (project: any) => {
    editingId.value = project.id;
    form.title = project.title;
    form.type = project.type;
    form.description = project.description || '';
    form.url = project.url || '';
    form.start_date = project.start_date || '';
    form.end_date = project.end_date || '';
    showForm.value = true;
};

const deleteProject = (id: number) => {
    if (confirm('Are you sure you want to delete this project?')) {
        useForm().delete(`/resume-editor/project/${id}`);
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
    });
};
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Projects & Achievements</CardTitle>
                <CardDescription>
                    Showcase your work and accomplishments
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing projects -->
                <div v-if="projects.length > 0" class="space-y-4">
                    <div
                        v-for="project in projects"
                        :key="project.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                    >
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-semibold text-foreground">
                                    {{ project.title }}
                                </h4>
                                <span
                                    class="inline-block rounded bg-primary/10 px-2 py-1 text-xs text-primary"
                                >
                                    {{ stringForHuman(project.type) }}
                                </span>
                            </div>
                            <p
                                v-if="project.start_date"
                                class="mt-1 text-xs text-foreground/60"
                            >
                                {{ formatDate(project.start_date) }}
                                <span v-if="project.end_date"
                                    >- {{ formatDate(project.end_date) }}</span
                                >
                            </p>
                            <p
                                v-if="project.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ project.description }}
                            </p>
                            <a
                                v-if="project.url"
                                :href="project.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-block text-xs text-primary hover:underline"
                            >
                                View Project →
                            </a>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editProject(project)"
                            >
                                <Edit2 class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="deleteProject(project.id)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div
                    v-if="showForm"
                    class="rounded-lg border border-border bg-muted/30 p-4"
                >
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    for="title"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Title *
                                </label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="e.g., E-commerce Platform"
                                    required
                                />
                            </div>
                            <div>
                                <label
                                    for="type"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Type *
                                </label>
                                <Select v-model="form.type">
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="type in typeOptions"
                                            :key="type"
                                            :value="type"
                                        >
                                            {{ stringForHuman(type) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div>
                            <label
                                for="description"
                                class="mb-1 block text-sm font-medium"
                            >
                                Description
                            </label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe your project or achievement"
                                :rows="4"
                            />
                        </div>

                        <div>
                            <label
                                for="url"
                                class="mb-1 block text-sm font-medium"
                            >
                                Project URL
                            </label>
                            <Input
                                id="url"
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.com"
                            />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    for="start_date"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Start Date
                                </label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                />
                            </div>
                            <div>
                                <label
                                    for="end_date"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    End Date
                                </label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                />
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ editingId ? 'Update' : 'Add' }} Project
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="resetForm"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Add button -->
                <div v-if="!showForm">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showForm = true"
                        class="w-full"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Add Project or Achievement
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'skills')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'additionalInfo')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
