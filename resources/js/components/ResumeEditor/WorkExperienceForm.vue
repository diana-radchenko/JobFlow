<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
} from 'lucide-vue-next';
import type { WorkExperience } from '@/types/laravel-models';
import { formatDateForInput } from '@/helpers/dates';

interface Props {
    workExperiences: WorkExperience[];
}

interface Emits {
    nextSection: [section: string];
}

defineProps<Props>();
defineEmits<Emits>();



const form = useForm({
    company_name: '',
    job_title: '',
    city: '',
    country: '',
    is_remote: false,
    start_date: '',
    end_date: '',
    is_current: false,
    description: '',
});

const showForm = ref(false);
const editingId = ref<number | null>(null);

const formatWorkLocationLine = (exp: WorkExperience): string => {
    const place = [exp.city, exp.country]
        .filter((x) => x != null && String(x).trim() !== '')
        .join(', ');
    if (exp.is_remote && place) {
        return `Remote · ${place}`;
    }
    if (exp.is_remote) {
        return 'Remote';
    }
    return place;
};

const resetForm = () => {
    form.reset();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(`/resume-editor/work-experience/${editingId.value}`, {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post('/resume-editor/work-experience', {
            onSuccess: () => resetForm(),
        });
    }
};

const editExperience = (experience: WorkExperience) => {
    editingId.value = experience.id;
    form.company_name = experience.company_name;
    form.job_title = experience.job_title;
    form.city = experience.city ?? '';
    form.country = experience.country ?? '';
    form.is_remote = experience.is_remote;
    form.start_date = formatDateForInput(experience.start_date);
    form.end_date = formatDateForInput(experience.end_date);
    form.is_current = experience.is_current;
    form.description = experience.description || '';
    showForm.value = true;
};

const deleteExperience = (id: number) => {
    if (confirm('Are you sure you want to delete this work experience?')) {
        useForm().delete(`/resume-editor/work-experience/${id}`);
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
                <CardTitle>Work Experience</CardTitle>
                <CardDescription>
                    Add your professional work history
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing experiences -->
                <div v-if="workExperiences.length > 0" class="space-y-4">
                    <div
                        v-for="exp in workExperiences"
                        :key="exp.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                    >
                        <div class="flex-1">
                            <h4 class="font-semibold text-foreground">
                                {{ exp.job_title }}
                            </h4>
                            <p class="text-sm text-foreground/70">
                                {{ exp.company_name }}
                            </p>
                            <p class="mt-1 text-xs text-foreground/60">
                                {{ formatDate(exp.start_date) }} -
                                <span v-if="exp.is_current">Present</span>
                                <span v-else>{{
                                    exp.end_date
                                        ? formatDate(exp.end_date)
                                        : ''
                                }}</span>
                            </p>
                            <p
                                v-if="formatWorkLocationLine(exp)"
                                class="text-xs text-foreground/60"
                            >
                                📍 {{ formatWorkLocationLine(exp) }}
                            </p>
                            <p
                                v-if="exp.description"
                                class="mt-2 text-sm text-foreground/70"
                            >
                                {{ exp.description }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editExperience(exp)"
                            >
                                <Edit2 class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="deleteExperience(exp.id)"
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
                            <div class="grid gap-2">
                                <Label for="company">Company Name *</Label>
                                <Input
                                    id="company"
                                    v-model="form.company_name"
                                    name="company_name"
                                    placeholder="e.g., Acme Corporation"
                                    required
                                />
                                <InputError :message="form.errors.company_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="title">Job Title *</Label>
                                <Input
                                    id="title"
                                    v-model="form.job_title"
                                    name="job_title"
                                    placeholder="e.g., Senior Developer"
                                    required
                                />
                                <InputError :message="form.errors.job_title" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="work_city">City</Label>
                                <Input
                                    id="work_city"
                                    v-model="form.city"
                                    type="text"
                                    name="city"
                                    autocomplete="address-level2"
                                />
                                <InputError :message="form.errors.city" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="work_country">Country</Label>
                                <Input
                                    id="work_country"
                                    v-model="form.country"
                                    type="text"
                                    name="country"
                                    autocomplete="country-name"
                                />
                                <InputError :message="form.errors.country" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_remote"
                                v-model:checked="form.is_remote"
                            />
                            <Label for="is_remote" class="cursor-pointer font-normal">
                                Remote role
                            </Label>
                        </div>
                        <InputError :message="form.errors.is_remote" />

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    name="start_date"
                                    required
                                />
                                <InputError :message="form.errors.start_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    name="end_date"
                                    :disabled="form.is_current"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_current"
                                v-model:checked="form.is_current"
                            />
                            <Label
                                for="is_current"
                                class="cursor-pointer font-normal"
                            >
                                I currently work here
                            </Label>
                        </div>
                        <InputError :message="form.errors.is_current" />

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                name="description"
                                placeholder="Describe your responsibilities and achievements"
                                :rows="4"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ editingId ? 'Update' : 'Add' }} Experience
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
                        Add Work Experience
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'personalInfo')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'education')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
