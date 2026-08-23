<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    Eye,
    EyeOff,
    MapPin,
    Pencil,
    Users,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type { UserWorkJobApplication, WorkJob } from '@/types/laravel-models';
import employerApplications from '@/routes/employer/applications';
import jobs from '@/routes/employer/jobs';

const props = defineProps<{
    job: WorkJob;
    applications: UserWorkJobApplication[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'My Jobs',
                href: '/employer/jobs',
            },
            {
                title: 'Applications',
            },
        ],
    },
});

const formatDate = (date: string | null) =>
    date
        ? new Date(date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : '';
</script>

<template>
    <Head :title="props.job.title" />

    <div class="mx-auto max-w-4xl space-y-6 px-2 py-6">
        <Link
            :href="jobs.index()"
            class="inline-flex items-center gap-1 text-sm text-foreground/60 transition-colors hover:text-foreground"
        >
            <ChevronLeft class="h-4 w-4" />
            Back to my jobs
        </Link>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold">{{ props.job.title }}</h1>
                <p class="flex items-center gap-2 text-sm text-foreground/60">
                    {{ props.job.company }}
                    <span class="flex items-center gap-1">
                        <MapPin class="h-3.5 w-3.5" />
                        {{ props.job.location }}
                    </span>
                </p>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <Badge v-if="props.job.status">{{
                        props.job.status
                    }}</Badge>
                    <Badge v-if="props.job.industry" variant="outline">{{
                        props.job.industry
                    }}</Badge>
                    <Badge v-if="props.job.position_level" variant="outline">{{
                        props.job.position_level
                    }}</Badge>
                    <Badge v-if="props.job.employment_type" variant="outline">{{
                        props.job.employment_type
                    }}</Badge>
                    <Badge v-if="props.job.workplace_type" variant="outline">{{
                        props.job.workplace_type
                    }}</Badge>
                    <Badge
                        v-for="technology in props.job.technologies"
                        :key="String(technology)"
                        variant="secondary"
                    >
                        {{ technology }}
                    </Badge>
                </div>
            </div>
            <Button as-child variant="outline">
                <Link :href="jobs.edit(props.job.id)">
                    <Pencil class="mr-2 h-4 w-4" />
                    Edit Job
                </Link>
            </Button>
        </div>

        <Card
            ><CardContent class="space-y-4 pt-6">
                <div>
                    <h2 class="font-semibold">Description</h2>
                    <p class="text-sm whitespace-pre-wrap text-foreground/70">
                        {{ props.job.description }}
                    </p>
                </div>
                <div v-if="props.job.responsibilities">
                    <h2 class="font-semibold">Responsibilities</h2>
                    <p class="text-sm whitespace-pre-wrap text-foreground/70">
                        {{ props.job.responsibilities }}
                    </p>
                </div>
                <div v-if="props.job.requirements">
                    <h2 class="font-semibold">Requirements</h2>
                    <p class="text-sm whitespace-pre-wrap text-foreground/70">
                        {{ props.job.requirements }}
                    </p>
                </div>
                <div v-if="props.job.benefits">
                    <h2 class="font-semibold">Benefits</h2>
                    <p class="text-sm whitespace-pre-wrap text-foreground/70">
                        {{ props.job.benefits }}
                    </p>
                </div>
            </CardContent></Card
        >

        <div>
            <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold">
                <Users class="h-5 w-5" />
                Applications ({{ props.applications.length }})
            </h2>

            <div
                v-if="props.applications.length === 0"
                class="py-12 text-center text-foreground/60"
            >
                Nobody has applied to this job yet.
            </div>

            <div class="grid gap-3">
                <Link
                    v-for="application in props.applications"
                    :key="application.id"
                    :href="
                        employerApplications.show([
                            props.job.id,
                            application.id,
                        ])
                    "
                    class="block"
                >
                    <Card class="transition-colors hover:bg-accent">
                        <CardContent
                            class="flex flex-wrap items-center justify-between gap-4 p-4"
                        >
                            <div class="space-y-1">
                                <div class="font-medium">
                                    {{
                                        application.user?.name ??
                                        application.user?.email
                                    }}
                                </div>
                                <div class="text-xs text-foreground/60">
                                    Applied
                                    {{ formatDate(application.created_at) }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex items-center gap-1 text-xs text-foreground/60"
                                    :title="
                                        application.viewed_at
                                            ? 'You have opened this application'
                                            : 'Not opened yet'
                                    "
                                >
                                    <Eye
                                        v-if="application.viewed_at"
                                        class="h-4 w-4"
                                    />
                                    <EyeOff v-else class="h-4 w-4" />
                                    {{
                                        application.viewed_at ? 'Viewed' : 'New'
                                    }}
                                </span>
                                <span
                                    :class="`rounded-full px-3 py-1 text-xs font-medium ${getApplicationStatusColor(application.status)}`"
                                >
                                    {{ stringForHuman(application.status) }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </div>
</template>
