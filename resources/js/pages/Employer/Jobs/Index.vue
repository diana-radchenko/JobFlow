<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Pencil,
    Plus,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { WorkJob } from '@/types/laravel-models';
import employerJobs from '@/routes/employer/jobs';

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

defineProps<{
    jobs: (WorkJob & { applications_count: number })[];
}>();

const deleteJob = (job: WorkJob) => {
    if (confirm(`Delete "${job.title}"? This cannot be undone.`)) {
        useForm({}).delete(employerJobs.destroy.url(job.id));
    }
};

const formatDate = (date: string | null) =>
    date
        ? new Date(date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
          })
        : '';
</script>

<template>
    <Head title="My Jobs" />

    <div class="mx-auto max-w-4xl space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">My Jobs</h1>
                <p class="text-sm text-foreground/60">
                    Post openings and review the people who applied to them.
                </p>
            </div>
            <Button as-child>
                <Link :href="employerJobs.create()">
                    <Plus class="mr-2 h-4 w-4" />
                    Post a Job
                </Link>
            </Button>
        </div>

        <div v-if="jobs.length === 0" class="py-12 text-center">
            <BriefcaseBusiness
                class="mx-auto mb-3 h-10 w-10 text-foreground/30"
            />
            <p class="text-foreground/60">
                You haven't posted any jobs yet. Post your first opening to
                start receiving applications.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Card v-for="job in jobs" :key="job.id">
                <CardHeader>
                    <CardTitle>
                        <Link
                            :href="employerJobs.show(job.id)"
                            class="transition-colors hover:text-primary"
                        >
                            {{ job.title }}
                        </Link>
                    </CardTitle>
                    <CardDescription>
                        {{ job.company }} &middot; {{ job.location }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-wrap gap-4 text-xs text-foreground/60"
                    >
                        <Link
                            :href="`${employerJobs.show(job.id).url}#applicants`"
                            class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-base font-bold text-primary-foreground shadow"
                        >
                            <Users class="h-3.5 w-3.5" />
                            Applicants {{ job.applications_count }}
                        </Link>
                        <span>Updated {{ formatDate(job.updated_at) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button as-child size="sm">
                            <Link :href="employerJobs.show(job.id)">
                                View Vacancy
                            </Link>
                        </Button>
                        <Button as-child size="sm" variant="outline">
                            <Link :href="employerJobs.edit(job.id)">
                                <Pencil class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="deleteJob(job)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

