<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import jobs from '@/routes/employer/jobs';
import type { WorkJob } from '@/types/laravel-models';

const props = defineProps<{
    job: WorkJob | null;
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
                        <Textarea
                            id="description"
                            v-model="form.description"
                            required
                            :rows="8"
                        />
                        <InputError :message="form.errors.description" />
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
