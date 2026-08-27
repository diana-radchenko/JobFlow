<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CalendarDays,
    ChevronLeft,
    CircleDollarSign,
    Clock3,
    Ellipsis,
    Eye,
    EyeOff,
    MapPin,
    Pencil,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type { UserWorkJobApplication, WorkJob } from '@/types/laravel-models';
import employerApplications from '@/routes/employer/applications';
import jobs from '@/routes/employer/jobs';

type Vacancy = WorkJob & { applications_count: number };
const props = defineProps<{
    job: Vacancy;
    applications: UserWorkJobApplication[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My Jobs', href: '/employer/jobs' },
            { title: 'Vacancy' },
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
        : 'Not published';
const formatSalary = () => {
    if (!props.job.salary_start && !props.job.salary_end) {
        return 'Not specified';
    }

    const currency = props.job.salary_currency ?? 'USD';
    const format = (value: string | number | null) =>
        value
            ? new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency,
                  maximumFractionDigits: 0,
              }).format(Number(value))
            : null;
    const start = format(props.job.salary_start);
    const end = format(props.job.salary_end);

    return `${start && end ? `${start} – ${end}` : (start ?? end)} / ${props.job.salary_period ?? 'year'}`;
};
const deleteJob = () => {
    if (confirm(`Delete "${props.job.title}"? This cannot be undone.`)) {
        useForm({}).delete(jobs.destroy.url(props.job.id));
    }
};
</script>

