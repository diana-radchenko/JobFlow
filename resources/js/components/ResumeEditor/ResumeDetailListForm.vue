<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Edit2,
    Plus,
    Save,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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

type SectionType = 'publication' | 'award-honor' | 'language';
type Item = Record<string, any> & { id: number; included?: boolean };
type Field = {
    key: string;
    label: string;
    inputType?: 'text' | 'date' | 'url' | 'textarea' | 'select';
    required?: boolean;
    placeholder?: string;
};
type SectionConfig = {
    title: string;
    singular: string;
    description: string;
    primary: string;
    secondary?: string;
    date?: string;
    descriptionField?: string;
    url?: string;
    fields: Field[];
};

const props = defineProps<{
    resume: { id: number; title: string };
    items: Item[];
    type: SectionType;
    previousSection: string;
    nextSection: string;
}>();

defineEmits<{ nextSection: [section: string] }>();

const configs: Record<SectionType, SectionConfig> = {
    publication: {
        title: 'Publications',
        singular: 'Publication',
        description: 'Articles, books, papers, and other published work',
        primary: 'title',
        secondary: 'publisher',
        date: 'publication_date',
        descriptionField: 'description',
        url: 'url',
        fields: [
            {
                key: 'title',
                label: 'Title',
                required: true,
                placeholder: 'e.g., The Future of Sustainable Finance',
            },
            {
                key: 'publisher',
                label: 'Publisher / Journal',
                placeholder: 'e.g., Harvard Business Review',
            },
            {
                key: 'publication_date',
                label: 'Publication Date',
                inputType: 'date',
            },
            {
                key: 'url',
                label: 'Publication URL',
                inputType: 'url',
                placeholder: 'https://example.com/article',
            },
            {
                key: 'description',
                label: 'Description',
                inputType: 'textarea',
                placeholder:
                    'Briefly describe the publication and your contribution',
            },
        ],
    },
    'award-honor': {
        title: 'Awards & Honors',
        singular: 'Award or Honor',
        description: 'Recognition, prizes, scholarships, and distinctions',
        primary: 'title',
        secondary: 'issuer',
        date: 'awarded_date',
        descriptionField: 'description',
        fields: [
            {
                key: 'title',
                label: 'Award / Honor',
                required: true,
                placeholder: 'e.g., Dean’s List',
            },
            {
                key: 'issuer',
                label: 'Issuing Organization',
                placeholder: 'e.g., University of California',
            },
            { key: 'awarded_date', label: 'Date Awarded', inputType: 'date' },
            {
                key: 'description',
                label: 'Description',
                inputType: 'textarea',
                placeholder:
                    'Add context, selection criteria, or achievement details',
            },
        ],
    },
    language: {
        title: 'Languages',
        singular: 'Language',
        description: 'Languages you speak and your proficiency level',
        primary: 'name',
        secondary: 'proficiency',
        fields: [
            {
                key: 'name',
                label: 'Language',
                required: true,
                placeholder: 'e.g., English',
            },
            {
                key: 'proficiency',
                label: 'Proficiency',
                inputType: 'select',
                required: true,
            },
        ],
    },
};

const config = computed(() => configs[props.type]);
const blankData = () =>
    Object.fromEntries(
        config.value.fields.map((field) => [
            field.key,
            field.inputType === 'select' ? 'Intermediate' : '',
        ]),
    );
const form = useForm<Record<string, string>>(blankData());
const showForm = ref(false);
const editingId = ref<number | null>(null);
const proficiencies = [
    'Native',
    'Fluent',
    'Professional',
    'Intermediate',
    'Basic',
];

const baseUrl = computed(
    () => `/resume-editor/${props.resume.id}/${props.type}`,
);

const resetForm = () => {
    form.defaults(blankData());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = false;
};

const submit = () => {
    const options = { onSuccess: resetForm, preserveScroll: true };
    if (editingId.value)
        form.put(`${baseUrl.value}/${editingId.value}`, options);
    else form.post(baseUrl.value, options);
};

const editItem = (item: Item) => {
    editingId.value = item.id;
    for (const field of config.value.fields) {
        const value = item[field.key];
        form[field.key] =
            field.inputType === 'date' && value
                ? String(value).slice(0, 10)
                : (value ?? '');
    }
    showForm.value = true;
};

