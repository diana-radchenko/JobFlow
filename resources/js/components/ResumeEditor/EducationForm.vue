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
import { EducationDegreeEnum } from '@/enums/laravel-models-enums';
import { formatDateForInput } from '@/helpers/dates';
import { stringForHuman } from '@/helpers/strings';
import education from '@/routes/resume-editor/education';
import items from '@/routes/resume-editor/items';
import type { EducationDegree } from '@/types/laravel-models';

interface Props {
    resume: { id: number; title: string };
    educations: any[];
}

const props = defineProps<Props>();

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const degreeOptions: EducationDegree[] = Object.values(EducationDegreeEnum);

const form = useForm({
    degree: '',
    institution: '',
    field_of_study: '',
    start_date: '',
    end_date: '',
    description: '',
});

const showForm = ref(false);
const editingId = ref<number | null>(null);

const includedIds = computed(() =>
    props.educations
        .filter((edu) => edu.included)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
        .map((edu) => edu.id),
);

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(education.update.url([props.resume.id, editingId.value]), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(education.store.url(props.resume.id), {
            onSuccess: () => resetForm(),
        });
    }
};

const editEducation = (edu: any) => {
    editingId.value = edu.id;
    form.degree = edu.degree;
    form.institution = edu.institution;
    form.field_of_study = edu.field_of_study || '';
    form.start_date = formatDateForInput(edu.start_date);
    form.end_date = formatDateForInput(edu.end_date);
    form.description = edu.description || '';
    showForm.value = true;
};

const deleteEducation = (id: number) => {
    if (confirm('Are you sure you want to delete this education entry?')) {
        useForm().delete(education.destroy.url([props.resume.id, id]));
    }
};

const toggleInclude = (edu: any) => {
    router.post(
        items.toggle.url([props.resume.id, 'education', edu.id]),
        {},
        { preserveScroll: true },
    );
};

const moveItem = (edu: any, direction: -1 | 1) => {
    const ids = [...includedIds.value];
    const index = ids.indexOf(edu.id);
    const swapIndex = index + direction;

    if (index === -1 || swapIndex < 0 || swapIndex >= ids.length) {
        return;
    }

    [ids[index], ids[swapIndex]] = [ids[swapIndex], ids[index]];

    router.post(
        items.reorder.url([props.resume.id, 'education']),
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
                <CardTitle>Education</CardTitle>
                <CardDescription>
                    Choose which education entries to include in "{{
                        resume.title
                    }}"
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing educations -->
                <div v-if="educations.length > 0" class="space-y-4">
                    <div
                        v-for="edu in educations"
                        :key="edu.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                        :class="{ 'opacity-50': !edu.included }"
                    >
                        <div class="flex items-start gap-3">
                            <Checkbox
                                :model-value="edu.included"
                                @update:model-value="toggleInclude(edu)"
                            />
                            <div class="flex-1">
                                <h4 class="font-semibold text-foreground">
                                    {{ stringForHuman(edu.degree) }}
                                </h4>
                                <p class="text-sm text-foreground/70">
                                    {{ edu.institution }}
                                </p>
                                <p
                                    v-if="edu.field_of_study"
                                    class="text-sm text-foreground/70"
                                >
                                    {{ edu.field_of_study }}
                                </p>
                                <p
                                    v-if="edu.start_date"
                                    class="mt-1 text-xs text-foreground/60"
                                >
                                    {{ formatDate(edu.start_date) }}
                                    <span v-if="edu.end_date"
                                        >- {{ formatDate(edu.end_date) }}</span
                                    >
                                </p>
                                <p
                                    v-if="edu.description"
                                    class="mt-2 text-sm text-foreground/70"
                                >
                                    {{ edu.description }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="editEducation(edu)"
                                >
                                    <Edit2 class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="deleteEducation(edu.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                           <!--  <div v-if="edu.included" class="flex gap-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="includedIds[0] === edu.id"
                                    @click="moveItem(edu, -1)"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="
                                        includedIds[includedIds.length - 1] ===
                                        edu.id
                                    "
                                    @click="moveItem(edu, 1)"
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
                                    for="degree"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Degree *
                                </label>
                                <Select v-model="form.degree">
                                    <SelectTrigger id="degree">
                                        <SelectValue
                                            placeholder="Select degree level"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="deg in degreeOptions"
                                            :key="deg"
                                            :value="deg"
                                        >
                                            {{ stringForHuman(deg) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label
                                    for="institution"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Institution *
                                </label>
                                <Input
                                    id="institution"
                                    v-model="form.institution"
                                    placeholder="e.g., University of Example"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                for="field"
                                class="mb-1 block text-sm font-medium"
                            >
                                Field of Study *
                            </label>
                            <Input
                                id="field"
                                v-model="form.field_of_study"
                                placeholder="e.g., Computer Science"
                                required
                            />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    for="start_date"
                                    class="mb-1 block text-sm font-medium"
                                >
                                    Start Date *
                                </label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    required
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
                                placeholder="Additional details about your education"
                                :rows="3"
                            />
                        </div>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ editingId ? 'Update' : 'Add' }} Education
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
                        Add Education
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'workExperience')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'skills')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
