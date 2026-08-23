<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WorkJob } from '@/types/laravel-models';
import jobs from '@/routes/employer/jobs';

const props = defineProps<{
    job: WorkJob | null;
    jobOptions: {
        industries: string[];
        positionLevels: string[];
        employmentTypes: string[];
        workplaceTypes: string[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'My Jobs',
                href: '/employer/jobs',
            },
        ],
    },
});

const form = useForm({
    title: props.job?.title ?? '',
    company: props.job?.company ?? '',
    location: props.job?.location ?? '',
    contacts: props.job?.contacts ?? '',
    description: props.job?.description ?? '',
    salary_start: props.job?.salary_start?.toString() ?? '',
    salary_end: props.job?.salary_end?.toString() ?? '',
    technologies: ((props.job?.technologies ?? []) as string[]).join(', '),
    industry: props.job?.industry ?? '',
    position_level: props.job?.position_level ?? '',
    employment_type: props.job?.employment_type ?? '',
    workplace_type: props.job?.workplace_type ?? '',
    responsibilities: props.job?.responsibilities ?? '',
    requirements: props.job?.requirements ?? '',
    benefits: props.job?.benefits ?? '',
    salary_currency: props.job?.salary_currency ?? 'USD',
    salary_period: props.job?.salary_period ?? 'year',
    status: props.job?.status ?? 'published',
});

const submit = () => {
    // The column is a json array; the field is a plain comma-separated input.
    form.transform((data) => ({
        ...data,
        technologies: data.technologies
            .split(',')
            .map((technology) => technology.trim())
            .filter(Boolean),
    }));

    if (props.job) {
        form.put(jobs.update.url(props.job.id));
    } else {
        form.post(jobs.store.url());
    }
};
</script>

<template>
    <Head :title="props.job ? 'Edit Job' : 'Post a Job'" />

    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <Link
            :href="jobs.index()"
            class="inline-flex items-center gap-1 text-sm text-foreground/60 transition-colors hover:text-foreground"
        >
            <ChevronLeft class="h-4 w-4" />
            Back to my jobs
        </Link>

        <div>
            <h1 class="text-2xl font-semibold">
                {{ props.job ? 'Edit Job' : 'Post a Job' }}
            </h1>
            <p class="text-sm text-foreground/60">
                Candidates see this listing in Job Selection.
            </p>
        </div>

        <Card>
            <CardContent class="pt-6">
                <form @submit.prevent="submit" class="grid gap-5">
                    <div class="grid gap-2">
                        <Label for="title">Job title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            required
                            autofocus
                            placeholder="e.g., Senior Backend Engineer"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="industry">Industry</Label
                            ><select
                                id="industry"
                                v-model="form.industry"
                                class="rounded-md border bg-background p-2"
                            >
                                <option value="">Choose industry</option>
                                <option
                                    v-for="item in jobOptions.industries"
                                    :key="item"
                                >
                                    {{ item }}
                                </option></select
                            ><InputError :message="form.errors.industry" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="position_level">Position level</Label
                            ><select
                                id="position_level"
                                v-model="form.position_level"
                                class="rounded-md border bg-background p-2"
                            >
                                <option value="">Choose level</option>
                                <option
                                    v-for="item in jobOptions.positionLevels"
                                    :key="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="employment_type">Employment type</Label
                            ><select
                                id="employment_type"
                                v-model="form.employment_type"
                                class="rounded-md border bg-background p-2"
                            >
                                <option value="">Choose type</option>
                                <option
                                    v-for="item in jobOptions.employmentTypes"
                                    :key="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="workplace_type">Workplace</Label
                            ><select
                                id="workplace_type"
                                v-model="form.workplace_type"
                                class="rounded-md border bg-background p-2"
                            >
                                <option value="">Choose workplace</option>
                                <option
                                    v-for="item in jobOptions.workplaceTypes"
                                    :key="item"
                                >
                                    {{ item }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="company">Company</Label>
                            <Input
                                id="company"
                                v-model="form.company"
                                required
                            />
                            <InputError :message="form.errors.company" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="location">Location</Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                required
                                placeholder="e.g., Remote"
                            />
                            <InputError :message="form.errors.location" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="salary_start">Salary from</Label>
                            <Input
                                id="salary_start"
                                v-model="form.salary_start"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <InputError :message="form.errors.salary_start" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="salary_end">Salary to</Label>
                            <Input
                                id="salary_end"
                                v-model="form.salary_end"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                            <InputError :message="form.errors.salary_end" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="contacts">Contact</Label>
                        <Input
                            id="contacts"
                            v-model="form.contacts"
                            required
                            placeholder="careers@example.com"
                        />
                        <InputError :message="form.errors.contacts" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="technologies">Technologies</Label>
                        <Input
                            id="technologies"
                            v-model="form.technologies"
                            placeholder="PHP, Laravel, Vue.js"
                        />
                        <p class="text-xs text-foreground/60">
                            Separate each one with a comma.
                        </p>
                        <InputError :message="form.errors.technologies" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <MarkdownEditor
                            id="description"
                            v-model="form.description"
                            required
                            :rows="8"
                            placeholder="Describe the role, responsibilities, and requirements. Supports Markdown: ## headings, **bold**, _italic_, - lists."
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div
                        v-for="field in [
                            'responsibilities',
                            'requirements',
                            'benefits',
                        ] as const"
                        :key="field"
                        class="grid gap-2"
                    >
                        <Label :for="field">{{
                            field.charAt(0).toUpperCase() + field.slice(1)
                        }}</Label>
                        <MarkdownEditor
                            :id="field"
                            v-model="form[field]"
                            :rows="5"
                        />
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :disabled="form.processing">
                            {{ props.job ? 'Save Changes' : 'Post Job' }}
                        </Button>
                        <Button as-child type="button" variant="outline">
                            <Link :href="jobs.index()">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