const deleteItem = (item: Item) => {
    if (
        confirm(
            `Are you sure you want to delete this ${config.value.singular.toLowerCase()}?`,
        )
    ) {
        router.delete(`${baseUrl.value}/${item.id}`, { preserveScroll: true });
    }
};

const toggleInclude = (item: Item) =>
    router.post(
        `/resume-editor/${props.resume.id}/items/${props.type}/${item.id}/toggle`,
        {},
        { preserveScroll: true },
    );

const formatDate = (value?: string) =>
    value
        ? new Date(value).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'short',
          })
        : '';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ config.title }}</CardTitle>
            <CardDescription
                >{{ config.description }}. Choose which entries to include in
                "{{ resume.title }}".</CardDescription
            >
        </CardHeader>
        <CardContent class="space-y-6">
            <div v-if="items.length" class="space-y-4">
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="flex items-start justify-between gap-4 rounded-lg border p-4"
                    :class="{ 'opacity-50': !item.included }"
                >
                    <div class="flex items-start gap-3">
                        <Checkbox
                            :model-value="item.included"
                            @update:model-value="toggleInclude(item)"
                        />
                        <div>
                            <h4 class="font-semibold">
                                {{ item[config.primary] }}
                            </h4>
                            <p
                                v-if="
                                    config.secondary && item[config.secondary]
                                "
                                class="text-sm text-foreground/70"
                            >
                                {{ item[config.secondary] }}
                            </p>
                            <p
                                v-if="config.date && item[config.date]"
                                class="text-xs text-foreground/60"
                            >
                                {{ formatDate(item[config.date]) }}
                            </p>
                            <p
                                v-if="
                                    config.descriptionField &&
                                    item[config.descriptionField]
                                "
                                class="mt-2 text-sm whitespace-pre-wrap text-foreground/70"
                            >
                                {{ item[config.descriptionField] }}
                            </p>
                            <a
                                v-if="config.url && item[config.url]"
                                :href="item[config.url]"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-block text-xs text-primary hover:underline"
                                >View publication →</a
                            >
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="editItem(item)"
                            ><Edit2 class="h-4 w-4"
                        /></Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="deleteItem(item)"
                            ><Trash2 class="h-4 w-4"
                        /></Button>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="rounded-lg border bg-muted/30 p-4">
                <form class="space-y-4" @submit.prevent="submit">
                    <div
                        v-for="field in config.fields"
                        :key="field.key"
                        class="grid gap-2"
                    >
                        <Label :for="`${type}-${field.key}`"
                            >{{ field.label
                            }}<span v-if="field.required"> *</span></Label
                        >
                        <Textarea
                            v-if="field.inputType === 'textarea'"
                            :id="`${type}-${field.key}`"
                            v-model="form[field.key]"
                            :placeholder="field.placeholder"
                            :rows="4"
                        />
                        <select
                            v-else-if="field.inputType === 'select'"
                            :id="`${type}-${field.key}`"
                            v-model="form[field.key]"
                            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            required
                        >
                            <option
                                v-for="level in proficiencies"
                                :key="level"
                                :value="level"
                            >
                                {{ level }}
                            </option>
                        </select>
                        <Input
                            v-else
                            :id="`${type}-${field.key}`"
                            v-model="form[field.key]"
                            :type="field.inputType ?? 'text'"
                            :placeholder="field.placeholder"
                            :required="field.required"
                        />
                        <InputError :message="form.errors[field.key]" />
                    </div>
                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing"
                            ><Save class="mr-2 h-4 w-4" />{{
                                editingId ? 'Update' : 'Add'
                            }}
                            {{ config.singular }}</Button
                        >
                        <Button
                            type="button"
                            variant="outline"
                            @click="resetForm"
                            >Cancel</Button
                        >
                    </div>
                </form>
            </div>

            <Button
                v-if="!showForm"
                type="button"
                variant="outline"
                class="w-full"
                @click="showForm = true"
                ><Plus class="mr-2 h-4 w-4" />Add {{ config.singular }}</Button
            >

            <div class="flex justify-between border-t pt-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="$emit('nextSection', previousSection)"
                    ><ChevronLeft class="mr-2 h-4 w-4" />Back</Button
                >
                <Button type="button" @click="$emit('nextSection', nextSection)"
                    >Next<ChevronRight class="ml-2 h-4 w-4"
                /></Button>
            </div>
        </CardContent>
    </Card>
</template>

