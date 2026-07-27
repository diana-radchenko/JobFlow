<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
    ChevronUp,
    ChevronDown,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { ProjectTypeEnum } from '@/enums/laravel-models-enums';
import { formatDateForInput } from '@/helpers/dates';
import { stringForHuman } from '@/helpers/strings';
import items from '@/routes/resume-editor/items';
import project from '@/routes/resume-editor/project';
import type { ProjectType } from '@/types/laravel-models';

interface Props {
    resume: { id: number; title: string };
    projects: any[];
}

const props = defineProps<Props>();

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const typeOptions: ProjectType[] = Object.values(ProjectTypeEnum);
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

const includedIds = computed(() =>
    props.projects
        .filter((p) => p.included)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
        .map((p) => p.id),
);

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(project.update.url([props.resume.id, editingId.value]), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(project.store.url(props.resume.id), {
            onSuccess: () => resetForm(),
        });
    }
};

const editProject = (p: any) => {
    editingId.value = p.id;
    form.title = p.title;
    form.type = p.type;
    form.description = p.description || '';
    form.url = p.url || '';
    form.start_date = formatDateForInput(p.start_date);
    form.end_date = formatDateForInput(p.end_date);
    showForm.value = true;
};

const deleteProject = (id: number) => {
    if (confirm('Are you sure you want to delete this project?')) {
        useForm().delete(project.destroy.url([props.resume.id, id]));
    }
};

const toggleInclude = (p: any) => {
    router.post(
        items.toggle.url([props.resume.id, 'project', p.id]),
        {},
        { preserveScroll: true },
    );
};

const moveItem = (p: any, direction: -1 | 1) => {
    const ids = [...includedIds.value];
    const index = ids.indexOf(p.id);
    const swapIndex = index + direction;

    if (index === -1 || swapIndex < 0 || swapIndex >= ids.length) {
        return;
    }

    [ids[index], ids[swapIndex]] = [ids[swapIndex], ids[index]];

    router.post(
        items.reorder.url([props.resume.id, 'project']),
        { ids },
        { preserveScroll: true },
    );
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
                <CardTitle>Projects, Achievements & Research</CardTitle>
                <CardDescription>
                    Choose which projects to include in "{{ resume.title }}"
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing projects -->
                <div v-if="projects.length > 0" class="space-y-4">
                    <div
                        v-for="p in projects"
                        :key="p.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                        :class="{ 'opacity-50': !p.included }"
                    >
                        <div class="flex items-start gap-3">
                            <Checkbox
                                :model-value="p.included"
                                @update:model-value="toggleInclude(p)"
                            />
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-foreground">
                                        {{ p.title }}
                                    </h4>
                                    <span
                                        class="inline-block rounded bg-primary/10 px-2 py-1 text-xs text-primary"
                                    >
                                        {{ stringForHuman(p.type) }}
                                    </span>
                                </div>
                                <p
                                    v-if="p.start_date"
                                    class="mt-1 text-xs text-foreground/60"
                                >
                                    {{ formatDate(p.start_date) }}
                                    <span v-if="p.end_date"
                                        >- {{ formatDate(p.end_date) }}</span
                                    >
                                </p>
                                <p
                                    v-if="p.description"
                                    class="mt-2 text-sm text-foreground/70"
                                >
                                    {{ p.description }}
                                </p>
                                <a
                                    v-if="p.url"
                                    :href="p.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-2 inline-block text-xs text-primary hover:underline"
                                >
                                    View Project →
                                </a>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="editProject(p)"
                                >
                                    <Edit2 class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="deleteProject(p.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <!-- <div v-if="p.included" class="flex gap-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="includedIds[0] === p.id"
                                    @click="moveItem(p, -1)"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="
                                        includedIds[includedIds.length - 1] ===
                                        p.id
                                    "
                                    @click="moveItem(p, 1)"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </Button>
                            </div> -->
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
                        Add Project, Achievement, or Research
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
                        @click="$emit('nextSection', 'volunteer')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
