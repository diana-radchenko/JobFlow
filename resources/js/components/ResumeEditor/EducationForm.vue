<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
} from 'lucide-vue-next';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { EducationDegreeEnum } from '@/enums/laravel-models-enums';
import { stringForHuman } from '@/helpers/strings';
import type { EducationDegree } from '@/types/laravel-models';

defineProps<{
    educations: any[];
}>();

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

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(`/resume-editor/education/${editingId.value}`, {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post('/resume-editor/education', {
            onSuccess: () => resetForm(),
        });
    }
};

const editEducation = (education: any) => {
    editingId.value = education.id;
    form.degree = education.degree;
    form.institution = education.institution;
    form.field_of_study = education.field_of_study || '';
    form.start_date = education.start_date || '';
    form.end_date = education.end_date || '';
    form.description = education.description || '';
    showForm.value = true;
};

const deleteEducation = (id: number) => {
    if (confirm('Are you sure you want to delete this education entry?')) {
        useForm().delete(`/resume-editor/education/${id}`);
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
                <CardTitle>Education</CardTitle>
                <CardDescription>
                    Add your educational qualifications
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing educations -->
                <div v-if="educations.length > 0" class="space-y-4">
                    <div
                        v-for="edu in educations"
                        :key="edu.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                    >
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
