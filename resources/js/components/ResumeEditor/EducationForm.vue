<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ChevronLeft, ChevronRight, Plus, Trash2, Edit2, Save } from 'lucide-vue-next';

const props = defineProps<{
    educations: any[];
}>();

interface Emits {
    nextSection: [section: string];
}

defineEmits<Emits>();

const degreeOptions: string[] = [
    'High School',
    'Certificate',
    'Associate',
    'Bachelors',
    'Masters',
    'Doctorate',
    'Postdoctoral Researcher',
];

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
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
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
                            <h4 class="font-semibold text-foreground">{{ edu.degree }}</h4>
                            <p class="text-sm text-foreground/70">{{ edu.institution }}</p>
                            <p v-if="edu.field_of_study" class="text-sm text-foreground/70">
                                {{ edu.field_of_study }}
                            </p>
                            <p v-if="edu.start_date" class="text-xs text-foreground/60 mt-1">
                                {{ formatDate(edu.start_date) }} 
                                <span v-if="edu.end_date">- {{ formatDate(edu.end_date) }}</span>
                            </p>
                            <p v-if="edu.description" class="text-sm text-foreground/70 mt-2">
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
                <div v-if="showForm" class="rounded-lg border border-border p-4 bg-muted/30">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="degree" class="block text-sm font-medium mb-1">
                                    Degree *
                                </label>
                                <Select v-model="form.degree">
                                    <SelectTrigger id="degree">
                                        <SelectValue placeholder="Select degree level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="deg in degreeOptions" :key="deg" :value="deg">
                                            {{ deg }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label for="institution" class="block text-sm font-medium mb-1">
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
                            <label for="field" class="block text-sm font-medium mb-1">
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
                                <label for="start_date" class="block text-sm font-medium mb-1">
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
                                <label for="end_date" class="block text-sm font-medium mb-1">
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
                            <label for="description" class="block text-sm font-medium mb-1">
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
                                <Save class="h-4 w-4 mr-2" />
                                {{ editingId ? 'Update' : 'Add' }} Education
                            </Button>
                            <Button type="button" variant="outline" @click="resetForm">
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
                        <Plus class="h-4 w-4 mr-2" />
                        Add Education
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 pt-4 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'workExperience')"
                    >
                        <ChevronLeft class="h-4 w-4 mr-2" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'skills')"
                    >
                        Next
                        <ChevronRight class="h-4 w-4 ml-2" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