<template>
    <Head :title="props.job.title" />
    <div class="mx-auto max-w-5xl space-y-6 px-3 py-6 sm:px-6">
        <Link
            :href="jobs.index()"
            class="inline-flex items-center gap-1 text-sm text-foreground/60 transition-colors hover:text-foreground"
        >
            <ChevronLeft class="h-4 w-4" /> Back to My Jobs
        </Link>

        <section class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="min-w-0 space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge class="capitalize">{{ props.job.status }}</Badge>
                        <span class="text-sm text-foreground/60"
                            >Published
                            {{ formatDate(props.job.published_at) }}</span
                        >
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ props.job.title }}
                        </h1>
                        <p class="mt-2 text-lg text-foreground/70">
                            {{ props.job.company }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-stretch gap-2">
                    <Button as-child class="h-auto min-h-12 px-5 shadow-md">
                        <a href="#applicants" class="gap-3">
                            <Users class="h-5 w-5" />
                            <span class="text-left leading-tight"
                                ><span class="block text-base font-bold"
                                    >Applicants</span
                                ><span
                                    class="block text-sm font-semibold opacity-90"
                                    >{{
                                        props.job.applications_count
                                    }}
                                    total</span
                                ></span
                            >
                        </a>
                    </Button>
                    <Button as-child variant="outline" class="min-h-12"
                        ><Link :href="jobs.edit(props.job.id)"
                            ><Pencil class="mr-2 h-4 w-4" />Edit</Link
                        ></Button
                    >
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child
                            ><Button
                                variant="outline"
                                size="icon"
                                class="h-12 w-12"
                                aria-label="More vacancy actions"
                                ><Ellipsis class="h-5 w-5" /></Button
                        ></DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem as-child
                                ><Link :href="jobs.index()"
                                    >Back to My Jobs</Link
                                ></DropdownMenuItem
                            >
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="text-destructive"
                                @click="deleteJob"
                                ><Trash2 class="mr-2 h-4 w-4" />Delete
                                vacancy</DropdownMenuItem
                            >
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex gap-3 rounded-2xl bg-muted/60 p-4">
                    <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-foreground/50">Location</p>
                        <p class="font-semibold">
                            {{ props.job.location || 'Not specified' }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-2xl bg-muted/60 p-4">
                    <BriefcaseBusiness
                        class="mt-0.5 h-5 w-5 shrink-0 text-primary"
                    />
                    <div>
                        <p class="text-xs text-foreground/50">
                            Workplace · Employment
                        </p>
                        <p class="font-semibold">
                            {{
                                stringForHuman(
                                    props.job.workplace_type || 'not specified',
                                )
                            }}
                            ·
                            {{
                                stringForHuman(
                                    props.job.employment_type ||
                                        'not specified',
                                )
                            }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-2xl bg-muted/60 p-4">
                    <CircleDollarSign
                        class="mt-0.5 h-5 w-5 shrink-0 text-primary"
                    />
                    <div>
                        <p class="text-xs text-foreground/50">Salary</p>
                        <p class="font-semibold">{{ formatSalary() }}</p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-2xl bg-muted/60 p-4">
                    <Clock3 class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                    <div>
                        <p class="text-xs text-foreground/50">
                            Experience level
                        </p>
                        <p class="font-semibold">
                            {{
                                stringForHuman(
                                    props.job.position_level || 'not specified',
                                )
                            }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-2xl bg-muted/60 p-4">
                    <CalendarDays
                        class="mt-0.5 h-5 w-5 shrink-0 text-primary"
                    />
                    <div>
                        <p class="text-xs text-foreground/50">Industry</p>
                        <p class="font-semibold">
                            {{ props.job.industry || 'Not specified' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <Card
            ><CardContent class="space-y-7 p-6 sm:p-8">
                <div>
                    <h2 class="mb-2 text-lg font-bold">Description</h2>
                    <p class="whitespace-pre-wrap text-foreground/70">
                        {{ props.job.description }}
                    </p>
                </div>
                <div v-if="props.job.responsibilities">
                    <h2 class="mb-2 text-lg font-bold">Responsibilities</h2>
                    <p class="whitespace-pre-wrap text-foreground/70">
                        {{ props.job.responsibilities }}
                    </p>
                </div>
                <div v-if="props.job.requirements">
                    <h2 class="mb-2 text-lg font-bold">Requirements</h2>
                    <p class="whitespace-pre-wrap text-foreground/70">
                        {{ props.job.requirements }}
                    </p>
                </div>
                <div v-if="props.job.benefits">
                    <h2 class="mb-2 text-lg font-bold">Benefits</h2>
                    <p class="whitespace-pre-wrap text-foreground/70">
                        {{ props.job.benefits }}
                    </p>
                </div>
            </CardContent></Card
        >

        <section id="applicants" class="scroll-mt-6 space-y-4">
            <div
                class="flex flex-wrap items-end justify-between gap-3 rounded-2xl bg-primary px-5 py-4 text-primary-foreground shadow-md"
            >
                <div>
                    <p class="text-xl font-bold">Applicants</p>
                    <p class="text-sm opacity-85">
                        Real applications for this vacancy
                    </p>
                </div>
                <div class="text-4xl font-black">
                    {{ props.job.applications_count }}
                </div>
            </div>
            <div
                v-if="props.applications.length === 0"
                class="rounded-2xl border py-12 text-center text-foreground/60"
            >
                Nobody has applied to this vacancy yet.
            </div>
            <div class="grid gap-3">
                <Card
                    v-for="application in props.applications"
                    :key="application.id"
                    class="transition-all hover:border-primary/50 hover:shadow-md"
                >
                    <CardContent
                        class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-lg font-bold">
                                    {{
                                        application.user?.name ??
                                        application.user?.email
                                    }}
                                </p>
                                <span
                                    class="flex items-center gap-1 text-xs text-foreground/50"
                                    ><Eye
                                        v-if="application.viewed_at"
                                        class="h-4 w-4"
                                    /><EyeOff v-else class="h-4 w-4" />{{
                                        application.viewed_at ? 'Viewed' : 'New'
                                    }}</span
                                >
                            </div>
                            <p class="font-medium text-foreground/70">
                                {{
                                    application.resume?.title ?? props.job.title
                                }}
                            </p>
                            <p class="text-sm text-foreground/50">
                                Applied {{ formatDate(application.created_at) }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                :class="`rounded-full px-3 py-1 text-xs font-semibold ${getApplicationStatusColor(application.status)}`"
                                >{{ stringForHuman(application.status) }}</span
                            ><Button as-child
                                ><Link
                                    :href="
                                        employerApplications.show([
                                            props.job.id,
                                            application.id,
                                        ])
                                    "
                                    >View Application</Link
                                ></Button
                            >
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </div>
</template>

