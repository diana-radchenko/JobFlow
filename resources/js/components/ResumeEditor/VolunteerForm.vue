<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Trash2,
    Edit2,
    Save,
} from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateForInput } from '@/helpers/dates';
import items from '@/routes/resume-editor/items';
import volunteerExperience from '@/routes/resume-editor/volunteer-experience';
import type { VolunteerExperience } from '@/types/laravel-models';

interface Props {
    resume: { id: number; title: string };
    volunteerExperiences: VolunteerExperience[];
}

interface Emits {
    nextSection: [section: string];
}

const props = defineProps<Props>();
defineEmits<Emits>();

const form = useForm({
    organization: '',
    role: '',
    description: '',
    url: '',
    city: '',
    country: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

const showForm = ref(false);
const editingId = ref<number | null>(null);

const resetForm = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    if (editingId.value) {
        form.put(
            volunteerExperience.update.url([props.resume.id, editingId.value]),
            { onSuccess: () => resetForm() },
        );
    } else {
        form.post(volunteerExperience.store.url(props.resume.id), {
            onSuccess: () => resetForm(),
        });
    }
};

const editItem = (item: VolunteerExperience) => {
    editingId.value = item.id;
    form.organization = item.organization;
    form.role = item.role;
    form.description = item.description ?? '';
    form.url = item.url ?? '';
    form.city = item.city ?? '';
    form.country = item.country ?? '';
    form.start_date = formatDateForInput(item.start_date);
    form.end_date = formatDateForInput(item.end_date);
    form.is_current = item.is_current;
    showForm.value = true;
};

const deleteItem = (id: number) => {
    if (confirm('Are you sure you want to delete this volunteer experience?')) {
        useForm().delete(
            volunteerExperience.destroy.url([props.resume.id, id]),
        );
    }
};

const toggleInclude = (item: VolunteerExperience) => {
    router.post(
        items.toggle.url([props.resume.id, 'volunteer-experience', item.id]),
        {},
        { preserveScroll: true },
    );
};

const formatLocationLine = (item: VolunteerExperience): string =>
    [item.city, item.country]
        .filter((x) => x != null && String(x).trim() !== '')
        .join(', ');

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
    });
</script>

<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>Volunteer &amp; Community</CardTitle>
                <CardDescription>
                    Choose which volunteer and community work to include in "{{
                        resume.title
                    }}"
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- List of existing entries -->
                <div v-if="volunteerExperiences.length > 0" class="space-y-4">
                    <div
                        v-for="item in volunteerExperiences"
                        :key="item.id"
                        class="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                        :class="{ 'opacity-50': !item.included }"
                    >
                        <div class="flex items-start gap-3">
                            <Checkbox
                                :model-value="item.included"
                                @update:model-value="toggleInclude(item)"
                            />
                            <div class="flex-1">
                                <h4 class="font-semibold text-foreground">
                                    {{ item.role }}
                                </h4>
                                <p class="text-sm text-foreground/70">
                                    {{ item.organization }}
                                </p>
                                <p class="mt-1 text-xs text-foreground/60">
                                    {{ formatDate(item.start_date) }} -
                                    <span v-if="item.is_current">Present</span>
                                    <span v-else>{{
                                        item.end_date
                                            ? formatDate(item.end_date)
                                            : ''
                                    }}</span>
                                </p>
                                <p
                                    v-if="formatLocationLine(item)"
                                    class="text-xs text-foreground/60"
                                >
                                    📍 {{ formatLocationLine(item) }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-2 text-sm text-foreground/70"
                                >
                                    {{ item.description }}
                                </p>
                                <a
                                    v-if="item.url"
                                    :href="item.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-2 inline-block text-xs text-primary hover:underline"
                                >
                                    View Organization →
                                </a>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editItem(item)"
                            >
                                <Edit2 class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="deleteItem(item.id)"
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
                                <Label for="organization">Organization *</Label>
                                <Input
                                    id="organization"
                                    v-model="form.organization"
                                    placeholder="e.g., Red Cross"
                                    required
                                />
                                <InputError
                                    :message="form.errors.organization"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="role">Role *</Label>
                                <Input
                                    id="role"
                                    v-model="form.role"
                                    placeholder="e.g., Volunteer Coordinator"
                                    required
                                />
                                <InputError :message="form.errors.role" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what you did and the impact you had"
                                :rows="4"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="url">Organization URL</Label>
                            <Input
                                id="url"
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.org"
                            />
                            <InputError :message="form.errors.url" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="city">City</Label>
                                <Input
                                    id="city"
                                    v-model="form.city"
                                    placeholder="e.g., Tashkent"
                                />
                                <InputError :message="form.errors.city" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="country">Country</Label>
                                <Input
                                    id="country"
                                    v-model="form.country"
                                    placeholder="e.g., Uzbekistan"
                                />
                                <InputError :message="form.errors.country" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
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
                                    :disabled="form.is_current"
                                />
                                <InputError :message="form.errors.end_date" />
                            </div>
                        </div>

                        <div>
                            <Checkbox
                                id="is_current"
                                v-model="form.is_current"
                                label="I currently volunteer here"
                            />
                            <InputError :message="form.errors.is_current" />
                        </div>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ editingId ? 'Update' : 'Add' }} Volunteer
                                Experience
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
                        Add Volunteer Experience
                    </Button>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="$emit('nextSection', 'projects')"
                    >
                        <ChevronLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="$emit('nextSection', 'leadership')"
                    >
                        Next
                        <ChevronRight class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
